<?php

namespace App\Filament\Resources\CourseModules\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CourseModuleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Resumo do módulo')
                    ->description('Unidade de organização das aulas do curso.')
                    ->schema([
                        TextEntry::make('status')
                            ->label('Situação')
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'draft' => 'Rascunho',
                                'published' => 'Publicado',
                                'locked' => 'Bloqueado',
                                default => (string) $state,
                            }),
                        TextEntry::make('title')
                            ->label('Título')
                            ->weight('bold'),
                        TextEntry::make('course.name')
                            ->label('Curso'),
                        TextEntry::make('position')
                            ->label('Ordem')
                            ->numeric(),
                        IconEntry::make('is_available')
                            ->label('Disponível')
                            ->boolean(),
                        TextEntry::make('available_from')
                            ->label('Disponível a partir de')
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
