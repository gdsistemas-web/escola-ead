<?php

namespace App\Filament\Student\Pages;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Services\CertificateService;
use App\Services\CourseCompletionService;
use App\Services\ProgressService;
use App\Services\RetentionService;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use UnitEnum;

class MyCoursesPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPlayCircle;

    protected static ?string $navigationLabel = 'Meus cursos';

    protected static ?string $title = 'Meus cursos';

    protected static UnitEnum|string|null $navigationGroup = 'Academico';

    protected static ?string $slug = 'cursos';

    protected string $view = 'filament.student.pages.my-courses';

    public ?int $selectedEnrollmentId = null;

    public ?int $selectedLessonId = null;

    public function getEnrollments(): Collection
    {
        return Enrollment::with('course.category', 'course.teacher')
            ->where('user_id', auth()->id())
            ->latest('enrolled_at')
            ->get();
    }

    public function selectedEnrollment(): ?Enrollment
    {
        if (! $this->selectedEnrollmentId) {
            return null;
        }

        return Enrollment::with([
            'course.category',
            'course.teacher',
            'course.modules.lessons.materials',
            'course.modules.lessons.progress' => fn ($query) => $query->where('user_id', auth()->id()),
            'course.exams.attempts' => fn ($query) => $query->where('user_id', auth()->id())->latest(),
            'course.certificates' => fn ($query) => $query->where('user_id', auth()->id())->latest(),
        ])
            ->where('user_id', auth()->id())
            ->find($this->selectedEnrollmentId);
    }

    public function selectedLesson(): ?Lesson
    {
        $enrollment = $this->selectedEnrollment();

        if (! $enrollment) {
            return null;
        }

        return $enrollment->course->modules
            ->flatMap(fn ($module) => $module->lessons)
            ->firstWhere('id', $this->selectedLessonId)
            ?? $this->firstAvailableLesson($enrollment);
    }

    public function academicStatus(Enrollment $enrollment): array
    {
        $completion = app(CourseCompletionService::class);
        $status = $completion->status($enrollment->course, $enrollment->user);
        $lessonIds = $completion->requiredLessonIds($enrollment->course);
        $completedLessonIds = LessonProgress::where('user_id', $enrollment->user_id)
            ->whereIn('lesson_id', $lessonIds)
            ->where('is_completed', true)
            ->pluck('lesson_id');

        $nextLesson = Lesson::whereIn('id', $lessonIds->diff($completedLessonIds))
            ->with('module:id,title')
            ->orderBy('position')
            ->first();

        return $status + [
            'next_lesson' => $nextLesson,
            'pending_items' => app(RetentionService::class)->studentPendingItems($enrollment),
        ];
    }

    public function journeySummary(): array
    {
        $enrollments = $this->getEnrollments();
        $statuses = $enrollments->map(fn (Enrollment $enrollment) => $this->academicStatus($enrollment));

        return [
            'active' => $enrollments->where('status', 'active')->count(),
            'completed' => $enrollments->where('status', 'completed')->count(),
            'average_progress' => $enrollments->count() ? round($statuses->avg('progress_percent')) : 0,
            'certificates_available' => $statuses->where('eligible', true)->count(),
            'next' => $enrollments
                ->map(fn (Enrollment $enrollment) => [
                    'enrollment' => $enrollment,
                    'status' => $this->academicStatus($enrollment),
                ])
                ->filter(fn (array $item) => ! $item['status']['eligible'])
                ->sortByDesc(fn (array $item) => $item['status']['progress_percent'])
                ->first(),
        ];
    }

    public function openEnrollment(int $enrollmentId): void
    {
        $enrollment = Enrollment::with('course')
            ->where('user_id', auth()->id())
            ->findOrFail($enrollmentId);

        $this->selectedEnrollmentId = $enrollment->id;

        $status = $this->academicStatus($enrollment);
        $this->selectedLessonId = $status['next_lesson']?->id ?? $this->firstAvailableLesson($this->selectedEnrollment())?->id;
    }

    public function closeEnrollment(): void
    {
        $this->selectedEnrollmentId = null;
        $this->selectedLessonId = null;
    }

    public function selectLesson(int $lessonId): void
    {
        $lessons = $this->selectedEnrollment()?->course?->modules
            ->flatMap(fn ($module) => $module->lessons) ?? collect();

        abort_unless($lessons->contains('id', $lessonId), 403);

        $this->selectedLessonId = $lessonId;
    }

    public function completeLesson(int $lessonId): void
    {
        $lesson = $this->selectedEnrollment()?->course?->modules
            ->flatMap(fn ($module) => $module->lessons)
            ->firstWhere('id', $lessonId);

        abort_unless($lesson, 403);

        app(ProgressService::class)->saveLessonProgress(
            $lesson,
            auth()->user(),
            max(60, (int) $lesson->duration_minutes * 60),
            100,
        );

        Notification::make()
            ->title('Progresso salvo')
            ->body('Aula marcada como concluída.')
            ->success()
            ->send();
    }

    public function issueCertificate(int $courseId): void
    {
        $certificate = app(CertificateService::class)->issue(Course::findOrFail($courseId), auth()->user());

        Notification::make()
            ->title('Certificado emitido')
            ->body("Código: {$certificate->code}")
            ->success()
            ->send();
    }

    private function firstAvailableLesson(?Enrollment $enrollment): ?Lesson
    {
        if (! $enrollment) {
            return null;
        }

        $lessons = $enrollment->course->modules->flatMap(fn ($module) => $module->lessons);

        return $lessons->first(fn (Lesson $lesson) => $lesson->is_available) ?? $lessons->first();
    }
}
