<?php

namespace App\Filament\Resources\CertificateTemplates\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CertificateTemplateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('logo_path'),
                TextInput::make('background_path'),
                Textarea::make('signatures')
                    ->columnSpanFull(),
                Textarea::make('body_html')
                    ->required()
                    ->columnSpanFull(),
                Toggle::make('is_default')
                    ->required(),
            ]);
    }
}
