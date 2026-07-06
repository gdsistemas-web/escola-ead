<?php

namespace App\Filament\Resources\LessonMaterials\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class LessonMaterialForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('lesson_id')
                    ->relationship('lesson', 'title')
                    ->required(),
                TextInput::make('title')
                    ->required(),
                TextInput::make('file_path')
                    ->required(),
                TextInput::make('mime_type'),
                TextInput::make('size_bytes')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('downloads_count')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
