<?php

namespace App\Filament\Resources\Lessons\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LessonInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Resumo da aula')
                    ->description('Conteúdo disponível dentro de um módulo do curso.')
                    ->schema([
                        TextEntry::make('title')
                            ->label('Título')
                            ->weight('bold'),
                        TextEntry::make('module.title')
                            ->label('Módulo')
                            ->placeholder('-'),
                        TextEntry::make('content_type')
                            ->label('Tipo')
                            ->badge(),
                        TextEntry::make('duration_minutes')
                            ->label('Duração')
                            ->suffix(' min')
                            ->numeric(),
                        TextEntry::make('position')
                            ->label('Ordem')
                            ->numeric(),
                        IconEntry::make('is_required')
                            ->label('Obrigatória')
                            ->boolean(),
                        IconEntry::make('is_available')
                            ->label('Disponível')
                            ->boolean(),
                    ])
                    ->columns(3),

                Section::make('Conteúdo')
                    ->schema([
                        TextEntry::make('description')
                            ->label('Descrição')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('content_url')
                            ->label('URL do conteúdo')
                            ->placeholder('-')
                            ->url(fn (?string $state): ?string => $state)
                            ->openUrlInNewTab(),
                        TextEntry::make('file_path')
                            ->label('Arquivo')
                            ->placeholder('-'),
                    ])
                    ->columns(2),

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
