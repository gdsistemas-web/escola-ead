<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\ForumCategory;
use App\Models\ForumNotification;
use App\Models\ForumTopic;
use App\Models\Notification;
use App\Models\User;
use App\Services\RetentionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RetentionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_student_inactivity_alerts_are_created_once_per_milestone(): void
    {
        [$teacher, $student, $course] = $this->makeCourse();
        $enrollment = Enrollment::create([
            'course_id' => $course->id,
            'user_id' => $student->id,
            'status' => 'active',
            'source' => 'manual',
            'updated_at' => now()->subDays(16),
            'created_at' => now()->subDays(16),
        ]);

        $first = app(RetentionService::class)->runStudentInactivityAlerts();
        $second = app(RetentionService::class)->runStudentInactivityAlerts();

        $this->assertSame(1, $first['15']);
        $this->assertSame(0, $second['15']);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $student->id,
            'type' => "student_inactive_15_enrollment_{$enrollment->id}",
        ]);
        $this->assertSame(1, Notification::count());
    }

    public function test_forum_sla_alerts_notify_course_teacher_once(): void
    {
        [$teacher, $student, $course] = $this->makeCourse();
        $category = ForumCategory::create([
            'course_id' => $course->id,
            'name' => 'Duvidas',
            'type' => 'course',
        ]);
        $topic = ForumTopic::create([
            'forum_category_id' => $category->id,
            'course_id' => $course->id,
            'user_id' => $student->id,
            'title' => 'Preciso de ajuda',
            'body' => 'Duvida do aluno',
            'status' => 'open',
            'created_at' => now()->subHours(72),
            'updated_at' => now()->subHours(72),
        ]);

        $this->assertSame(1, app(RetentionService::class)->runForumSlaAlerts(48));
        $this->assertSame(0, app(RetentionService::class)->runForumSlaAlerts(48));
        $this->assertDatabaseHas('forum_notifications', [
            'user_id' => $teacher->id,
            'forum_topic_id' => $topic->id,
            'type' => "forum_sla_48_topic_{$topic->id}",
        ]);
        $this->assertSame(1, ForumNotification::count());
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
            'status' => 'published',
        ]);

        return [$teacher, $student, $course];
    }
}
