<?php

namespace App\Providers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        CreateAction::configureUsing(fn (CreateAction $action) => $action
            ->label('Novo')
            ->modalSubmitActionLabel('Salvar'));

        ViewAction::configureUsing(fn (ViewAction $action) => $action
            ->label('Ver'));

        EditAction::configureUsing(fn (EditAction $action) => $action
            ->label('Editar'));

        DeleteAction::configureUsing(fn (DeleteAction $action) => $action
            ->label('Excluir')
            ->modalHeading('Excluir registro')
            ->modalDescription('Tem certeza de que deseja excluir este registro? Esta ação não pode ser desfeita.')
            ->modalSubmitActionLabel('Excluir'));

        DeleteBulkAction::configureUsing(fn (DeleteBulkAction $action) => $action
            ->label('Excluir selecionados')
            ->modalHeading('Excluir registros selecionados')
            ->modalDescription('Tem certeza de que deseja excluir os registros selecionados? Esta ação não pode ser desfeita.')
            ->modalSubmitActionLabel('Excluir selecionados'));
    }
}
