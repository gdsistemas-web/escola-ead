<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Certificate;
use App\Models\ChatRoom;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PermissionMatrixTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_cannot_create_or_view_draft_courses(): void
    {
        [$admin, $teacher, $student, $category] = $this->usersAndCategory();
        $draft = $this->course($category, $teacher, ['status' => 'draft']);

        Sanctum::actingAs($student);

        $this->postJson('/api/courses', [
            'category_id' => $category->id,
            'name' => 'Curso indevido',
            'workload_hours' => 4,
        ])->assertForbidden();

        $this->getJson("/api/courses/{$draft->id}")->assertForbidden();

        $this->getJson('/api/courses')
            ->assertOk()
            ->assertJsonMissing(['id' => $draft->id]);
    }

    public function test_teacher_cannot_delete_courses_or_revoke_certificates(): void
    {
        [$admin, $teacher, $student, $category] = $this->usersAndCategory();
        $course = $this->course($category, $teacher, ['status' => 'published']);
        $certificate = $this->certificate($course, $student);

        Sanctum::actingAs($teacher);

        $this->deleteJson("/api/courses/{$course->id}")->assertForbidden();
        $this->postJson("/api/certificates/{$certificate->id}/revoke", [
            'reason' => 'Teste de permissão',
        ])->assertForbidden();

        Sanctum::actingAs($admin);

        $this->deleteJson("/api/courses/{$course->id}")->assertNoContent();
    }

    public function test_non_participant_cannot_read_chat_room(): void
    {
        [, $teacher, $student, $category] = $this->usersAndCategory();
        $outsider = User::factory()->create();
        Role::findOrCreate('aluno');
        $outsider->assignRole('aluno');

        $course = $this->course($category, $teacher, ['status' => 'published']);
        $room = ChatRoom::create([
            'course_id' => $course->id,
            'name' => 'Aluno/professor',
            'type' => 'direct',
        ]);
        $room->participants()->create(['user_id' => $teacher->id]);
        $room->participants()->create(['user_id' => $student->id]);

        Sanctum::actingAs($outsider);

        $this->getJson("/api/chats/{$room->id}")->assertForbidden();

        Sanctum::actingAs($student);

        $this->getJson("/api/chats/{$room->id}")->assertOk();
    }

    public function test_management_exports_are_restricted_to_staff(): void
    {
        [$admin, , $student] = $this->usersAndCategory();

        $this->actingAs($student)
            ->get('/gestao/export/courses/csv')
            ->assertForbidden();

        $response = $this->actingAs($admin)
            ->get('/gestao/export/courses/csv')
            ->assertOk();

        $csv = $response->streamedContent();

        $this->assertStringContainsString('Carga horária', $csv);
        $this->assertStringContainsString('Situação', $csv);
    }

    private function usersAndCategory(): array
    {
        Role::findOrCreate('administrador');
        Role::findOrCreate('professor');
        Role::findOrCreate('aluno');

        $admin = User::factory()->create();
        $teacher = User::factory()->create();
        $student = User::factory()->create();

        $admin->assignRole('administrador');
        $teacher->assignRole('professor');
        $student->assignRole('aluno');

        $category = Category::create(['name' => 'Legislativo', 'slug' => 'legislativo']);

        return [$admin, $teacher, $student, $category];
    }

    private function course(Category $category, User $teacher, array $overrides = []): Course
    {
        return Course::create(array_merge([
            'category_id' => $category->id,
            'teacher_id' => $teacher->id,
            'name' => 'Processo Legislativo',
            'slug' => 'processo-legislativo-'.strtolower(fake()->bothify('??##')),
            'short_description' => 'Fundamentos legislativos.',
            'minimum_grade' => 7,
            'minimum_progress_percent' => 75,
            'workload_hours' => 20,
            'status' => 'draft',
        ], $overrides));
    }

    private function certificate(Course $course, User $student): Certificate
    {
        return Certificate::create([
            'course_id' => $course->id,
            'user_id' => $student->id,
            'code' => 'CERT'.fake()->unique()->numerify('####'),
            'verification_hash' => hash('sha256', fake()->uuid()),
            'student_name' => $student->name,
            'course_name' => $course->name,
            'workload_hours' => $course->workload_hours,
            'completed_at' => now()->toDateString(),
            'issued_at' => now(),
            'status' => 'valid',
        ]);
    }
}
