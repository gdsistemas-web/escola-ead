<?php

namespace App\Http\Controllers\Api;

use App\Models\Course;
use App\Models\Enrollment;
use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Services\CourseCompletionService;
use App\Services\EnrollmentService;
use App\Services\ActivityLogger;
use App\Services\RetentionService;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    public function index(CourseCompletionService $completion, RetentionService $retention)
    {
        $query = Enrollment::with('course.category', 'course.teacher', 'user')->latest();

        if (request()->user()->hasRole('aluno')) {
            $query->where('user_id', request()->user()->id);
        }

        $paginated = $query->paginate(30);
        $paginated->getCollection()->transform(fn (Enrollment $enrollment) => $this->withAcademicStatus($enrollment, $completion, $retention));

        return $paginated;
    }

    public function store(Request $request, EnrollmentService $enrollments, ActivityLogger $logger)
    {
        $data = $request->validate([
            'course_id' => ['required', 'exists:courses,id'],
            'user_id' => ['nullable', 'exists:users,id'],
            'source' => ['nullable', 'in:automatic,manual'],
            'document' => ['required_without:user_id', 'nullable', 'string', 'max:30'],
            'phone' => ['required_without:user_id', 'nullable', 'string', 'max:30'],
            'birthdate' => ['nullable', 'date', 'before:today'],
            'city' => ['required_without:user_id', 'nullable', 'string', 'max:120'],
            'state' => ['required_without:user_id', 'nullable', 'string', 'size:2'],
            'education_level' => ['nullable', 'string', 'max:120'],
            'occupation' => ['nullable', 'string', 'max:120'],
            'institution' => ['nullable', 'string', 'max:160'],
            'motivation' => ['nullable', 'string', 'max:1000'],
            'accessibility_needs' => ['nullable', 'string', 'max:1000'],
            'accept_terms' => ['required_without:user_id', 'nullable', 'accepted'],
        ]);

        abort_if(! empty($data['user_id']) && ! $request->user()->hasAnyRole(['administrador', 'professor']), 403);

        $enrollment = $enrollments->enroll(
            Course::findOrFail($data['course_id']),
            ! empty($data['user_id']) ? \App\Models\User::findOrFail($data['user_id']) : $request->user(),
            $data['source'] ?? 'automatic',
            $data,
        );

        $logger->log('enrollment.created', $enrollment, ['source' => $enrollment->source], $request);

        return $enrollment;
    }

    public function show(Enrollment $enrollment)
    {
        abort_unless(
            request()->user()->hasRole('administrador')
                || request()->user()->id === $enrollment->user_id
                || $enrollment->course?->teacher_id === request()->user()->id,
            403,
        );

        return $this->withAcademicStatus($enrollment->load([
            'course.category',
            'course.teacher',
            'course.modules.lessons.materials',
            'course.modules.lessons.progress' => fn ($query) => $query->where('user_id', $enrollment->user_id),
            'course.exams.attempts' => fn ($query) => $query->where('user_id', $enrollment->user_id)->latest(),
            'course.certificates' => fn ($query) => $query->where('user_id', $enrollment->user_id)->latest(),
            'user.profile',
        ]), app(CourseCompletionService::class), app(RetentionService::class));
    }

    public function update(Request $request, Enrollment $enrollment, ActivityLogger $logger)
    {
        abort_unless($request->user()->hasRole('administrador') || $enrollment->course?->teacher_id === $request->user()->id, 403);

        $enrollment->update($request->validate([
            'status' => ['sometimes', 'in:active,completed,cancelled,waiting'],
            'final_grade' => ['nullable', 'numeric', 'between:0,10'],
            'progress_percent' => ['nullable', 'integer', 'between:0,100'],
        ]));

        $logger->log('enrollment.updated', $enrollment, $enrollment->only(['status', 'final_grade', 'progress_percent']), $request);

        return $enrollment;
    }

    public function destroy(Enrollment $enrollment)
    {
        abort_unless(request()->user()->hasRole('administrador') || request()->user()->id === $enrollment->user_id, 403);

        $enrollment->update(['status' => 'cancelled']);

        return response()->noContent();
    }

    private function withAcademicStatus(Enrollment $enrollment, CourseCompletionService $completion, RetentionService $retention): Enrollment
    {
        $status = $completion->status($enrollment->course, $enrollment->user);
        $lessonIds = $completion->requiredLessonIds($enrollment->course);
        $completedLessonIds = $enrollment->user
            ->loadMissing('enrollments')
            ->getKey()
            ? \App\Models\LessonProgress::where('user_id', $enrollment->user_id)
                ->whereIn('lesson_id', $lessonIds)
                ->where('is_completed', true)
                ->pluck('lesson_id')
            : collect();

        $nextLesson = Lesson::whereIn('id', $lessonIds->diff($completedLessonIds))
            ->with('module:id,title')
            ->orderBy('position')
            ->first();

        $enrollment->setAttribute('academic_status', [
            'certificate_available' => $status['eligible'],
            'missing_requirements' => $status['missing'],
            'progress_percent' => $status['progress_percent'],
            'required_progress_percent' => $status['required_progress_percent'],
            'minimum_grade' => $status['minimum_grade'],
            'final_grade' => $status['final_grade'],
            'exams' => $status['exams'],
            'required_lessons' => $status['required_lessons'],
            'completed_required_lessons' => $status['completed_required_lessons'],
            'next_lesson' => $nextLesson,
        ]);
        $enrollment->setAttribute('progress_percent', $status['progress_percent']);
        $enrollment->setAttribute('pending_items', $retention->studentPendingItems($enrollment));

        return $enrollment;
    }
}
