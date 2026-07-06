<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Exam;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\User;
use App\Services\CourseReviewService;
use App\Services\EnrollmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;
use Illuminate\Validation\ValidationException;

class CourseReviewWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_creates_draft_and_cannot_publish_directly(): void
    {
        $teacher = User::factory()->create();
        Role::findOrCreate('professor');
        $teacher->assignRole('professor');
        $category = Category::create(['name' => 'Legislativo', 'slug' => 'legislativo']);

        Sanctum::actingAs($teacher);

        $response = $this->postJson('/api/courses', [
            'category_id' => $category->id,
            'name' => 'Curso em autoria',
            'short_description' => 'Curso criado pelo professor.',
            'workload_hours' => 10,
            'minimum_grade' => 7,
            'minimum_progress_percent' => 75,
            'status' => 'published',
        ])->assertCreated();

        $this->assertSame('draft', $response->json('status'));
        $this->assertSame($teacher->id, $response->json('teacher_id'));
    }

    public function test_enrollment_is_blocked_until_course_is_published(): void
    {
        [, , $student, $course] = $this->makeWorkflowCourse(false);

        $this->expectException(HttpException::class);

        app(EnrollmentService::class)->enroll($course, $student);
    }

    public function test_course_review_status_flow(): void
    {
        [$admin, $teacher, $student, $course] = $this->makeWorkflowCourse(false);
        $review = app(CourseReviewService::class);

        $review->submit($course, $teacher);
        $this->assertSame('pending_review', $course->refresh()->status);

        $review->requestChanges($course, $admin, 'Adicionar descrição mais clara.');
        $this->assertSame('changes_requested', $course->refresh()->status);
        $this->assertSame('Adicionar descrição mais clara.', $course->review_notes);

        $review->submit($course, $teacher);
        $review->approve($course, $admin, 'Aprovado.');

        $this->assertSame('published', $course->refresh()->status);
        $this->assertNotNull($course->reviewed_at);

        $enrollment = app(EnrollmentService::class)->enroll($course, $student);
        $this->assertSame('active', $enrollment->status);
    }

    public function test_review_requires_modules_lessons_and_exam_questions(): void
    {
        $teacher = User::factory()->create();
        Role::findOrCreate('professor');
        $teacher->assignRole('professor');
        $category = Category::create(['name' => 'Legislativo', 'slug' => 'legislativo']);
        $course = Course::create([
            'category_id' => $category->id,
            'teacher_id' => $teacher->id,
            'name' => 'Curso incompleto',
            'slug' => 'curso-incompleto',
            'short_description' => 'Sem conteúdo ainda.',
            'minimum_grade' => 7,
            'minimum_progress_percent' => 75,
            'workload_hours' => 20,
            'status' => 'draft',
        ]);

        $this->expectException(ValidationException::class);

        app(CourseReviewService::class)->submit($course, $teacher);
    }

    private function makeWorkflowCourse(bool $published = true): array
    {
        $admin = User::factory()->create();
        $teacher = User::factory()->create();
        $student = User::factory()->create();
        Role::findOrCreate('administrador');
        Role::findOrCreate('professor');
        $admin->assignRole('administrador');
        $teacher->assignRole('professor');

        $category = Category::create(['name' => 'Legislativo', 'slug' => 'legislativo']);
        $course = Course::create([
            'category_id' => $category->id,
            'teacher_id' => $teacher->id,
            'name' => 'Processo Legislativo',
            'slug' => 'processo-legislativo',
            'short_description' => 'Fundamentos legislativos para servidores.',
            'minimum_grade' => 7,
            'minimum_progress_percent' => 75,
            'workload_hours' => 20,
            'status' => $published ? 'published' : 'draft',
        ]);
        $module = CourseModule::create([
            'course_id' => $course->id,
            'title' => 'Fundamentos',
            'position' => 1,
            'status' => 'published',
            'is_available' => true,
        ]);
        Lesson::create([
            'course_module_id' => $module->id,
            'title' => 'Aula 1',
            'content_type' => 'youtube',
            'content_url' => 'https://www.youtube.com/embed/example',
            'duration_minutes' => 20,
            'position' => 1,
            'is_required' => true,
            'is_available' => true,
        ]);
        $exam = Exam::create([
            'course_id' => $course->id,
            'course_module_id' => $module->id,
            'title' => 'Prova final',
            'minimum_grade' => 7,
            'max_attempts' => 1,
            'is_active' => true,
        ]);
        $question = Question::create([
            'exam_id' => $exam->id,
            'type' => 'multiple_choice',
            'statement' => 'Qual alternativa esta correta?',
            'weight' => 1,
        ]);
        QuestionOption::create([
            'question_id' => $question->id,
            'label' => 'A',
            'text' => 'Correta',
            'is_correct' => true,
        ]);

        return [$admin, $teacher, $student, $course];
    }
}
