<?php

namespace App\Services;

use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CourseReviewService
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly TeacherNotificationService $teacherNotifications,
        private readonly ActivityLogger $logger,
    ) {
    }

    public function submit(Course $course, User $teacher): Course
    {
        abort_unless($course->teacher_id === $teacher->id || $teacher->hasRole('administrador'), 403);
        abort_unless(in_array($course->status, ['draft', 'changes_requested'], true), 422, 'Curso não pode ser enviado neste status.');

        $missing = $this->missingRequirements($course);

        if ($missing) {
            throw ValidationException::withMessages([
                'course' => 'Antes de enviar, complete: '.implode(', ', $missing).'.',
            ]);
        }

        return DB::transaction(function () use ($course, $teacher) {
            $course->update([
                'status' => 'pending_review',
                'submitted_for_review_at' => now(),
                'reviewed_at' => null,
                'reviewed_by' => null,
                'review_notes' => null,
                'is_featured' => false,
            ]);

            User::role('administrador')->get()->each(fn (User $admin) => $this->notifications->notify(
                $admin,
                "course_review_{$course->id}",
                'Curso aguardando revisão',
                "O professor {$teacher->name} enviou o curso {$course->name} para revisão.",
                '/gestao/revisão-cursos',
            ));

            $this->logger->log('course.submitted_for_review', $course, ['teacher_id' => $teacher->id], request());

            return $course->refresh();
        });
    }

    public function approve(Course $course, User $admin, ?string $notes = null): Course
    {
        abort_unless($admin->hasRole('administrador'), 403);
        abort_unless($course->status === 'pending_review', 422, 'Apenas cursos em revisão podem ser aprovados.');

        return DB::transaction(function () use ($course, $admin, $notes) {
            $course->update([
                'status' => 'published',
                'reviewed_at' => now(),
                'reviewed_by' => $admin->id,
                'review_notes' => $notes,
            ]);

            $this->notifications->notify(
                $course->teacher,
                "course_approved_{$course->id}",
                'Curso aprovado',
                "O curso {$course->name} foi aprovado e publicado no catálogo.",
                '/professor/cursos',
                false,
            );
            $this->teacherNotifications->courseApproved($course);
            $this->logger->log('course.approved', $course, ['admin_id' => $admin->id, 'notes' => $notes], request());

            return $course->refresh();
        });
    }

    public function requestChanges(Course $course, User $admin, string $notes): Course
    {
        abort_unless($admin->hasRole('administrador'), 403);
        abort_unless($course->status === 'pending_review', 422, 'Apenas cursos em revisão podem ser devolvidos.');

        return DB::transaction(function () use ($course, $admin, $notes) {
            $course->update([
                'status' => 'changes_requested',
                'reviewed_at' => now(),
                'reviewed_by' => $admin->id,
                'review_notes' => $notes,
            ]);

            $this->notifications->notify(
                $course->teacher,
                "course_changes_requested_{$course->id}",
                'Ajustes solicitados no curso',
                "O curso {$course->name} precisa de ajustes: {$notes}",
                '/professor/autoria-cursos',
                false,
            );
            $this->teacherNotifications->courseChangesRequested($course, $notes);
            $this->logger->log('course.changes_requested', $course, ['admin_id' => $admin->id, 'notes' => $notes], request());

            return $course->refresh();
        });
    }

    public function missingRequirements(Course $course): array
    {
        $missing = [];

        if (! $course->category_id) {
            $missing[] = 'categoria';
        }
        if (! trim((string) $course->name)) {
            $missing[] = 'nome';
        }
        if (! trim((string) $course->short_description)) {
            $missing[] = 'descrição curta';
        }
        if ((int) $course->workload_hours <= 0) {
            $missing[] = 'carga horaria';
        }
        if ((float) $course->minimum_grade <= 0) {
            $missing[] = 'nota minima';
        }
        if ((int) $course->minimum_progress_percent <= 0) {
            $missing[] = 'conclusao minima';
        }
        if (! $course->modules()->exists()) {
            $missing[] = 'ao menos um módulo';
        }
        if (! $course->lessons()->exists()) {
            $missing[] = 'ao menos uma aula';
        }
        if (! $course->exams()->whereHas('questions')->exists()) {
            $missing[] = 'prova com questões';
        }

        return $missing;
    }
}
