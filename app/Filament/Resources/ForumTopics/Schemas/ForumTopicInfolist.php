<?php

namespace App\Filament\Resources\ForumTopics\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ForumTopicInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Resumo do tópico')
                    ->description('Discussão acadêmica aberta no fórum.')
                    ->schema([
                        TextEntry::make('status')->label('Situação')->badge(),
                        TextEntry::make('type')->label('Tipo')->badge(),
                        TextEntry::make('title')->label('Título')->weight('bold'),
                        TextEntry::make('category.name')->label('Categoria'),
                        TextEntry::make('author.name')->label('Autor'),
                        IconEntry::make('is_pinned')->label('Fixado')->boolean(),
                        IconEntry::make('is_closed')->label('Encerrado')->boolean(),
                    ])
                    ->columns(3),
                Section::make('Conteúdo')
                    ->schema([
                        TextEntry::make('body')->label('Conteúdo')->html()->columnSpanFull(),
                        TextEntry::make('acceptedReply.body')->label('Melhor resposta')->placeholder('-')->columnSpanFull(),
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
