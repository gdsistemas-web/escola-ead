<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Support\Collection;

class CourseCompletionService
{
    public function __construct(private readonly ForumService $forum)
    {
    }

    public function status(Course $course, User $student): array
    {
        $enrollment = Enrollment::where('course_id', $course->id)
            ->where('user_id', $student->id)
            ->first();

        $progressPercent = $this->progressPercent($course, $student);
        $examStatus = $this->examStatus($course, $student);
        $finalGrade = $examStatus['final_grade'];
        $hasEnrollment = (bool) $enrollment;
        $enrollmentIsValid = $enrollment && in_array($enrollment->status, ['active', 'completed'], true);
        $progressPassed = $progressPercent >= (int) $course->minimum_progress_percent;
        $gradePassed = $examStatus['passed'] && ($finalGrade === null || $finalGrade >= (float) $course->minimum_grade);

        $missing = [];

        if (! $hasEnrollment) {
            $missing[] = 'matricula';
        } elseif (! $enrollmentIsValid) {
            $missing[] = 'matricula_ativa';
        }

        if (! $progressPassed) {
            $missing[] = 'progresso_minimo';
        }

        if (! $gradePassed) {
            $missing[] = 'nota_minima';
        }

        return [
            'eligible' => $enrollmentIsValid && $progressPassed && $gradePassed,
            'missing' => $missing,
            'enrollment' => $enrollment,
            'progress_percent' => $progressPercent,
            'required_progress_percent' => (int) $course->minimum_progress_percent,
            'final_grade' => $finalGrade,
            'minimum_grade' => (float) $course->minimum_grade,
            'exams' => $examStatus['exams'],
            'exams_passed' => $examStatus['passed'],
            'required_lessons' => $this->requiredLessonIds($course)->count(),
            'completed_required_lessons' => $this->completedRequiredLessons($course, $student),
        ];
    }

    public function completeIfEligible(Course $course, User $student): ?Enrollment
    {
        $status = $this->status($course, $student);

        if (! $status['eligible'] || ! $status['enrollment']) {
            return null;
        }

        $status['enrollment']->update([
            'status' => 'completed',
            'progress_percent' => $status['progress_percent'],
            'final_grade' => $status['final_grade'],
            'completed_at' => $status['enrollment']->completed_at ?? now(),
        ]);

        $this->forum->reputation($student, 'course_completed', ForumService::POINTS['course_completed'], $status['enrollment'], courseId: $course->id);

        return $status['enrollment']->refresh();
    }

    public function refreshEnrollment(Course $course, User $student): void
    {
        $status = $this->status($course, $student);

        if (! $status['enrollment']) {
            return;
        }

        $updates = [
            'progress_percent' => $status['progress_percent'],
            'final_grade' => $status['final_grade'],
        ];

        if ($status['eligible']) {
            $updates['status'] = 'completed';
            $updates['completed_at'] = $status['enrollment']->completed_at ?? now();
        }

        $status['enrollment']->update($updates);
    }

    public function progressPercent(Course $course, User $student): int
    {
        $lessonIds = $this->requiredLessonIds($course);

        if ($lessonIds->isEmpty()) {
            return 100;
        }

        $completed = LessonProgress::where('user_id', $student->id)
            ->whereIn('lesson_id', $lessonIds)
            ->where('is_completed', true)
            ->count();

        return (int) floor(($completed / $lessonIds->count()) * 100);
    }

    public function requiredLessonIds(Course $course): Collection
    {
        return $course->modules()
            ->with(['lessons' => fn ($query) => $query
                ->select('id', 'course_module_id', 'is_required')
                ->where('is_required', true)
                ->where('is_available', true)])
            ->where('is_available', true)
            ->where('status', 'published')
            ->get()
            ->flatMap(fn ($module) => $module->lessons->pluck('id'))
            ->values();
    }

    private function completedRequiredLessons(Course $course, User $student): int
    {
        $lessonIds = $this->requiredLessonIds($course);

        if ($lessonIds->isEmpty()) {
            return 0;
        }

        return LessonProgress::where('user_id', $student->id)
            ->whereIn('lesson_id', $lessonIds)
            ->where('is_completed', true)
            ->count();
    }

    private function examStatus(Course $course, User $student): array
    {
        $exams = Exam::where('course_id', $course->id)
            ->where('is_active', true)
            ->get();

        if ($exams->isEmpty()) {
            return [
                'passed' => true,
                'final_grade' => null,
                'exams' => [],
            ];
        }

        $statuses = $exams->map(function (Exam $exam) use ($student) {
            $bestAttempt = ExamAttempt::where('exam_id', $exam->id)
                ->where('user_id', $student->id)
                ->where('status', 'graded')
                ->orderByDesc('grade')
                ->first();

            $grade = $bestAttempt?->grade === null ? null : (float) $bestAttempt->grade;

            return [
                'id' => $exam->id,
                'title' => $exam->title,
                'minimum_grade' => (float) $exam->minimum_grade,
                'best_grade' => $grade,
                'passed' => $grade !== null && $grade >= (float) $exam->minimum_grade,
            ];
        });

        $graded = $statuses->pluck('best_grade')->filter(fn ($grade) => $grade !== null);

        return [
            'passed' => $statuses->every(fn (array $exam) => $exam['passed']),
            'final_grade' => $graded->isEmpty() ? null : round($graded->avg(), 2),
            'exams' => $statuses->values()->all(),
        ];
    }
}
