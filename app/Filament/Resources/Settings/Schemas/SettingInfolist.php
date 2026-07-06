<?php

namespace App\Filament\Resources\Settings\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SettingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Resumo da configuração')
                    ->description('Parâmetro administrativo usado pela plataforma.')
                    ->schema([
                        TextEntry::make('key')
                            ->label('Chave')
                            ->copyable()
                            ->weight('bold'),
                        TextEntry::make('group')
                            ->label('Grupo')
                            ->badge()
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'institution' => 'Instituição',
                                'mail' => 'E-mail e SMTP',
                                'template' => 'Templates',
                                'general' => 'Geral',
                                default => (string) $state,
                            }),
                    ])
                    ->columns(2),

                Section::make('Valor configurado')
                    ->description('Conteúdo salvo em JSON para edição pelo administrador.')
                    ->schema([
                        TextEntry::make('value')
                            ->label('Valor')
                            ->formatStateUsing(fn ($state): string => is_array($state)
                                ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                                : (string) $state)
                            ->copyable()
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
