<?php

namespace App\Filament\Resources\Courses\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CourseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identificação')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),
                        TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->unique(ignoreRecord: true),
                        Select::make('category_id')
                            ->label('Categoria')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('teacher_id')
                            ->label('Professor responsável')
                            ->relationship('teacher', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Textarea::make('short_description')
                            ->label('Descrição curta')
                            ->rows(3)
                            ->columnSpanFull(),
                        RichEditor::make('description')
                            ->label('Descrição completa')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Publicação e regras LMS')
                    ->schema([
                        TextInput::make('workload_hours')
                            ->label('Carga horária')
                            ->required()
                            ->numeric()
                            ->default(0),
                        TextInput::make('minimum_grade')
                            ->label('Nota mínima')
                            ->required()
                            ->numeric()
                            ->default(7),
                        TextInput::make('minimum_progress_percent')
                            ->label('Conclusão mínima (%)')
                            ->required()
                            ->numeric()
                            ->default(75),
                        TextInput::make('seat_limit')
                            ->label('Limite de vagas')
                            ->numeric(),
                        Select::make('status')
                            ->label('Situação')
                            ->options([
                                'draft' => 'Rascunho',
                                'pending_review' => 'Em revisão',
                                'changes_requested' => 'Ajustes solicitados',
                                'published' => 'Publicado',
                                'closed' => 'Encerrado',
                            ])
                            ->required()
                            ->default('draft'),
                        Toggle::make('is_featured')
                            ->label('Destaque no portal')
                            ->required(),
                    ])
                    ->columns(3),
                Section::make('Mídia')
                    ->schema([
                        FileUpload::make('cover_image_path')
                            ->label('Imagem de capa')
                            ->image()
                            ->directory('courses/covers'),
                        TextInput::make('presentation_video_url')
                            ->label('Vídeo de apresentação')
                            ->url(),
                        DatePicker::make('starts_at')
                            ->label('Data de início'),
                        DatePicker::make('ends_at')
                            ->label('Data de término'),
                    ])
                    ->columns(2),
            ]);
    }
}
