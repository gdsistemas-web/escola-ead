<?php

namespace App\Filament\Resources\Certificates\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CertificateForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('course_id')
                    ->relationship('course', 'name')
                    ->required(),
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                TextInput::make('certificate_template_id')
                    ->numeric(),
                TextInput::make('code')
                    ->required(),
                TextInput::make('student_name')
                    ->required(),
                TextInput::make('course_name')
                    ->required(),
                TextInput::make('workload_hours')
                    ->required()
                    ->numeric()
                    ->default(0),
                DatePicker::make('completed_at')
                    ->required(),
                DateTimePicker::make('issued_at')
                    ->required(),
                TextInput::make('pdf_path'),
                TextInput::make('verification_hash')
                    ->disabled(),
                Select::make('status')
                    ->options([
                        'valid' => 'Válido',
                        'revoked' => 'Revogado',
                    ])
                    ->default('valid')
                    ->required(),
                DateTimePicker::make('revoked_at'),
                TextInput::make('revoked_reason')
                    ->maxLength(500),
            ]);
    }
}
