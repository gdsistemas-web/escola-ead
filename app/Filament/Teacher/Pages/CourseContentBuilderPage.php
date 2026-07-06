<?php

namespace App\Filament\Teacher\Pages;

use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Exam;
use App\Models\Lesson;
use App\Models\LessonMaterial;
use App\Models\Question;
use App\Models\QuestionOption;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use UnitEnum;

class CourseContentBuilderPage extends Page
{
    use WithFileUploads;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Conteúdos';

    protected static ?string $title = 'Conteúdos';

    protected static UnitEnum|string|null $navigationGroup = 'Academico';

    protected static ?string $slug = 'conteudos';

    protected string $view = 'filament.teacher.pages.course-content-builder';

    public ?int $selectedCourseId = null;

    public array $moduleForm = ['title' => '', 'description' => '', 'position' => null];

    public array $lessonForm = [
        'course_module_id' => null,
        'title' => '',
        'description' => '',
        'content_type' => 'youtube',
        'content_url' => '',
        'duration_minutes' => 0,
        'position' => null,
        'is_required' => true,
        'is_available' => true,
    ];

    public array $materialForm = ['lesson_id' => null, 'title' => ''];

    public ?TemporaryUploadedFile $materialFile = null;

    public array $examForm = [
        'course_module_id' => null,
        'title' => '',
        'description' => '',
        'minimum_grade' => 7,
        'time_limit_minutes' => null,
        'max_attempts' => 1,
        'correction_type' => 'automatic',
        'is_active' => true,
    ];

    public array $questionForm = [
        'exam_id' => null,
        'type' => 'multiple_choice',
        'statement' => '',
        'weight' => 1,
        'option_a' => '',
        'option_b' => '',
        'option_c' => '',
        'option_d' => '',
        'correct_option' => 'A',
    ];

