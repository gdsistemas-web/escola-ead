<?php

namespace App\Filament\Teacher\Pages;

use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\ForumTopic;
use App\Services\ScormImportService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use UnitEnum;

class MyCoursesPage extends Page
{
    use WithFileUploads;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?string $navigationLabel = 'Meus cursos';

    protected static ?string $title = 'Meus cursos';

    protected static UnitEnum|string|null $navigationGroup = 'Academico';

    protected static ?string $slug = 'cursos';

    protected string $view = 'filament.teacher.pages.my-courses';

    public ?int $selectedCourseId = null;

    public bool $showScormImport = false;

    public array $scormForm = [
        'category_id' => null,
        'course_name' => '',
    ];

    public ?TemporaryUploadedFile $scormFile = null;

    public function getCourses(): Collection
    {
        return Course::with('category')
            ->withCount(['modules', 'lessons', 'enrollments', 'forumTopics', 'certificates'])
            ->where('teacher_id', auth()->id())
            ->latest()
            ->get();
    }

    public function selectedCourse(): ?Course
    {
        if (! $this->selectedCourseId) {
            return null;
        }

        return Course::with([
            'category',
            'modules.lessons',
            'enrollments.user.profile',
            'exams.attempts.user',
            'forumTopics.author',
            'forumTopics.visibleReplies',
            'certificates',
        ])
            ->withCount(['modules', 'lessons', 'enrollments', 'forumTopics', 'certificates'])
            ->where('teacher_id', auth()->id())
            ->find($this->selectedCourseId);
    }

    public function openCourse(int $courseId): void
    {
        $course = Course::where('teacher_id', auth()->id())->findOrFail($courseId);
        $this->selectedCourseId = $course->id;
    }

    public function closeCourse(): void
    {
        $this->selectedCourseId = null;
    }

    public function studentsAtRisk(Course $course): int
    {
        return Enrollment::where('course_id', $course->id)
            ->where('status', 'active')
            ->where('updated_at', '<', now()->subDays(15))
            ->count();
    }

    public function unansweredTopics(Course $course): int
    {
        return ForumTopic::where(function ($query) use ($course) {
            $query->where('course_id', $course->id)
                ->orWhereHas('category', fn ($query) => $query->where('course_id', $course->id));
        })
            ->whereDoesntHave('replies')
            ->where(fn ($query) => $query->whereNull('status')->orWhere('status', '!=', 'hidden'))
            ->count();
    }

    public function summary(): array
    {
        $courses = $this->getCourses();

        return [
            'courses' => $courses->count(),
            'students' => $courses->sum('enrollments_count'),
            'topics' => $courses->sum('forum_topics_count'),
            'certificates' => $courses->sum('certificates_count'),
        ];
    }

    public function categories(): Collection
    {
        return Category::where('is_active', true)->orderBy('name')->get();
    }

    public function openScormImport(): void
    {
        $this->resetValidation();
        $this->scormForm = [
            'category_id' => Category::where('is_active', true)->orderBy('name')->value('id'),
            'course_name' => '',
        ];
        $this->scormFile = null;
        $this->showScormImport = true;
    }

    public function closeScormImport(): void
    {
        $this->showScormImport = false;
    }

    public function importScorm(ScormImportService $importer): void
    {
        $data = $this->validate([
            'scormForm.category_id' => ['required', 'exists:categories,id'],
            'scormForm.course_name' => ['nullable', 'string', 'max:255'],
            'scormFile' => ['required', 'file', 'mimes:zip', 'max:204800'],
        ]);

        $course = $importer->import(
            $this->scormFile,
            auth()->user(),
            (int) $data['scormForm']['category_id'],
            $data['scormForm']['course_name'] ?: null,
        );

        $this->showScormImport = false;
        $this->scormFile = null;
        $this->selectedCourseId = $course->id;

        Notification::make()
            ->title('SCORM importado')
            ->body("Curso criado como rascunho: {$course->name}")
            ->success()
            ->send();
    }
}
