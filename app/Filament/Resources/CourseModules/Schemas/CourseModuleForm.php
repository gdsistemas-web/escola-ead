<?php

namespace App\Filament\Resources\CourseModules\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CourseModuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('course_id')
                    ->label('Curso')
                    ->relationship('course', 'name')
                    ->required(),
                TextInput::make('title')
                    ->label('Título')
                    ->required(),
                Textarea::make('description')
                    ->label('Descrição')
                    ->columnSpanFull(),
                TextInput::make('position')
                    ->label('Ordem')
                    ->required()
                    ->numeric()
                    ->default(1),
                Toggle::make('is_available')
                    ->label('Disponível')
                    ->required(),
                TextInput::make('status')
                    ->label('Situação')
                    ->required()
                    ->default('draft'),
                DateTimePicker::make('available_from'),
            ]);
    }
}
