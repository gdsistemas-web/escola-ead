<?php

namespace App\Services;

use App\Models\ChatMessage;
use App\Models\ChatRoom;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\ForumReply;
use App\Models\ForumTopic;
use App\Models\User;

class TeacherNotificationService
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    public function newEnrollment(Enrollment $enrollment): void
    {
        $enrollment->loadMissing('course.teacher', 'user');
        $course = $enrollment->course;
        $teacher = $course?->teacher;

        if (! $course || ! $teacher) {
            return;
        }

        $this->notifications->notify(
            $teacher,
            "teacher_enrollment_{$enrollment->id}",
            'Novo aluno matriculado - EAD EPI',
            "{$enrollment->user?->name} se matriculou no curso {$course->name}. Acompanhe a turma pelo painel do professor.",
            '/professor/cursos',
            true,
            [
                'preheader' => 'Um novo aluno entrou em um curso sob sua responsabilidade.',
                'headline' => 'Novo aluno matriculado',
                'kicker' => 'Rotina do professor',
                'protocol' => $enrollment->application_data['protocol'] ?? null,
                'action_label' => 'Abrir turma',
                'summary' => [
                    'Curso' => $course->name,
                    'Aluno' => $enrollment->user?->name,
                    'Situação' => $enrollment->status === 'waiting' ? 'Lista de espera' : 'Matrícula ativa',
                    'Origem' => $enrollment->source,
                ],
                'cards' => [
                    ['title' => 'Acompanhe a turma', 'body' => 'Veja progresso, certificados e alunos em risco.'],
                    ['title' => 'Abra o chat', 'body' => 'Responda dúvidas individuais pelo painel.'],
                    ['title' => 'Use o fórum', 'body' => 'Transforme dúvidas recorrentes em discussão acadêmica.'],
                ],
            ],
        );
    }

    public function studentChatMessage(ChatRoom $room, ChatMessage $message): void
    {
        $room->loadMissing('course.teacher');
        $message->loadMissing('user');
        $course = $room->course;
        $teacher = $course?->teacher;

        if (! $course || ! $teacher || $message->user_id === $teacher->id) {
            return;
        }

        $this->notifications->notify(
            $teacher,
            "teacher_chat_message_{$message->id}",
            'Nova mensagem de aluno - EAD EPI',
            "{$message->user?->name} enviou uma mensagem no chat do curso {$course->name}.",
            '/professor/comunicacao',
            true,
            [
                'preheader' => 'Há uma nova mensagem aguardando resposta no painel do professor.',
                'headline' => 'Nova mensagem de aluno',
                'kicker' => 'Atendimento pedagógico',
                'protocol' => 'CHAT-'.$message->id,
                'action_label' => 'Responder no chat',
                'summary' => [
                    'Curso' => $course->name,
                    'Aluno' => $message->user?->name,
                    'Enviado em' => $message->sent_at?->format('d/m/Y H:i'),
                ],
            ],
        );
    }

    public function forumTopicCreated(ForumTopic $topic): void
    {
        $topic->loadMissing('course.teacher', 'category.course.teacher', 'author');
        $course = $this->topicCourse($topic);
        $teacher = $course?->teacher;

        if (! $course || ! $teacher || $topic->user_id === $teacher->id) {
            return;
        }

        $this->notifications->notify(
            $teacher,
            "teacher_forum_topic_{$topic->id}",
            'Novo tópico no fórum - EAD EPI',
            "{$topic->author?->name} criou um tópico no fórum do curso {$course->name}: {$topic->title}.",
            '/professor/forum',
            true,
            [
                'preheader' => 'Um aluno abriu uma nova discussão no fórum acadêmico.',
                'headline' => 'Novo tópico no fórum',
                'kicker' => 'Mediação acadêmica',
                'protocol' => 'FORUM-'.$topic->id,
                'action_label' => 'Abrir fórum',
                'summary' => [
                    'Curso' => $course->name,
                    'Aluno' => $topic->author?->name,
                    'Tópico' => $topic->title,
                    'Status' => $topic->status ?? 'open',
                ],
            ],
        );
    }

    public function forumReplyCreated(ForumTopic $topic, ForumReply $reply): void
    {
        $topic->loadMissing('course.teacher', 'category.course.teacher');
        $reply->loadMissing('author');
        $course = $this->topicCourse($topic);
        $teacher = $course?->teacher;

        if (! $course || ! $teacher || $reply->user_id === $teacher->id) {
            return;
        }

        $this->notifications->notify(
            $teacher,
            "teacher_forum_reply_{$reply->id}",
            'Nova resposta no fórum - EAD EPI',
            "{$reply->author?->name} respondeu ao tópico {$topic->title} no curso {$course->name}.",
            '/professor/forum',
            true,
            [
                'preheader' => 'Há uma nova resposta de aluno no fórum acadêmico.',
                'headline' => 'Nova resposta no fórum',
                'kicker' => 'Mediação acadêmica',
                'protocol' => 'RESPOSTA-'.$reply->id,
                'action_label' => 'Ver discussão',
                'summary' => [
                    'Curso' => $course->name,
                    'Aluno' => $reply->author?->name,
                    'Tópico' => $topic->title,
                    'Respondido em' => $reply->created_at?->format('d/m/Y H:i'),
                ],
            ],
        );
    }

    public function courseApproved(Course $course): void
    {
        $course->loadMissing('teacher');
        $teacher = $course->teacher;

        if (! $teacher) {
            return;
        }

        $this->notifications->notify(
            $teacher,
            "teacher_course_approved_mail_{$course->id}",
            'Curso aprovado - EAD EPI',
            "O curso {$course->name} foi aprovado e publicado no catálogo.",
            '/professor/cursos',
            true,
            [
                'preheader' => 'Seu curso foi aprovado pela gestão e já está publicado.',
                'headline' => 'Curso aprovado',
                'kicker' => 'Revisão pedagógica',
                'protocol' => 'CURSO-'.$course->id,
                'action_label' => 'Ver curso',
                'summary' => [
                    'Curso' => $course->name,
                    'Status' => 'Publicado',
                    'Carga horária' => "{$course->workload_hours} horas",
                ],
            ],
        );
    }

    public function certificateIssued(Certificate $certificate): void
    {
        $certificate->loadMissing('course.teacher', 'user');
        $course = $certificate->course;
        $teacher = $course?->teacher;

        if (! $course || ! $teacher) {
            return;
        }

        $this->notifications->notify(
            $teacher,
            "teacher_certificate_{$certificate->id}",
            'Aluno certificado - EAD EPI',
            "{$certificate->user?->name} emitiu certificado no curso {$course->name}.",
            '/professor/cursos',
            true,
            [
                'preheader' => 'Um aluno concluiu os requisitos e emitiu certificado.',
                'headline' => 'Aluno certificado',
                'kicker' => 'Conclusão de curso',
                'protocol' => $certificate->code,
                'action_label' => 'Acompanhar turma',
                'summary' => [
                    'Curso' => $course->name,
                    'Aluno' => $certificate->user?->name,
                    'Carga horária' => "{$certificate->workload_hours} horas",
                    'Emitido em' => $certificate->issued_at?->format('d/m/Y H:i'),
                ],
            ],
        );
    }

    public function courseChangesRequested(Course $course, string $notes): void
    {
        $course->loadMissing('teacher');
        $teacher = $course->teacher;

        if (! $teacher) {
            return;
        }

        $this->notifications->notify(
            $teacher,
            "teacher_course_changes_mail_{$course->id}",
            'Ajustes solicitados no curso - EAD EPI',
            "O curso {$course->name} precisa de ajustes antes da publicação.",
            '/professor/autoria-cursos',
            true,
            [
                'preheader' => 'A gestão solicitou ajustes em um curso enviado para revisão.',
                'headline' => 'Ajustes solicitados no curso',
                'kicker' => 'Revisão pedagógica',
                'protocol' => 'CURSO-'.$course->id,
                'action_label' => 'Revisar curso',
                'summary' => [
                    'Curso' => $course->name,
                    'Status' => 'Ajustes solicitados',
                    'Observação' => $notes,
                ],
            ],
        );
    }

    private function topicCourse(ForumTopic $topic): ?Course
    {
        return $topic->course ?? $topic->category?->course;
    }
}
