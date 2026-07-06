<?php

namespace App\Http\Controllers\Api;

use App\Models\Category;
use App\Models\Course;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CourseController extends Controller
{
    public function index()
    {
        $query = Course::with('category', 'teacher')->withCount('modules')->latest();

        if (request()->user()->hasRole('professor')) {
            $query->where('teacher_id', request()->user()->id);
        } elseif (! request()->user()->hasRole('administrador')) {
            $query->where('status', 'published');
        }

        return $query->paginate(20);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->hasAnyRole(['administrador', 'professor']), 403);

        $data = $this->validated($request);
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);
        $data['teacher_id'] = $request->user()->hasRole('administrador') ? ($data['teacher_id'] ?? $request->user()->id) : $request->user()->id;
        $data['status'] = $request->user()->hasRole('administrador') ? ($data['status'] ?? 'draft') : 'draft';

        return Course::create($data)->load('category', 'teacher');
    }

    public function show(Course $course)
    {
        abort_unless(
            request()->user()->hasRole('administrador')
                || $course->teacher_id === request()->user()->id
                || $course->status === 'published'
                || request()->user()->enrollments()->where('course_id', $course->id)->exists(),
            403,
        );

        return $course->load('category', 'teacher', 'modules.lessons.materials', 'exams.questions.options');
    }

    public function update(Request $request, Course $course)
    {
        abort_unless($request->user()->hasRole('administrador') || $course->teacher_id === $request->user()->id, 403);

        $data = $this->validated($request, true);

        if (! $request->user()->hasRole('administrador')) {
            unset($data['teacher_id'], $data['status'], $data['is_featured']);
        }

        $course->update($data);

        return $course->refresh()->load('category', 'teacher');
    }

    public function destroy(Course $course)
    {
        abort_unless(request()->user()->hasRole('administrador'), 403);

        $course->delete();

        return response()->noContent();
    }

    public function categories()
    {
        return Category::orderBy('name')->get();
    }

    private function validated(Request $request, bool $partial = false): array
    {
        return $request->validate([
            'category_id' => [$partial ? 'sometimes' : 'required', 'exists:categories,id'],
            'teacher_id' => [$partial ? 'sometimes' : 'nullable', 'exists:users,id'],
            'name' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'workload_hours' => ['nullable', 'integer', 'min:0'],
            'cover_image_path' => ['nullable', 'string'],
            'presentation_video_url' => ['nullable', 'string'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
            'minimum_grade' => ['nullable', 'numeric', 'between:0,10'],
            'minimum_progress_percent' => ['nullable', 'integer', 'between:0,100'],
            'seat_limit' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'in:draft,pending_review,changes_requested,published,closed'],
            'is_featured' => ['nullable', 'boolean'],
        ]);
    }
}
