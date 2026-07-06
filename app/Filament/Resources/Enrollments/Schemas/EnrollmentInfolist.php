<?php

namespace App\Filament\Resources\Enrollments\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EnrollmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Resumo da matrícula')
                    ->description('Situação acadêmica e dados principais da inscrição.')
                    ->schema([
                        TextEntry::make('status')
                            ->label('Situação')
                            ->badge()
                            ->color(fn (?string $state): string => match ($state) {
                                'active' => 'success',
                                'completed' => 'info',
                                'waiting' => 'warning',
                                'cancelled' => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'active' => 'Ativa',
                                'completed' => 'Concluída',
                                'cancelled' => 'Cancelada',
                                'waiting' => 'Lista de espera',
                                default => (string) $state,
                            }),
                        TextEntry::make('application_data.protocol')
                            ->label('Protocolo')
                            ->placeholder('-')
                            ->copyable()
                            ->weight('bold'),
                        TextEntry::make('course.name')
                            ->label('Curso')
                            ->weight('bold'),
                        TextEntry::make('user.name')
                            ->label('Aluno')
                            ->weight('bold'),
                    ])
                    ->columns(2),

                Section::make('Desempenho')
                    ->schema([
                        TextEntry::make('progress_percent')
                            ->label('Progresso')
                            ->suffix('%')
                            ->numeric(),
                        TextEntry::make('final_grade')
                            ->label('Nota final')
                            ->numeric()
                            ->placeholder('-'),
                        TextEntry::make('source')
                            ->label('Origem')
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'manual' => 'Manual',
                                'automatic' => 'Automática',
                                default => (string) $state,
                            }),
                        TextEntry::make('enrolled_at')
                            ->label('Matrícula em')
                            ->dateTime('d/m/Y H:i'),
                        TextEntry::make('completed_at')
                            ->label('Conclusão em')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('-'),
                        TextEntry::make('terms_accepted_at')
                            ->label('Aceite LGPD')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('-'),
                    ])
                    ->columns(3),

                Section::make('Formulário de inscrição')
                    ->description('Dados preenchidos pelo aluno no ato da matrícula.')
                    ->schema([
                        TextEntry::make('application_data.education_level')
                            ->label('Escolaridade')
                            ->placeholder('-'),
                        TextEntry::make('application_data.occupation')
                            ->label('Ocupação')
                            ->placeholder('-'),
                        TextEntry::make('application_data.institution')
                            ->label('Instituição/órgão')
                            ->placeholder('-'),
                        TextEntry::make('application_data.motivation')
                            ->label('Motivação')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('application_data.accessibility_needs')
                            ->label('Acessibilidade/observações')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                Section::make('Auditoria')
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Criado em')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('-'),
                        TextEntry::make('updated_at')
                            ->label('Atualizado em')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('-'),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }
}
