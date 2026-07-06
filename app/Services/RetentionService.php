<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\ForumNotification;
use App\Models\ForumTopic;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Support\Collection;

class RetentionService
{
    public function __construct(
        private readonly CourseCompletionService $completion,
        private readonly NotificationService $notifications,
    )
    {
    }

    public function runStudentInactivityAlerts(bool $mail = false): array
    {
        $counts = ['7' => 0, '15' => 0, '30' => 0];

        Enrollment::with('course', 'user')
            ->where('status', 'active')
            ->chunkById(100, function ($enrollments) use (&$counts, $mail) {
                foreach ($enrollments as $enrollment) {
                    $days = $this->inactiveDays($enrollment);
                    $milestone = collect([30, 15, 7])->first(fn (int $value) => $days >= $value);

                    if (! $milestone) {
                        continue;
                    }

                    if ($this->notifyStudentInactivity($enrollment, $milestone, $days, $mail)) {
                        $counts[(string) $milestone]++;
                    }
                }
            });

        return $counts;
    }

    public function runForumSlaAlerts(int $hours = 48, bool $mail = false): int
    {
        $created = 0;

        ForumTopic::with('course.teacher', 'author')
            ->where('status', 'open')
            ->whereDoesntHave('replies')
            ->where('created_at', '<=', now()->subHours($hours))
            ->chunkById(100, function ($topics) use (&$created, $hours, $mail) {
                foreach ($topics as $topic) {
                    $teacher = $topic->course?->teacher;

                    if (! $teacher) {
                        continue;
                    }

                    $key = "forum_sla_{$hours}_topic_{$topic->id}";
                    $exists = ForumNotification::where('user_id', $teacher->id)->where('type', $key)->exists();

                    if ($exists) {
                        continue;
                    }

                    ForumNotification::create([
                        'user_id' => $teacher->id,
                        'forum_topic_id' => $topic->id,
                        'type' => $key,
                        'title' => 'Duvida sem resposta no forum',
                        'body' => "O tópico \"{$topic->title}\" está sem resposta há mais de {$hours} horas.",
                        'url' => '/aluno/forum',
                    ]);

                    $this->notifications->notify(
                        $teacher,
                        $key,
                        'Duvida sem resposta no forum',
                        "O tópico \"{$topic->title}\" precisa de acompanhamento docente.",
                        '/aluno/forum',
                        $mail,
                    );

                    $created++;
                }
            });

        return $created;
    }

    public function studentPendingItems(Enrollment $enrollment): array
    {
        $status = $this->completion->status($enrollment->course, $enrollment->user);
        $lessonIds = $this->completion->requiredLessonIds($enrollment->course);
        $completedLessonIds = LessonProgress::where('user_id', $enrollment->user_id)
            ->whereIn('lesson_id', $lessonIds)
            ->where('is_completed', true)
            ->pluck('lesson_id');
        $pendingLessons = Lesson::whereIn('id', $lessonIds->diff($completedLessonIds))
            ->with('module:id,title')
            ->orderBy('position')
            ->get();
        $pendingExams = collect($status['exams'])->reject(fn (array $exam) => $exam['passed'])->values();
        $hasCertificate = Certificate::where('course_id', $enrollment->course_id)
            ->where('user_id', $enrollment->user_id)
            ->exists();

        return [
            'certificate_available' => $status['eligible'],
            'certificate_issued' => $hasCertificate,
            'summary' => [
                'pending_lessons' => $pendingLessons->count(),
                'pending_exams' => $pendingExams->count(),
                'missing_requirements' => count($status['missing']),
            ],
            'lessons' => $pendingLessons->map(fn (Lesson $lesson) => [
                'id' => $lesson->id,
                'title' => $lesson->title,
                'module' => $lesson->module?->title,
            ])->values()->all(),
            'exams' => $pendingExams->all(),
            'requirements' => [
                'progress_percent' => $status['progress_percent'],
                'required_progress_percent' => $status['required_progress_percent'],
                'final_grade' => $status['final_grade'],
                'minimum_grade' => $status['minimum_grade'],
                'missing' => $status['missing'],
            ],
        ];
    }