    public function getCourses(): Collection
    {
        return Course::withCount(['modules', 'lessons', 'exams'])
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
            'modules.lessons.materials',
            'exams.questions.options',
        ])
            ->where('teacher_id', auth()->id())
            ->find($this->selectedCourseId);
    }

    public function openCourse(int $courseId): void
    {
        $course = Course::where('teacher_id', auth()->id())->findOrFail($courseId);
        $this->selectedCourseId = $course->id;
        $this->lessonForm['course_module_id'] = $course->modules()->orderBy('position')->value('id');
        $this->examForm['course_module_id'] = $this->lessonForm['course_module_id'];
    }

    public function createModule(): void
    {
        $course = $this->editableCourse();
        $data = $this->validate([
            'moduleForm.title' => ['required', 'string', 'max:255'],
            'moduleForm.description' => ['nullable', 'string', 'max:1000'],
            'moduleForm.position' => ['nullable', 'integer', 'min:1'],
        ])['moduleForm'];

        $module = CourseModule::create([
            'course_id' => $course->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'position' => $data['position'] ?: ($course->modules()->max('position') + 1),
            'status' => 'published',
            'is_available' => true,
        ]);

        $this->moduleForm = ['title' => '', 'description' => '', 'position' => null];
        $this->lessonForm['course_module_id'] = $module->id;
        $this->examForm['course_module_id'] = $module->id;
        Notification::make()->title('Módulo criado')->success()->send();
    }

    public function createLesson(): void
    {
        $course = $this->editableCourse();
        $data = $this->validate([
            'lessonForm.course_module_id' => ['required', 'exists:course_modules,id'],
            'lessonForm.title' => ['required', 'string', 'max:255'],
            'lessonForm.description' => ['nullable', 'string'],
            'lessonForm.content_type' => ['required', 'in:youtube,vimeo,mp4,pdf,docx,pptx,external_link'],
            'lessonForm.content_url' => ['nullable', 'string', 'max:1000'],
            'lessonForm.duration_minutes' => ['nullable', 'integer', 'min:0'],
            'lessonForm.position' => ['nullable', 'integer', 'min:1'],
            'lessonForm.is_required' => ['boolean'],
            'lessonForm.is_available' => ['boolean'],
        ])['lessonForm'];

        $module = CourseModule::where('course_id', $course->id)->findOrFail($data['course_module_id']);

        Lesson::create([
            ...$data,
            'course_module_id' => $module->id,
            'position' => $data['position'] ?: ($module->lessons()->max('position') + 1),
            'duration_minutes' => $data['duration_minutes'] ?: 0,
            'is_required' => (bool) $data['is_required'],
            'is_available' => (bool) $data['is_available'],
        ]);

        $this->lessonForm = [
            'course_module_id' => $module->id,
            'title' => '',
            'description' => '',
            'content_type' => 'youtube',
            'content_url' => '',
            'duration_minutes' => 0,
            'position' => null,
            'is_required' => true,
            'is_available' => true,
        ];
        Notification::make()->title('Aula criada')->success()->send();
    }

    public function uploadMaterial(): void
    {
        $course = $this->editableCourse();
        $data = $this->validate([
            'materialForm.lesson_id' => ['required', 'exists:lessons,id'],
            'materialForm.title' => ['required', 'string', 'max:255'],
            'materialFile' => ['required', 'file', 'max:20480', 'mimes:pdf,doc,docx,ppt,pptx,zip,jpg,jpeg,png,mp4'],
        ]);

        $lesson = Lesson::whereHas('module', fn ($query) => $query->where('course_id', $course->id))->findOrFail($data['materialForm']['lesson_id']);
        $path = $this->materialFile->store("courses/{$course->id}/materials", 'public');

        LessonMaterial::create([
            'lesson_id' => $lesson->id,
            'title' => $data['materialForm']['title'],
            'file_path' => $path,
            'mime_type' => $this->materialFile->getMimeType(),
            'size_bytes' => $this->materialFile->getSize(),
        ]);

        $this->materialForm = ['lesson_id' => $lesson->id, 'title' => ''];
        $this->materialFile = null;
        Notification::make()->title('Material enviado')->success()->send();
    }

    public function createExam(): void
    {
        $course = $this->editableCourse();
        $data = $this->validate([
            'examForm.course_module_id' => ['nullable', 'exists:course_modules,id'],
            'examForm.title' => ['required', 'string', 'max:255'],
            'examForm.description' => ['nullable', 'string'],
            'examForm.minimum_grade' => ['required', 'numeric', 'between:0,10'],
            'examForm.time_limit_minutes' => ['nullable', 'integer', 'min:1'],
            'examForm.max_attempts' => ['required', 'integer', 'min:1'],
            'examForm.correction_type' => ['required', 'in:automatic,manual'],
            'examForm.is_active' => ['boolean'],
        ])['examForm'];

        if ($data['course_module_id']) {
            CourseModule::where('course_id', $course->id)->findOrFail($data['course_module_id']);
        }

        $exam = Exam::create([
            ...$data,
            'course_id' => $course->id,
            'is_active' => (bool) $data['is_active'],
        ]);

        $this->examForm = [
            'course_module_id' => $data['course_module_id'],
            'title' => '',
            'description' => '',
            'minimum_grade' => 7,
            'time_limit_minutes' => null,
            'max_attempts' => 1,
            'correction_type' => 'automatic',
            'is_active' => true,
        ];
        $this->questionForm['exam_id'] = $exam->id;
        Notification::make()->title('Prova criada')->success()->send();
    }

    public function createQuestion(): void
    {
        $course = $this->editableCourse();
        $data = $this->validate([
            'questionForm.exam_id' => ['required', 'exists:exams,id'],
            'questionForm.type' => ['required', 'in:multiple_choice,true_false,essay'],
            'questionForm.statement' => ['required', 'string'],
            'questionForm.weight' => ['required', 'numeric', 'min:0.1'],
            'questionForm.option_a' => ['nullable', 'string', 'max:1000'],
            'questionForm.option_b' => ['nullable', 'string', 'max:1000'],
            'questionForm.option_c' => ['nullable', 'string', 'max:1000'],
            'questionForm.option_d' => ['nullable', 'string', 'max:1000'],
            'questionForm.correct_option' => ['nullable', 'in:A,B,C,D'],
        ])['questionForm'];

        $exam = Exam::where('course_id', $course->id)->findOrFail($data['exam_id']);

        DB::transaction(function () use ($data, $exam) {
            $question = Question::create([
                'exam_id' => $exam->id,
                'type' => $data['type'],
                'statement' => $data['statement'],
                'weight' => $data['weight'],
                'is_reusable' => true,
            ]);

            if ($data['type'] !== 'essay') {
                collect(['A', 'B', 'C', 'D'])
                    ->map(fn ($label) => ['label' => $label, 'text' => $data['option_'.strtolower($label)] ?? null])
                    ->filter(fn ($option) => filled($option['text']))
                    ->each(fn ($option) => QuestionOption::create([
                        'question_id' => $question->id,
                        'label' => $option['label'],
                        'text' => $option['text'],
                        'is_correct' => $option['label'] === ($data['correct_option'] ?? 'A'),
                    ]));
            }
        });

        $this->questionForm = [
            'exam_id' => $exam->id,
            'type' => 'multiple_choice',
            'statement' => '',
            'weight' => 1,
            'option_a' => '',
            'option_b' => '',
            'option_c' => '',
            'option_d' => '',
            'correct_option' => 'A',
        ];
        Notification::make()->title('Questão criada')->success()->send();
    }

    private function editableCourse(): Course
    {
        $course = $this->selectedCourse();
        abort_unless($course && in_array($course->status, ['draft', 'changes_requested'], true), 422, 'Conteúdo editável apenas em rascunho ou ajustes solicitados.');

        return $course;
    }
}
