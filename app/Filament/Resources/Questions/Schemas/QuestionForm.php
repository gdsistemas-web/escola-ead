<?php

namespace App\Filament\Resources\Questions\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class QuestionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Questão')
                    ->schema([
                        Select::make('category_id')
                            ->label('Categoria')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('exam_id')
                            ->label('Avaliação')
                            ->relationship('exam', 'title')
                            ->searchable()
                            ->preload(),
                        Select::make('type')
                            ->label('Tipo')
                            ->options([
                                'multiple_choice' => 'Múltipla escolha',
                                'true_false' => 'Verdadeiro/Falso',
                                'essay' => 'Discursiva',
                            ])
                            ->required()
                            ->default('multiple_choice'),
                        Select::make('difficulty')
                            ->label('Dificuldade')
                            ->options([
                                'easy' => 'Fácil',
                                'medium' => 'Média',
                                'hard' => 'Difícil',
                            ])
                            ->required()
                            ->default('medium'),
                        TextInput::make('subject')
                            ->label('Assunto'),
                        TextInput::make('weight')
                            ->label('Peso')
                            ->required()
                            ->numeric()
                            ->default(1),
                        Textarea::make('statement')
                            ->label('Enunciado')
                            ->required()
                            ->columnSpanFull(),
                        Textarea::make('correct_answer')
                            ->label('Resposta correta / gabarito')
                            ->columnSpanFull(),
                        Toggle::make('is_reusable')
                            ->label('Reutilizável')
                            ->required(),
                    ])
                    ->columns(2),
            ]);
    }
}
