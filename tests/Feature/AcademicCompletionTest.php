<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CertificateTemplate;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\Lesson;
use App\Models\LessonMaterial;
use App\Models\LessonProgress;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\User;
use App\Services\CertificateService;
use App\Services\ProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AcademicCompletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_certificate_is_blocked_until_completion_requirements_are_met(): void
    {
        [$student, $course, $lesson] = $this->makeCourseWithEnrollment();
        CertificateTemplate::create([
            'name' => 'Padrao',
            'body_html' => 'Certificado',
            'is_default' => true,
        ]);

        try {
            app(CertificateService::class)->issue($course, $student);
            $this->fail('Certificate should not be issued before completion requirements.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('certificates', 0);
        }

        app(ProgressService::class)->saveLessonProgress($lesson, $student, 60 * 30, 100);

        $certificate = app(CertificateService::class)->issue($course, $student);

        $this->assertSame($course->id, $certificate->course_id);
        $this->assertDatabaseHas('enrollments', [
            'course_id' => $course->id,
            'user_id' => $student->id,
            'status' => 'completed',
            'progress_percent' => 100,
        ]);
    }

    public function test_exam_submission_respects_backend_grading_and_attempt_limit(): void
    {
        [$student, $course] = $this->makeCourseWithEnrollment();
        $exam = Exam::create([
            'course_id' => $course->id,
            'title' => 'Prova final',
            'minimum_grade' => 7,
            'max_attempts' => 1,
            'is_active' => true,
        ]);
        $question = Question::create([
            'exam_id' => $exam->id,
            'type' => 'multiple_choice',
            'statement' => 'Qual alternativa esta correta?',
            'weight' => 2,
        ]);
        $correct = QuestionOption::create([
            'question_id' => $question->id,
            'label' => 'A',
            'text' => 'Correta',
            'is_correct' => true,
        ]);

        Sanctum::actingAs($student);

        $this->postJson("/api/exams/{$exam->id}/submit", [
            'answers' => [
                ['question_id' => $question->id, 'question_option_id' => $correct->id],
            ],
        ])
            ->assertCreated()
            ->assertJsonPath('grade', 10)
            ->assertJsonPath('status', 'graded');

        $this->postJson("/api/exams/{$exam->id}/submit", [
            'answers' => [
                ['question_id' => $question->id, 'question_option_id' => $correct->id],
            ],
        ])->assertStatus(422);
    }

    public function test_student_enrollment_show_includes_learning_room_payload(): void
    {
        [$student, $course, $lesson] = $this->makeCourseWithEnrollment();
        $enrollment = Enrollment::where('course_id', $course->id)->where('user_id', $student->id)->firstOrFail();
        LessonMaterial::create([
            'lesson_id' => $lesson->id,
            'title' => 'Apostila',
            'file_path' => 'materials/apostila.pdf',
            'mime_type' => 'application/pdf',
        ]);
        LessonProgress::create([
            'lesson_id' => $lesson->id,
            'user_id' => $student->id,
            'watched_seconds' => 600,
            'progress_percent' => 50,
            'is_completed' => false,
            'last_accessed_at' => now(),
        ]);

        Sanctum::actingAs($student);

        $this->getJson("/api/enrollments/{$enrollment->id}")
            ->assertOk()
            ->assertJsonPath('course.modules.0.lessons.0.materials.0.title', 'Apostila')
            ->assertJsonPath('course.modules.0.lessons.0.progress.0.progress_percent', 50)
            ->assertJsonStructure(['academic_status', 'pending_items']);
    }

    public function test_exam_show_does_not_expose_answer_key_to_student(): void
    {
        [$student, $course] = $this->makeCourseWithEnrollment();
        $exam = Exam::create([
            'course_id' => $course->id,
            'title' => 'Prova final',
            'is_active' => true,
        ]);
        $question = Question::create([
            'exam_id' => $exam->id,
            'type' => 'multiple_choice',
            'statement' => 'Qual alternativa esta correta?',
            'correct_answer' => 'A',
        ]);
        QuestionOption::create([
            'question_id' => $question->id,
            'label' => 'A',
            'text' => 'Correta',
            'is_correct' => true,
        ]);

        Sanctum::actingAs($student);

        $this->getJson("/api/exams/{$exam->id}")
            ->assertOk()
            ->assertJsonMissing(['is_correct' => true])
            ->assertJsonMissing(['correct_answer' => 'A']);
    }

    private function makeCourseWithEnrollment(): array
    {
        $teacher = User::factory()->create();
        $student = User::factory()->create();
        $category = Category::create(['name' => 'Legislativo', 'slug' => 'legislativo']);
        $course = Course::create([
            'category_id' => $category->id,
            'teacher_id' => $teacher->id,
            'name' => 'Processo Legislativo',
            'slug' => 'processo-legislativo',
            'minimum_grade' => 7,
            'minimum_progress_percent' => 75,
            'workload_hours' => 20,
            'status' => 'published',
        ]);
        $module = CourseModule::create([
            'course_id' => $course->id,
            'title' => 'Fundamentos',
            'position' => 1,
            'is_available' => true,
            'status' => 'published',
        ]);
        $lesson = Lesson::create([
            'course_module_id' => $module->id,
            'title' => 'Aula 1',
            'content_type' => 'youtube',
            'duration_minutes' => 30,
            'position' => 1,
            'is_required' => true,
            'is_available' => true,
        ]);
        Enrollment::create([
            'course_id' => $course->id,
            'user_id' => $student->id,
            'status' => 'active',
            'source' => 'manual',
        ]);

        return [$student, $course, $lesson];
    }
}
