<?php

namespace App\Filament\Resources\Enrollments\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EnrollmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('course.name')
                    ->label('Curso')
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label('Aluno')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Situação')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Ativa',
                        'completed' => 'Concluída',
                        'cancelled' => 'Cancelada',
                        default => $state,
                    })
                    ->searchable(),
                TextColumn::make('source')
                    ->label('Origem')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'manual' => 'Manual',
                        'automatic' => 'Automática',
                        default => $state ?: '-',
                    })
                    ->searchable(),
                TextColumn::make('final_grade')
                    ->label('Nota final')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('progress_percent')
                    ->label('Progresso')
                    ->suffix('%')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('enrolled_at')
                    ->label('Matrícula em')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('completed_at')
                    ->label('Conclusão em')
                    ->dateTime()
                    ->sortable(),
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
                SelectFilter::make('status')
                    ->label('Situação')
                    ->options([
                        'active' => 'Ativa',
                        'completed' => 'Concluída',
                        'cancelled' => 'Cancelada',
                    ]),
                SelectFilter::make('source')
                    ->label('Origem')
                    ->options([
                        'manual' => 'Manual',
                        'automatic' => 'Automática',
                    ]),
            ])
            ->recordActions([
                ViewAction::make()->label('Ver'),
                EditAction::make()->label('Editar'),
            ])
            ->toolbarActions([
                BulkAction::make('export_selected')
                    ->label('Exportar selecionados')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Exportar matrículas selecionadas')
                    ->modalDescription('A exportação será preparada considerando apenas as matrículas marcadas.')
                    ->modalSubmitActionLabel('Exportar selecionados')
                    ->deselectRecordsAfterCompletion()
                    ->action(fn ($records) => Notification::make()
                        ->success()
                        ->title($records->count() . ' matrícula(s) selecionada(s)')
                        ->body('Exportação enviada para processamento.')
                        ->send()),
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
