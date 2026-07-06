<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\ForumReputation;
use App\Models\ForumTopic;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ManagementExportController extends Controller
{
    public function pdf(Request $request, string $type)
    {
        abort_unless($request->user()?->hasAnyRole(['administrador', 'professor']), 403);

        $report = $this->report($type);

        return Pdf::loadView('pdf.management-report', $report)
            ->setPaper('a4', 'landscape')
            ->download($report['filename'].'.pdf');
    }

    public function csv(Request $request, string $type): StreamedResponse
    {
        abort_unless($request->user()?->hasAnyRole(['administrador', 'professor']), 403);

        $report = $this->report($type);

        return response()->streamDownload(function () use ($report): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, $report['headers'], ';');

            foreach ($report['rows'] as $row) {
                fputcsv($handle, $row, ';');
            }

            fclose($handle);
        }, $report['filename'].'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array{title: string, filename: string, headers: array<int, string>, rows: array<int, array<int, mixed>>}
     */
    private function report(string $type): array
    {
        return match ($type) {
            'courses' => [
                'title' => 'Relatório de cursos',
                'filename' => 'cursos',
                'headers' => ['Curso', 'Categoria', 'Professor', 'Carga horária', 'Situação'],
                'rows' => Course::query()
                    ->with(['category', 'teacher'])
                    ->orderBy('name')
                    ->get()
                    ->map(fn (Course $course): array => [
                        $course->name,
                        $course->category?->name,
                        $course->teacher?->name,
                        $course->workload_hours.'h',
                        $this->courseStatus($course->status),
                    ])
                    ->all(),
            ],
            'enrollments' => [
                'title' => 'Relatório de matrículas',
                'filename' => 'matriculas',
                'headers' => ['Aluno', 'Curso', 'Situação', 'Origem', 'Progresso', 'Nota final'],
                'rows' => Enrollment::query()
                    ->with(['user', 'course'])
                    ->latest('enrolled_at')
                    ->get()
                    ->map(fn (Enrollment $enrollment): array => [
                        $enrollment->user?->name,
                        $enrollment->course?->name,
                        $this->enrollmentStatus($enrollment->status),
                        $this->enrollmentSource($enrollment->source),
                        $enrollment->progress_percent.'%',
                        $enrollment->final_grade,
                    ])
                    ->all(),
            ],
            'forum-topics' => [
                'title' => 'Relatório de tópicos do fórum',
                'filename' => 'forum-topicos',
                'headers' => ['Tópico', 'Curso', 'Categoria', 'Autor', 'Situação', 'Respostas', 'Visualizações'],
                'rows' => ForumTopic::query()
                    ->with(['course', 'category', 'author'])
                    ->withCount('replies')
                    ->latest()
                    ->get()
                    ->map(fn (ForumTopic $topic): array => [
                        $topic->title,
                        $topic->course?->name,
                        $topic->category?->name,
                        $topic->author?->name,
                        $this->forumStatus($topic->status),
                        $topic->replies_count,
                        $topic->views_count,
                    ])
                    ->all(),
            ],
            'forum-engagement' => [
                'title' => 'Ranking de engajamento do fórum',
                'filename' => 'forum-engajamento',
                'headers' => ['Participante', 'E-mail', 'Pontuação'],
                'rows' => ForumReputation::query()
                    ->selectRaw('user_id, sum(points) as reputation')
                    ->with('user')
                    ->groupBy('user_id')
                    ->orderByDesc('reputation')
                    ->get()
                    ->map(fn (ForumReputation $row): array => [
                        $row->user?->name,
                        $row->user?->email,
                        $row->reputation,
                    ])
                    ->all(),
            ],
            default => [
                'title' => 'Relatório de usuários',
                'filename' => 'usuarios',
                'headers' => ['Nome', 'E-mail', 'Perfis', 'Criado em'],
                'rows' => User::query()
                    ->with('roles')
                    ->orderBy('name')
                    ->get()
                    ->map(fn (User $user): array => [
                        $user->name,
                        $user->email,
                        $user->roles->pluck('name')->implode(', '),
                        $user->created_at?->format('d/m/Y H:i'),
                    ])
                    ->all(),
            ],
        };
    }

    private function courseStatus(?string $status): string
    {
        return match ($status) {
            'published' => 'Publicado',
            'pending_review' => 'Em revisão',
            'changes_requested' => 'Ajustes solicitados',
            'closed' => 'Encerrado',
            'draft' => 'Rascunho',
            default => (string) $status,
        };
    }

    private function enrollmentStatus(?string $status): string
    {
        return match ($status) {
            'active' => 'Ativa',
            'completed' => 'Concluída',
            'cancelled' => 'Cancelada',
            default => (string) $status,
        };
    }

    private function enrollmentSource(?string $source): string
    {
        return match ($source) {
            'manual' => 'Manual',
            'automatic' => 'Automática',
            default => (string) $source,
        };
    }

    private function forumStatus(?string $status): string
    {
        return match ($status) {
            'open' => 'Aberto',
            'closed' => 'Encerrado',
            'resolved' => 'Resolvido',
            'pinned' => 'Fixado',
            'hidden' => 'Oculto',
            default => (string) $status,
        };
    }
}
