<?php

namespace App\Filament\Resources\Courses\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CoursesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Curso')
                    ->description(fn ($record) => $record->short_description)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label('Categoria')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('teacher.name')
                    ->label('Professor')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('workload_hours')
                    ->label('Carga')
                    ->suffix('h')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('minimum_grade')
                    ->label('Nota')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('minimum_progress_percent')
                    ->label('Conclusão')
                    ->suffix('%')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Situação')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Rascunho',
                        'pending_review' => 'Em revisão',
                        'changes_requested' => 'Ajustes solicitados',
                        'published' => 'Publicado',
                        'closed' => 'Encerrado',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'published' => 'success',
                        'pending_review' => 'info',
                        'changes_requested' => 'warning',
                        'closed' => 'gray',
                        default => 'warning',
                    }),
                IconColumn::make('is_featured')
                    ->label('Destaque')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Situação')
                    ->options([
                        'draft' => 'Rascunho',
                        'pending_review' => 'Em revisão',
                        'changes_requested' => 'Ajustes solicitados',
                        'published' => 'Publicado',
                        'closed' => 'Encerrado',
                    ]),
                SelectFilter::make('category_id')
                    ->label('Categoria')
                    ->relationship('category', 'name'),
            ])
            ->recordActions([
                ViewAction::make()->label('Ver'),
                EditAction::make()->label('Editar'),
                Action::make('approve_course')
                    ->label('Aprovar')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'pending_review')
                    ->form([
                        Textarea::make('notes')
                            ->label('Observações')
                            ->rows(3),
                    ])
                    ->action(fn ($record, array $data) => app(\App\Services\CourseReviewService::class)->approve($record, auth()->user(), $data['notes'] ?? null)),
                Action::make('request_changes')
                    ->label('Devolver')
                    ->icon('heroicon-m-arrow-uturn-left')
                    ->color('warning')
                    ->visible(fn ($record) => $record->status === 'pending_review')
                    ->form([
                        Textarea::make('notes')
                            ->label('O que precisa ajustar?')
                            ->required()
                            ->rows(4),
                    ])
                    ->action(fn ($record, array $data) => app(\App\Services\CourseReviewService::class)->requestChanges($record, auth()->user(), $data['notes'])),
            ])
            ->toolbarActions([
                BulkAction::make('export_selected')
                    ->label('Exportar selecionados')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Exportar cursos selecionados')
                    ->modalDescription('A exportação será preparada considerando apenas os cursos marcados.')
                    ->modalSubmitActionLabel('Exportar selecionados')
                    ->deselectRecordsAfterCompletion()
                    ->action(fn ($records) => Notification::make()
                        ->success()
                        ->title($records->count() . ' curso(s) selecionado(s)')
                        ->body('Exportação enviada para processamento.')
                        ->send()),
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
