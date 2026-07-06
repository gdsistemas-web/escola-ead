<?php

namespace App\Filament\Pages;

use App\Models\Course;
use App\Services\CourseReviewService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use UnitEnum;

class CourseReviewPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $navigationLabel = 'Revisão de cursos';

    protected static ?string $title = 'Revisão de cursos';

    protected static UnitEnum|string|null $navigationGroup = 'Academico';

    protected static ?string $slug = 'revisao-cursos';

    protected string $view = 'filament.pages.course-review';

    public ?int $selectedCourseId = null;

    public string $reviewNotes = '';

    public function getPendingCourses(): Collection
    {
        return Course::with('category', 'teacher', 'modules.lessons')
            ->where('status', 'pending_review')
            ->latest('submitted_for_review_at')
            ->get();
    }

    public function selectedCourse(): ?Course
    {
        if (! $this->selectedCourseId) {
            return null;
        }

        return Course::with('category', 'teacher', 'modules.lessons', 'exams')
            ->where('status', 'pending_review')
            ->find($this->selectedCourseId);
    }

    public function openCourse(int $courseId): void
    {
        $this->selectedCourseId = Course::where('status', 'pending_review')->findOrFail($courseId)->id;
        $this->reviewNotes = '';
    }

    public function approve(CourseReviewService $review): void
    {
        $course = $this->selectedCourse();
        abort_unless($course, 404);

        $review->approve($course, auth()->user(), $this->reviewNotes ?: null);
        $this->selectedCourseId = null;
        $this->reviewNotes = '';

        Notification::make()->title('Curso aprovado e publicado')->success()->send();
    }

    public function requestChanges(CourseReviewService $review): void
    {
        $course = $this->selectedCourse();
        abort_unless($course, 404);

        $this->validate(['reviewNotes' => ['required', 'string', 'min:5', 'max:2000']]);

        $review->requestChanges($course, auth()->user(), $this->reviewNotes);
        $this->selectedCourseId = null;
        $this->reviewNotes = '';

        Notification::make()->title('Curso devolvido para ajustes')->warning()->send();
    }
}
