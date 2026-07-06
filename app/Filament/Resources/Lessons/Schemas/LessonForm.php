<?php

namespace App\Filament\Resources\Lessons\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LessonForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Aula')
                    ->schema([
                        Select::make('course_module_id')
                            ->label('Módulo')
                            ->relationship('module', 'title')
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('title')
                            ->label('Título')
                            ->required(),
                        Textarea::make('description')
                            ->label('Descrição')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Conteúdo')
                    ->schema([
                        Select::make('content_type')
                            ->label('Tipo')
                            ->options([
                                'youtube' => 'YouTube',
                                'vimeo' => 'Vimeo',
                                'mp4' => 'MP4',
                                'pdf' => 'PDF',
                                'docx' => 'DOCX',
                                'pptx' => 'PPTX',
                                'external_link' => 'Link externo',
                                'scorm' => 'SCORM',
                            ])
                            ->required()
                            ->default('youtube'),
                        TextInput::make('content_url')
                            ->label('URL do conteúdo')
                            ->url(),
                        FileUpload::make('file_path')
                            ->label('Arquivo')
                            ->directory('lessons/content')
                            ->acceptedFileTypes(['application/pdf', 'video/mp4', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.openxmlformats-officedocument.presentationml.presentation']),
                        TextInput::make('duration_minutes')
                            ->label('Duração em minutos')
                            ->required()
                            ->numeric()
                            ->default(0),
                    ])
                    ->columns(2),
                Section::make('Disponibilidade')
                    ->schema([
                        TextInput::make('position')
                            ->label('Ordem')
                            ->required()
                            ->numeric()
                            ->default(1),
                        Toggle::make('is_required')
                            ->label('Obrigatória')
                            ->required(),
                        Toggle::make('is_available')
                            ->label('Disponível')
                            ->required(),
                    ])
                    ->columns(3),
            ]);
    }
}
