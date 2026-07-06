<?php

namespace App\Filament\Teacher\Pages;

use App\Models\Category;
use App\Models\Course;
use App\Services\CourseReviewService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use UnitEnum;

class CourseAuthoringPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPencilSquare;

    protected static ?string $navigationLabel = 'Autoria de cursos';

    protected static ?string $title = 'Autoria de cursos';

    protected static UnitEnum|string|null $navigationGroup = 'Academico';

    protected static ?string $slug = 'autoria-cursos';

    protected string $view = 'filament.teacher.pages.course-authoring';

    public ?int $editingCourseId = null;

    public array $courseForm = [
        'category_id' => null,
        'name' => '',
        'short_description' => '',
        'description' => '',
        'workload_hours' => 0,
        'minimum_grade' => 7,
        'minimum_progress_percent' => 75,
        'seat_limit' => null,
        'presentation_video_url' => '',
        'starts_at' => null,
        'ends_at' => null,
    ];

    public function getCourses(): Collection
    {
        return Course::with('category', 'reviewer')
            ->where('teacher_id', auth()->id())
            ->latest()
            ->get();
    }

    public function categories(): Collection
    {
        return Category::where('is_active', true)->orderBy('name')->get();
    }

    public function editCourse(int $courseId): void
    {
        $course = Course::where('teacher_id', auth()->id())->findOrFail($courseId);
        abort_unless(in_array($course->status, ['draft', 'changes_requested'], true), 422);

        $this->editingCourseId = $course->id;
        $this->courseForm = [
            'category_id' => $course->category_id,
            'name' => $course->name,
            'short_description' => $course->short_description,
            'description' => $course->description,
            'workload_hours' => $course->workload_hours,
            'minimum_grade' => $course->minimum_grade,
            'minimum_progress_percent' => $course->minimum_progress_percent,
            'seat_limit' => $course->seat_limit,
            'presentation_video_url' => $course->presentation_video_url,
            'starts_at' => $course->starts_at?->format('Y-m-d'),
            'ends_at' => $course->ends_at?->format('Y-m-d'),
        ];
    }

    public function newCourse(): void
    {
        $this->editingCourseId = null;
        $this->reset('courseForm');
        $this->courseForm['minimum_grade'] = 7;
        $this->courseForm['minimum_progress_percent'] = 75;
        $this->courseForm['workload_hours'] = 0;
    }

    public function saveDraft(): void
    {
        $data = $this->validate([
            'courseForm.category_id' => ['required', 'exists:categories,id'],
            'courseForm.name' => ['required', 'string', 'max:255'],
            'courseForm.short_description' => ['nullable', 'string', 'max:500'],
            'courseForm.description' => ['nullable', 'string'],
            'courseForm.workload_hours' => ['required', 'integer', 'min:0'],
            'courseForm.minimum_grade' => ['required', 'numeric', 'between:0,10'],
            'courseForm.minimum_progress_percent' => ['required', 'integer', 'between:0,100'],
            'courseForm.seat_limit' => ['nullable', 'integer', 'min:1'],
            'courseForm.presentation_video_url' => ['nullable', 'string', 'max:500'],
            'courseForm.starts_at' => ['nullable', 'date'],
            'courseForm.ends_at' => ['nullable', 'date'],
        ])['courseForm'];

        $payload = [
            ...$data,
            'teacher_id' => auth()->id(),
            'slug' => $this->uniqueSlug($data['name'], $this->editingCourseId),
            'status' => 'draft',
            'is_featured' => false,
        ];

        if ($this->editingCourseId) {
            $course = Course::where('teacher_id', auth()->id())->findOrFail($this->editingCourseId);
            abort_unless(in_array($course->status, ['draft', 'changes_requested'], true), 422);
            $course->update($payload);
        } else {
            $course = Course::create($payload);
            $this->editingCourseId = $course->id;
        }

        Notification::make()->title('Rascunho salvo')->success()->send();
    }

    public function submitForReview(int $courseId, CourseReviewService $review): void
    {
        $course = Course::where('teacher_id', auth()->id())->findOrFail($courseId);

        try {
            $review->submit($course, auth()->user());
            Notification::make()->title('Curso enviado para revisão')->success()->send();
        } catch (ValidationException $exception) {
            Notification::make()
                ->title('Checklist incompleto')
                ->body($exception->errors()['course'][0] ?? 'Revise os campos obrigatórios.')
                ->danger()
                ->send();
        }
    }

    public function missingRequirements(Course $course): array
    {
        return app(CourseReviewService::class)->missingRequirements($course);
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 2;

        while (Course::where('slug', $slug)->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
