<?php

namespace App\Filament\Resources\Questions\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class QuestionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Resumo da questão')
                    ->description('Questão do banco avaliativo.')
                    ->schema([
                        TextEntry::make('type')->label('Tipo')->badge(),
                        TextEntry::make('difficulty')->label('Dificuldade')->badge(),
                        TextEntry::make('exam.title')->label('Avaliação')->placeholder('-'),
                        TextEntry::make('category.name')->label('Categoria')->placeholder('-'),
                        TextEntry::make('subject')->label('Assunto')->placeholder('-'),
                        TextEntry::make('weight')->label('Peso')->numeric(),
                        IconEntry::make('is_reusable')->label('Reutilizável')->boolean(),
                    ])
                    ->columns(3),
                Section::make('Enunciado e resposta')
                    ->schema([
                        TextEntry::make('statement')->label('Enunciado')->columnSpanFull(),
                        TextEntry::make('correct_answer')->label('Resposta correta')->placeholder('-')->columnSpanFull(),
                    ]),
                Section::make('Auditoria')
                    ->schema([
                        TextEntry::make('created_at')->label('Criado em')->dateTime('d/m/Y H:i')->placeholder('-'),
                        TextEntry::make('updated_at')->label('Atualizado em')->dateTime('d/m/Y H:i')->placeholder('-'),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }
}
