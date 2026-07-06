<?php

namespace App\Filament\Resources\Exams\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ExamInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Resumo da avaliação')
                    ->description('Prova ou atividade avaliativa vinculada ao curso.')
                    ->schema([
                        IconEntry::make('is_active')
                            ->label('Ativa')
                            ->boolean(),
                        TextEntry::make('title')
                            ->label('Título')
                            ->weight('bold'),
                        TextEntry::make('course.name')
                            ->label('Curso'),
                        TextEntry::make('module.title')
                            ->label('Módulo')
                            ->placeholder('-'),
                        TextEntry::make('correction_type')
                            ->label('Correção')
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'automatic' => 'Automática',
                                'manual' => 'Manual',
                                default => (string) $state,
                            }),
                    ])
                    ->columns(3),

                Section::make('Regras')
                    ->schema([
                        TextEntry::make('minimum_grade')
                            ->label('Nota mínima')
                            ->numeric(),
                        TextEntry::make('time_limit_minutes')
                            ->label('Tempo limite')
                            ->suffix(' min')
                            ->numeric()
                            ->placeholder('-'),
                        TextEntry::make('max_attempts')
                            ->label('Tentativas')
                            ->numeric(),
                        TextEntry::make('opens_at')
                            ->label('Abertura')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('-'),
                        TextEntry::make('closes_at')
                            ->label('Encerramento')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('-'),
                    ])
                    ->columns(3),

                Section::make('Descrição')
                    ->schema([
                        TextEntry::make('description')
                            ->label('Descrição')
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),

                Section::make('Auditoria')
                    ->schema([
                        TextEntry::make('created_at')->label('Criado em')->dateTime('d/m/Y H:i')->placeholder('-'),
                        TextEntry::make('updated_at')->label('Atualizado em')->dateTime('d/m/Y H:i')->placeholder('-'),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }
}
