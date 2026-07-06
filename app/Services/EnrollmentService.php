<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class EnrollmentService
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly TeacherNotificationService $teacherNotifications,
    )
    {
    }

    public function enroll(Course $course, User $user, string $source = 'automatic', array $applicationData = []): Enrollment
    {
        abort_unless($course->status === 'published', 422, 'Curso ainda não está liberado para matrículas.');

        $activeCount = $course->enrollments()->whereIn('status', ['active', 'completed'])->count();
        $status = $course->seat_limit && $activeCount >= $course->seat_limit ? 'waiting' : 'active';
        $protocol = 'EAD-'.now()->format('Y').'-'.Str::upper(Str::random(8));
        $profileData = Arr::only($applicationData, ['document', 'phone', 'birthdate', 'city', 'state']);

        if ($profileData) {
            $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                array_filter([
                    ...$profileData,
                    'lgpd_consent_at' => now(),
                    'terms_accepted_at' => now(),
                    'lgpd_consent_version' => '2026.1',
                    'privacy_policy_version' => '2026.1',
                ], fn ($value) => $value !== null && $value !== '')
            );
        }

        $existing = Enrollment::where('course_id', $course->id)->where('user_id', $user->id)->first();
        $application = array_filter([
            'protocol' => $existing?->application_data['protocol'] ?? $protocol,
            'education_level' => $applicationData['education_level'] ?? null,
            'occupation' => $applicationData['occupation'] ?? null,
            'institution' => $applicationData['institution'] ?? null,
            'motivation' => $applicationData['motivation'] ?? null,
            'accessibility_needs' => $applicationData['accessibility_needs'] ?? null,
            'submitted_at' => now()->toISOString(),
        ], fn ($value) => $value !== null && $value !== '');

        $enrollment = Enrollment::updateOrCreate(
            ['course_id' => $course->id, 'user_id' => $user->id],
            [
                'status' => $status,
                'source' => $source,
                'application_data' => $application,
                'terms_accepted_at' => ! empty($applicationData['accept_terms']) ? now() : $existing?->terms_accepted_at,
                'enrolled_at' => $existing?->enrolled_at ?? now(),
            ]
        );

        $this->notifications->notify(
            $user,
            "enrollment_{$enrollment->id}",
            'Matrícula recebida - EAD EPI',
            "Recebemos sua inscrição no curso {$course->name}. Protocolo: {$enrollment->application_data['protocol']}. Status: {$status}.",
            '/aluno/cursos',
            true,
            [
                'preheader' => 'Sua inscrição foi registrada na Escola do Parlamento de Itapevi.',
                'headline' => 'Matrícula recebida - EAD EPI',
                'kicker' => 'Inscrição em curso',
                'protocol' => $enrollment->application_data['protocol'] ?? null,
                'action_label' => 'Acompanhar matrícula',
                'summary' => [
                    'Curso' => $course->name,
                    'Aluno' => $user->name,
                    'Status' => $status === 'waiting' ? 'Lista de espera' : 'Matrícula ativa',
                    'Carga horária' => "{$course->workload_hours} horas",
                ],
                'cards' => [
                    ['title' => 'Acompanhe seu e-mail', 'body' => 'Avisos, atualizações e certificados serão enviados por aqui.'],
                    ['title' => 'Acesse o ambiente', 'body' => 'Use a área do aluno para ver aulas, materiais e avaliações.'],
                    ['title' => 'Guarde o protocolo', 'body' => 'Ele ajuda na conferência da sua inscrição.'],
                ],
            ],
        );

        $this->teacherNotifications->newEnrollment($enrollment);

        return $enrollment;
    }
}
