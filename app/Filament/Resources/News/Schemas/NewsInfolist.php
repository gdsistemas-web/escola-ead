<?php

namespace App\Filament\Resources\News\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class NewsInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Resumo da notícia')
                    ->description('Conteúdo publicado no portal institucional.')
                    ->schema([
                        TextEntry::make('title')->label('Título')->weight('bold'),
                        TextEntry::make('slug')->label('Slug')->copyable(),
                        TextEntry::make('author.name')->label('Autor')->placeholder('-'),
                        TextEntry::make('published_at')->label('Publicado em')->dateTime('d/m/Y H:i')->placeholder('-'),
                    ])
                    ->columns(2),
                Section::make('Conteúdo')
                    ->schema([
                        TextEntry::make('excerpt')->label('Resumo')->placeholder('-')->columnSpanFull(),
                        TextEntry::make('body')->label('Texto')->columnSpanFull(),
                        ImageEntry::make('cover_image_path')->label('Imagem de capa')->placeholder('-'),
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
