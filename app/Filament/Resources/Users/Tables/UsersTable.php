<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Usuário')
                    ->description(fn ($record) => $record->email)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable(),
                TextColumn::make('roles.name')
                    ->label('Perfil')
                    ->badge()
                    ->separator(', '),
                TextColumn::make('email_verified_at')
                    ->label('Verificado em')
                    ->dateTime()
                    ->sortable(),
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
                SelectFilter::make('roles')
                    ->label('Perfil')
                    ->relationship('roles', 'name')
                    ->multiple(),
            ])
            ->recordActions([
                ViewAction::make()->label('Analisar'),
                EditAction::make()->label('Editar'),
            ])
            ->toolbarActions([
                BulkAction::make('export_selected')
                    ->label('Exportar selecionados')
                    ->icon('heroicon-m-arrow-down-tray')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Exportar usuários selecionados')
                    ->modalDescription('A exportação será preparada considerando apenas os registros marcados.')
                    ->modalSubmitActionLabel('Exportar selecionados')
                    ->deselectRecordsAfterCompletion()
                    ->action(fn ($records) => Notification::make()
                        ->success()
                        ->title($records->count() . ' usuário(s) selecionado(s)')
                        ->body('Exportação enviada para processamento.')
                        ->send()),
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
