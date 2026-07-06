<?php

namespace App\Filament\Resources\Settings\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Configuração')
                    ->description('Use JSON válido no campo valor. As configurações de e-mail são aplicadas automaticamente no envio.')
                    ->schema([
                        TextInput::make('key')
                            ->label('Chave')
                            ->required()
                            ->helperText('Ex.: mail.smtp, mail.template, institution.name'),
                        Select::make('group')
                            ->label('Grupo')
                            ->required()
                            ->options([
                                'institution' => 'Instituição',
                                'mail' => 'E-mail e SMTP',
                                'template' => 'Templates',
                                'general' => 'Geral',
                            ])
                            ->default('general')
                            ->searchable(),
                        Textarea::make('value')
                            ->label('Valor JSON')
                            ->formatStateUsing(fn ($state): string => json_encode($state ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
                            ->dehydrateStateUsing(fn (?string $state): array => json_decode($state ?: '{}', true) ?: [])
                            ->rows(14)
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
