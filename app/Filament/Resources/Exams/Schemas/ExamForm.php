<?php

namespace App\Filament\Resources\Exams\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ExamForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Avaliação')
                    ->schema([
                        Select::make('course_id')
                            ->label('Curso')
                            ->relationship('course', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('course_module_id')
                            ->label('Módulo')
                            ->relationship('module', 'title')
                            ->searchable()
                            ->preload(),
                        TextInput::make('title')
                            ->label('Título')
                            ->required(),
                        Textarea::make('description')
                            ->label('Descrição')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Regras')
                    ->schema([
                        TextInput::make('minimum_grade')
                            ->label('Nota mínima')
                            ->required()
                            ->numeric()
                            ->default(7),
                        TextInput::make('time_limit_minutes')
                            ->label('Tempo limite')
                            ->suffix(' min')
                            ->numeric(),
                        TextInput::make('max_attempts')
                            ->label('Tentativas máximas')
                            ->required()
                            ->numeric()
                            ->default(1),
                        Select::make('correction_type')
                            ->label('Correção')
                            ->options([
                                'automatic' => 'Automática',
                                'manual' => 'Manual',
                            ])
                            ->required()
                            ->default('automatic'),
                        DateTimePicker::make('opens_at')
                            ->label('Liberação'),
                        DateTimePicker::make('closes_at')
                            ->label('Encerramento'),
                        Toggle::make('is_active')
                            ->label('Ativa')
                            ->required(),
                    ])
                    ->columns(3),
            ]);
    }
}
