<?php

namespace App\Filament\Resources\ForumTopics\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ForumTopicForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Tópico')
                    ->schema([
                        Select::make('forum_category_id')
                            ->label('Categoria do fórum')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('user_id')
                            ->label('Autor')
                            ->relationship('author', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('title')
                            ->label('Título')
                            ->required()
                            ->columnSpanFull(),
                        RichEditor::make('body')
                            ->label('Conteúdo')
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Moderação e avaliação')
                    ->schema([
                        Select::make('status')
                            ->label('Situação')
                            ->options([
                                'open' => 'Aberto',
                                'closed' => 'Encerrado',
                                'resolved' => 'Resolvido',
                                'pinned' => 'Fixado',
                                'hidden' => 'Oculto',
                            ])
                            ->default('open')
                            ->required(),
                        Select::make('type')
                            ->label('Tipo')
                            ->options([
                                'discussion' => 'Debate',
                                'question' => 'Dúvida',
                                'announcement' => 'Comunicado',
                                'assessment' => 'Avaliativo',
                            ])
                            ->default('discussion')
                            ->required(),
                        Toggle::make('is_pinned')
                            ->label('Fixado'),
                        Toggle::make('is_closed')
                            ->label('Encerrado'),
                        Toggle::make('is_assessment')
                            ->label('Avaliativo'),
                        Toggle::make('requires_reply')
                            ->label('Resposta obrigatória'),
                        TextInput::make('assessment_points')
                            ->label('Valor em pontos')
                            ->numeric(),
                    ])
                    ->columns(3),
            ]);
    }
}
