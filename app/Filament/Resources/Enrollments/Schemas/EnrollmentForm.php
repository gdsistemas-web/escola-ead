<?php

namespace App\Filament\Resources\Enrollments\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EnrollmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Matrícula')
                    ->schema([
                        Select::make('course_id')
                            ->label('Curso')
                            ->relationship('course', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('user_id')
                            ->label('Aluno')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('status')
                            ->label('Situação')
                            ->options([
                                'active' => 'Ativa',
                                'completed' => 'Concluída',
                                'cancelled' => 'Cancelada',
                                'waiting' => 'Lista de espera',
                            ])
                            ->required()
                            ->default('active'),
                        Select::make('source')
                            ->label('Origem')
                            ->options([
                                'automatic' => 'Automática',
                                'manual' => 'Manual',
                            ])
                            ->required()
                            ->default('automatic'),
                        TextInput::make('final_grade')
                            ->label('Nota final')
                            ->numeric(),
                        TextInput::make('progress_percent')
                            ->label('Progresso')
                            ->suffix('%')
                            ->required()
                            ->numeric()
                            ->default(0),
                        DateTimePicker::make('enrolled_at')
                            ->label('Matrículado em')
                            ->required(),
                        DateTimePicker::make('completed_at')
                            ->label('Concluído em'),
                    ])
                    ->columns(2),
            ]);
    }
}
