<?php

namespace App\Filament\Resources\Lessons\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LessonsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Aula')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('module.title')
                    ->label('Módulo')
                    ->searchable(),
                TextColumn::make('content_type')
                    ->label('Tipo')
                    ->badge()
                    ->searchable(),
                TextColumn::make('duration_minutes')
                    ->label('Duração')
                    ->suffix(' min')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('position')
                    ->label('Ordem')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_required')
                    ->label('Obrigatória')
                    ->boolean(),
                IconColumn::make('is_available')
                    ->label('Disponível')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('content_type')
                    ->label('Tipo')
                    ->options([
                        'youtube' => 'YouTube',
                        'vimeo' => 'Vimeo',
                        'mp4' => 'MP4',
                        'pdf' => 'PDF',
                        'docx' => 'DOCX',
                        'pptx' => 'PPTX',
                        'external_link' => 'Link externo',
                    ]),
            ])
            ->recordActions([
                ViewAction::make()->label('Ver'),
                EditAction::make()->label('Editar'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
