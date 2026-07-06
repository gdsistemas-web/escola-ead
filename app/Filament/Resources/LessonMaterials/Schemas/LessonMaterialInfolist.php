<?php

namespace App\Filament\Resources\LessonMaterials\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LessonMaterialInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Resumo do material')
                    ->description('Arquivo complementar disponibilizado ao aluno.')
                    ->schema([
                        TextEntry::make('title')
                            ->label('Título')
                            ->weight('bold'),
                        TextEntry::make('lesson.title')
                            ->label('Aula'),
                        TextEntry::make('mime_type')
                            ->label('Tipo')
                            ->badge()
                            ->placeholder('-'),
                        TextEntry::make('downloads_count')
                            ->label('Downloads')
                            ->numeric(),
                    ])
                    ->columns(4),

                Section::make('Arquivo')
                    ->schema([
                        TextEntry::make('file_path')
                            ->label('Caminho')
                            ->copyable()
                            ->columnSpanFull(),
                        TextEntry::make('size_bytes')
                            ->label('Tamanho')
                            ->formatStateUsing(fn ($state): string => $state ? number_format(((int) $state) / 1024, 1, ',', '.').' KB' : '0 KB'),
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
