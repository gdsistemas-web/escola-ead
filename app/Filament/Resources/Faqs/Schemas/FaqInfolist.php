<?php

namespace App\Filament\Resources\Faqs\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FaqInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Resumo da pergunta')
                    ->description('Item exibido na área pública de dúvidas frequentes.')
                    ->schema([
                        IconEntry::make('is_active')->label('Ativa')->boolean(),
                        TextEntry::make('question')->label('Pergunta')->weight('bold'),
                        TextEntry::make('group')->label('Grupo')->badge(),
                        TextEntry::make('position')->label('Ordem')->numeric(),
                    ])
                    ->columns(4),
                Section::make('Resposta')
                    ->schema([
                        TextEntry::make('answer')->label('Resposta')->columnSpanFull(),
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
