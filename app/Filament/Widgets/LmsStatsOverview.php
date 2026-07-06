<?php

namespace App\Filament\Widgets;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LmsStatsOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Resumo do LMS';

    protected ?string $description = 'Indicadores principais da Escola do Parlamento de Itapevi.';

    protected function getStats(): array
    {
        return [
            Stat::make('Alunos', User::role('aluno')->count())
                ->description('Usuários com perfil de aluno')
                ->color('primary'),
            Stat::make('Professores', User::role('professor')->count())
                ->description('Educadores cadastrados')
                ->color('info'),
            Stat::make('Cursos publicados', Course::where('status', 'published')->count())
                ->description(Course::count().' cursos no total')
                ->color('success'),
            Stat::make('Matrículas ativas', Enrollment::where('status', 'active')->count())
                ->description('Inclui inscrições automáticas e manuais')
                ->color('warning'),
            Stat::make('Cursos em revisão', Course::where('status', 'pending_review')->count())
                ->description('Aguardando decisão do gestor')
                ->color('info'),
            Stat::make('Certificados', Certificate::count())
                ->description('Certificados emitidos')
                ->color('success'),
            Stat::make('Conversão em certificado', $this->certificateConversion().'%')
                ->description('Certificados sobre cursos concluídos')
                ->color('info'),
            Stat::make('Aulas', Lesson::count())
                ->description('Conteúdos cadastrados')
                ->color('primary'),
        ];
    }

    private function certificateConversion(): float
    {
        $completed = Enrollment::where('status', 'completed')->count();

        return $completed ? round((Certificate::count() / $completed) * 100, 1) : 0;
    }
}
