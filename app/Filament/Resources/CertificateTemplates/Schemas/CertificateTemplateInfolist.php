<?php

namespace App\Filament\Resources\CertificateTemplates\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CertificateTemplateInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Resumo do modelo')
                    ->description('Modelo usado na emissão e apresentação dos certificados.')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Nome')
                            ->weight('bold'),
                        IconEntry::make('is_default')
                            ->label('Modelo padrão')
                            ->boolean(),
                    ])
                    ->columns(2),

                Section::make('Identidade visual')
                    ->schema([
                        TextEntry::make('logo_path')
                            ->label('Logo')
                            ->placeholder('-'),
                        TextEntry::make('background_path')
                            ->label('Fundo')
                            ->placeholder('-'),
                        TextEntry::make('signatures')
                            ->label('Assinaturas')
                            ->formatStateUsing(fn ($state): string => is_array($state)
                                ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                                : (string) $state)
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Conteúdo do certificado')
                    ->schema([
                        TextEntry::make('body_html')
                            ->label('Texto/HTML')
                            ->columnSpanFull(),
                    ]),

                Section::make('Auditoria')
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Criado em')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('-'),
                        TextEntry::make('updated_at')
                            ->label('Atualizado em')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('-'),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }
}
