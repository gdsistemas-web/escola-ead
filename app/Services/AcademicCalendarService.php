<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Exam;
use App\Models\ForumTopic;
use App\Models\User;

class AcademicCalendarService
{
    public function events(?User $user = null): array
    {
        $courseQuery = Course::query()->where('status', 'published');

        if ($user?->hasRole('professor')) {
            $courseQuery->where('teacher_id', $user->id);
        } elseif ($user?->hasRole('aluno')) {
            $courseQuery->whereHas('enrollments', fn ($query) => $query->where('user_id', $user->id));
        }

        $courseIds = $courseQuery->pluck('id');

        return collect()
            ->merge($this->courseEvents($courseIds))
            ->merge($this->examEvents($courseIds))
            ->merge($this->forumAssessmentEvents($courseIds))
            ->sortBy('starts_at')
            ->values()
            ->all();
    }

    private function courseEvents($courseIds): array
    {
        return Course::whereIn('id', $courseIds)
            ->where(fn ($query) => $query->whereNotNull('starts_at')->orWhereNotNull('ends_at'))
            ->get()
            ->flatMap(function (Course $course) {
                return collect([
                    $course->starts_at ? [
                        'type' => 'course_start',
                        'title' => "Inicio do curso: {$course->name}",
                        'course_id' => $course->id,
                        'starts_at' => $course->starts_at,
                        'ends_at' => $course->starts_at,
                    ] : null,
                    $course->ends_at ? [
                        'type' => 'course_end',
                        'title' => "Encerramento do curso: {$course->name}",
                        'course_id' => $course->id,
                        'starts_at' => $course->ends_at,
                        'ends_at' => $course->ends_at,
                    ] : null,
                ])->filter();
            })
            ->values()
            ->all();
    }

    private function examEvents($courseIds): array
    {
        return Exam::with('course:id,name')
            ->whereIn('course_id', $courseIds)
            ->where(fn ($query) => $query->whereNotNull('opens_at')->orWhereNotNull('closes_at'))
            ->get()
            ->flatMap(function (Exam $exam) {
                return collect([
                    $exam->opens_at ? [
                        'type' => 'exam_open',
                        'title' => "Avaliação aberta: {$exam->title}",
                        'course_id' => $exam->course_id,
                        'exam_id' => $exam->id,
                        'starts_at' => $exam->opens_at,
                        'ends_at' => $exam->opens_at,
                    ] : null,
                    $exam->closes_at ? [
                        'type' => 'exam_close',
                        'title' => "Prazo da avaliacao: {$exam->title}",
                        'course_id' => $exam->course_id,
                        'exam_id' => $exam->id,
                        'starts_at' => $exam->closes_at,
                        'ends_at' => $exam->closes_at,
                    ] : null,
                ])->filter();
            })
            ->values()
            ->all();
    }

    private function forumAssessmentEvents($courseIds): array
    {
        return ForumTopic::whereIn('course_id', $courseIds)
            ->where('is_assessment', true)
            ->whereNotNull('assessment_due_at')
            ->get()
            ->map(fn (ForumTopic $topic) => [
                'type' => 'forum_assessment_due',
                'title' => "Prazo no forum: {$topic->title}",
                'course_id' => $topic->course_id,
                'topic_id' => $topic->id,
                'starts_at' => $topic->assessment_due_at,
                'ends_at' => $topic->assessment_due_at,
            ])
            ->all();
    }
}
