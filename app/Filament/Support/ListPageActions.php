<?php

namespace App\Filament\Support;

use Filament\Actions\Action;

class ListPageActions
{
    /**
     * @return array<Action>
     */
    public static function make(string $contextLabel, string $exportType): array
    {
        return [
            Action::make('pdf')
                ->label('PDF')
                ->icon('heroicon-m-document-arrow-down')
                ->color('gray')
                ->outlined()
                ->requiresConfirmation()
                ->modalHeading("Gerar PDF de {$contextLabel}")
                ->modalDescription('O relatório será gerado em PDF com os registros desta área.')
                ->modalSubmitActionLabel('Gerar PDF')
                ->action(fn () => redirect()->route('gestao.export.pdf', ['type' => $exportType])),

            Action::make('export_all')
                ->label('Exportar geral')
                ->icon('heroicon-m-arrow-down-tray')
                ->color('gray')
                ->outlined()
                ->requiresConfirmation()
                ->modalHeading("Exportar {$contextLabel}")
                ->modalDescription('A exportação geral baixará um arquivo CSV com os registros desta área.')
                ->modalSubmitActionLabel('Exportar')
                ->action(fn () => redirect()->route('gestao.export.csv', ['type' => $exportType])),

            Action::make('print')
                ->label('Imprimir')
                ->icon('heroicon-m-printer')
                ->color('gray')
                ->outlined()
                ->extraAttributes([
                    'x-on:click' => 'setTimeout(() => window.print(), 120)',
                ]),
        ];
    }
}
