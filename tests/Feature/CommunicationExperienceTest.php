<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ChatRoom;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CommunicationExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_and_mark_notifications_as_read(): void
    {
        $user = User::factory()->create();
        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => 'course_update',
            'title' => 'Nova atividade',
            'body' => 'Você possui uma nova atividade.',
            'url' => '/aluno/cursos',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/notifications?unread=1')
            ->assertOk()
            ->assertJsonPath('data.0.id', $notification->id);

        $this->postJson("/api/notifications/{$notification->id}/read")
            ->assertOk()
            ->assertJsonPath('id', $notification->id);

        $this->getJson('/api/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('unread', 0);
    }

    public function test_academic_calendar_returns_course_and_exam_events_for_student(): void
    {
        [$teacher, $student, $course] = $this->makeCourse();
        Enrollment::create([
            'course_id' => $course->id,
            'user_id' => $student->id,
            'status' => 'active',
            'source' => 'manual',
        ]);
        Exam::create([
            'course_id' => $course->id,
            'title' => 'Prova final',
            'opens_at' => now()->addDay(),
            'closes_at' => now()->addDays(7),
            'is_active' => true,
        ]);

        Sanctum::actingAs($student);

        $this->getJson('/api/academic-calendar')
            ->assertOk()
            ->assertJsonCount(4, 'events');
    }

    public function test_course_chat_includes_enrolled_student_and_teacher(): void
    {
        [$teacher, $student, $course] = $this->makeCourse();
        Enrollment::create([
            'course_id' => $course->id,
            'user_id' => $student->id,
            'status' => 'active',
            'source' => 'manual',
        ]);

        Sanctum::actingAs($teacher);

        $response = $this->postJson('/api/chats', [
            'course_id' => $course->id,
            'name' => 'Chat do curso',
            'type' => 'course',
        ])->assertCreated();

        $room = ChatRoom::findOrFail($response->json('id'));

        $this->assertTrue($room->participants()->where('user_id', $teacher->id)->exists());
        $this->assertTrue($room->participants()->where('user_id', $student->id)->exists());
    }

    private function makeCourse(): array
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
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addMonth(),
            'status' => 'published',
        ]);

        return [$teacher, $student, $course];
    }
}