    public function pedagogicalReport(?User $user = null): array
    {
        $courseQuery = Course::query();

        if ($user?->hasRole('professor')) {
            $courseQuery->where('teacher_id', $user->id);
        }

        $courses = $courseQuery
            ->withCount([
                'enrollments',
                'enrollments as active_enrollments_count' => fn ($query) => $query->where('status', 'active'),
                'enrollments as completed_enrollments_count' => fn ($query) => $query->where('status', 'completed'),
                'certificates',
                'forumTopics',
            ])
            ->get();

        return [
            'courses' => $courses->map(fn (Course $course) => [
                'id' => $course->id,
                'name' => $course->name,
                'enrollments' => $course->enrollments_count,
                'active' => $course->active_enrollments_count,
                'completed' => $course->completed_enrollments_count,
                'certificates' => $course->certificates_count,
                'completion_rate' => $course->enrollments_count ? round(($course->completed_enrollments_count / $course->enrollments_count) * 100, 1) : 0,
                'certificate_rate' => $course->completed_enrollments_count ? round(($course->certificates_count / $course->completed_enrollments_count) * 100, 1) : 0,
                'average_grade' => round((float) Enrollment::where('course_id', $course->id)->whereNotNull('final_grade')->avg('final_grade'), 2),
                'forum_topics' => $course->forum_topics_count,
                'students_at_risk' => Enrollment::where('course_id', $course->id)->where('status', 'active')->where('updated_at', '<', now()->subDays(15))->count(),
            ])->values()->all(),
            'totals' => [
                'students_at_risk_7_days' => $this->riskCount(7, $user),
                'students_at_risk_15_days' => $this->riskCount(15, $user),
                'students_at_risk_30_days' => $this->riskCount(30, $user),
                'forum_without_reply_48h' => $this->forumWithoutReplyCount(48, $user),
            ],
        ];
    }

    private function inactiveDays(Enrollment $enrollment): int
    {
        $lastProgress = LessonProgress::where('user_id', $enrollment->user_id)
            ->whereHas('lesson.module', fn ($query) => $query->where('course_id', $enrollment->course_id))
            ->latest('last_accessed_at')
            ->value('last_accessed_at');
        $lastActivity = $lastProgress ? \Carbon\Carbon::parse($lastProgress) : $enrollment->updated_at;

        return (int) floor($lastActivity->diffInDays(now()));
    }

    private function notifyStudentInactivity(Enrollment $enrollment, int $milestone, int $days, bool $mail = false): bool
    {
        $key = "student_inactive_{$milestone}_enrollment_{$enrollment->id}";

        return $this->notifications->notify(
            $enrollment->user,
            $key,
            "Você está há {$days} dias sem avançar",
            "Continue o curso {$enrollment->course->name}. Pequenos avancos ajudam voce a chegar ao certificado.",
            '/aluno/cursos',
            $mail,
        )->wasRecentlyCreated;
    }

    private function riskCount(int $days, ?User $user = null): int
    {
        $query = Enrollment::query()
            ->where('status', 'active')
            ->where('updated_at', '<=', now()->subDays($days));

        if ($user?->hasRole('professor')) {
            $query->whereHas('course', fn ($query) => $query->where('teacher_id', $user->id));
        }

        return $query->count();
    }

    private function forumWithoutReplyCount(int $hours, ?User $user = null): int
    {
        $query = ForumTopic::query()
            ->where('status', 'open')
            ->whereDoesntHave('replies')
            ->where('created_at', '<=', now()->subHours($hours));

        if ($user?->hasRole('professor')) {
            $query->whereHas('course', fn ($query) => $query->where('teacher_id', $user->id));
        }

        return $query->count();
    }
}
