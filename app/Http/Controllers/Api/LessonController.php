<?php

namespace App\Http\Controllers\Api;

use App\Models\Lesson;
use App\Http\Controllers\Controller;
use App\Services\ProgressService;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    public function index()
    {
        return Lesson::with('module.course', 'materials')->paginate(30);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->hasAnyRole(['administrador', 'professor']), 403);

        return Lesson::create($request->validate([
            'course_module_id' => ['required', 'exists:course_modules,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'content_type' => ['required', 'in:youtube,vimeo,mp4,pdf,docx,pptx,external_link,scorm'],
            'content_url' => ['nullable', 'string'],
            'file_path' => ['nullable', 'string'],
            'duration_minutes' => ['nullable', 'integer', 'min:0'],
            'position' => ['nullable', 'integer', 'min:1'],
            'is_required' => ['nullable', 'boolean'],
            'is_available' => ['nullable', 'boolean'],
        ]));
    }

    public function show(Lesson $lesson)
    {
        return $lesson->load('module.course', 'materials');
    }

    public function update(Request $request, Lesson $lesson)
    {
        abort_unless($request->user()->hasRole('administrador') || $lesson->module?->course?->teacher_id === $request->user()->id, 403);

        $lesson->update($request->all());

        return $lesson;
    }

    public function destroy(Lesson $lesson)
    {
        abort_unless(request()->user()->hasRole('administrador'), 403);

        $lesson->delete();

        return response()->noContent();
    }

    public function progress(Request $request, Lesson $lesson, ProgressService $progress)
    {
        $data = $request->validate([
            'watched_seconds' => ['required', 'integer', 'min:0'],
            'progress_percent' => ['required', 'integer', 'between:0,100'],
        ]);

        return $progress->saveLessonProgress($lesson, $request->user(), $data['watched_seconds'], $data['progress_percent']);
    }
}
