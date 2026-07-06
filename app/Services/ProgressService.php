<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;

class ProgressService
{
    public function __construct(
        private readonly CourseCompletionService $completion,
        private readonly ForumService $forum,
    )
    {
    }

    public function saveLessonProgress(Lesson $lesson, User $user, int $watchedSeconds, int $progressPercent): LessonProgress
    {
        $lesson->loadMissing('module.course');
        $course = $lesson->module->course;
        $enrollment = Enrollment::where('course_id', $course->id)
            ->where('user_id', $user->id)
            ->whereIn('status', ['active', 'completed'])
            ->first();

        abort_unless($enrollment, 403, 'Matricula ativa obrigatoria para registrar progresso.');
        abort_unless($lesson->is_available && $lesson->module->is_available && $lesson->module->status === 'published', 422, 'Aula indisponível.');

        $durationSeconds = max(0, (int) $lesson->duration_minutes * 60);
        $calculatedPercent = $durationSeconds > 0
            ? (int) floor((max(0, $watchedSeconds) / $durationSeconds) * 100)
            : $progressPercent;

        $calculatedPercent = min(100, max(0, $calculatedPercent));
        $previous = LessonProgress::where('lesson_id', $lesson->id)->where('user_id', $user->id)->first();
        $safePercent = max((int) ($previous?->progress_percent ?? 0), $calculatedPercent);
        $safeSeconds = max((int) ($previous?->watched_seconds ?? 0), max(0, $watchedSeconds));

        $progress = LessonProgress::updateOrCreate(
            ['lesson_id' => $lesson->id, 'user_id' => $user->id],
            [
                'watched_seconds' => $safeSeconds,
                'progress_percent' => $safePercent,
                'is_completed' => $safePercent >= 95,
                'last_accessed_at' => now(),
            ]
        );

        $this->refreshCourseProgress($course, $user);

        if ($progress->is_completed) {
            $this->forum->reputation($user, 'lesson_completed', ForumService::POINTS['lesson_completed'], $progress, courseId: $course->id);
        }

        return $progress;
    }

    public function refreshCourseProgress(Course $course, User $user): void
    {
        $this->completion->refreshEnrollment($course, $user);
    }
}
