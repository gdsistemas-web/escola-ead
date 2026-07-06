<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\ForumTopic;
use App\Models\LessonProgress;
use App\Models\User;

class ReportService
{
    public function __construct(private readonly RetentionService $retention)
    {
    }

    public function dashboard(?User $user = null): array
    {
        $base = [
            'total_students' => User::role('aluno')->count(),
            'total_teachers' => User::role('professor')->count(),
            'total_courses' => Course::count(),
            'active_enrollments' => Enrollment::where('status', 'active')->count(),
            'issued_certificates' => Certificate::count(),
            'active_students' => Enrollment::where('updated_at', '>=', now()->subDays(30))->distinct('user_id')->count('user_id'),
            'monthly_accesses' => 0,
            'popular_courses' => Course::withCount('enrollments')->orderByDesc('enrollments_count')->limit(5)->get(),
            'enrollments_by_period' => Enrollment::selectRaw('DATE(enrolled_at) as date, COUNT(*) as total')->groupBy('date')->orderBy('date')->limit(30)->get(),
        ];

        if (! $user) {
            return $base;
        }

        if ($user->hasRole('aluno')) {
            return $base + ['student' => $this->studentDashboard($user), 'pedagogical' => $this->retention->pedagogicalReport($user)];
        }

        if ($user->hasRole('professor')) {
            return $base + ['teacher' => $this->teacherDashboard($user), 'pedagogical' => $this->retention->pedagogicalReport($user)];
        }

        return $base + ['admin' => $this->adminDashboard(), 'pedagogical' => $this->retention->pedagogicalReport($user)];
    }

    private function studentDashboard(User $student): array
    {
        $enrollments = Enrollment::with('course')
            ->where('user_id', $student->id)
            ->latest()
            ->get();

        return [
            'active_courses' => $enrollments->where('status', 'active')->count(),
            'completed_courses' => $enrollments->where('status', 'completed')->count(),
            'certificates' => Certificate::where('user_id', $student->id)->latest()->limit(6)->get(),
            'pending_certificates' => $enrollments
                ->where('status', 'completed')
                ->filter(fn (Enrollment $enrollment) => ! Certificate::where('course_id', $enrollment->course_id)->where('user_id', $student->id)->exists())
                ->values(),
            'recent_progress' => LessonProgress::with('lesson.module.course')
                ->where('user_id', $student->id)
                ->latest('last_accessed_at')
                ->limit(5)
                ->get(),
            'pending_items' => $enrollments
                ->where('status', 'active')
                ->map(fn (Enrollment $enrollment) => [
                    'enrollment_id' => $enrollment->id,
                    'course' => $enrollment->course->name,
                    'pending' => $this->retention->studentPendingItems($enrollment),
                ])
                ->values(),
        ];
    }

    private function teacherDashboard(User $teacher): array
    {
        $courseIds = Course::where('teacher_id', $teacher->id)->pluck('id');

        return [
            'courses' => $courseIds->count(),
            'active_enrollments' => Enrollment::whereIn('course_id', $courseIds)->where('status', 'active')->count(),
            'completed_enrollments' => Enrollment::whereIn('course_id', $courseIds)->where('status', 'completed')->count(),
            'students_at_risk' => Enrollment::with('course', 'user')
                ->whereIn('course_id', $courseIds)
                ->where('status', 'active')
                ->where('updated_at', '<', now()->subDays(15))
                ->limit(20)
                ->get(),
            'forum_without_reply' => ForumTopic::with('course')
                ->whereIn('course_id', $courseIds)
                ->whereDoesntHave('replies')
                ->latest()
                ->limit(10)
                ->get(),
        ];
    }

    private function adminDashboard(): array
    {
        return [
            'students_at_risk' => Enrollment::with('course', 'user')
                ->where('status', 'active')
                ->where('updated_at', '<', now()->subDays(15))
                ->limit(30)
                ->get(),
            'forum_without_reply' => ForumTopic::with('course')
                ->whereDoesntHave('replies')
                ->latest()
                ->limit(15)
                ->get(),
            'certificate_conversion_percent' => Enrollment::where('status', 'completed')->count()
                ? round((Certificate::count() / Enrollment::where('status', 'completed')->count()) * 100, 1)
                : 0,
        ];
    }
}
