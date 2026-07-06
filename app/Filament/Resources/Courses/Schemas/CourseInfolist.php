<?php

namespace App\Filament\Resources\Courses\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CourseInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Resumo do curso')
                    ->description('Informações principais exibidas no catálogo e nos painéis acadêmicos.')
                    ->schema([
                        TextEntry::make('status')
                            ->label('Situação')
                            ->badge()
                            ->color(fn (?string $state): string => match ($state) {
                                'published' => 'success',
                                'pending_review' => 'warning',
                                'changes_requested' => 'danger',
                                'closed' => 'gray',
                                default => 'info',
                            })
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'draft' => 'Rascunho',
                                'pending_review' => 'Em revisão',
                                'changes_requested' => 'Ajustes solicitados',
                                'published' => 'Publicado',
                                'closed' => 'Encerrado',
                                default => (string) $state,
                            }),
                        TextEntry::make('name')
                            ->label('Nome')
                            ->weight('bold'),
                        TextEntry::make('category.name')
                            ->label('Categoria'),
                        TextEntry::make('teacher.name')
                            ->label('Professor'),
                        TextEntry::make('slug')
                            ->label('Slug')
                            ->copyable(),
                        IconEntry::make('is_featured')
                            ->label('Destaque')
                            ->boolean(),
                    ])
                    ->columns(3),

                Section::make('Conteúdo e descrição')
                    ->schema([
                        TextEntry::make('short_description')
                            ->label('Descrição curta')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        TextEntry::make('description')
                            ->label('Descrição completa')
                            ->placeholder('-')
                            ->columnSpanFull(),
                        ImageEntry::make('cover_image_path')
                            ->label('Imagem de capa')
                            ->placeholder('-'),
                        TextEntry::make('presentation_video_url')
                            ->label('Vídeo de apresentação')
                            ->placeholder('-')
                            ->url(fn (?string $state): ?string => $state)
                            ->openUrlInNewTab(),
                    ])
                    ->columns(2),

                Section::make('Regras acadêmicas')
                    ->schema([
                        TextEntry::make('workload_hours')
                            ->label('Carga horária')
                            ->suffix(' horas')
                            ->numeric(),
                        TextEntry::make('minimum_grade')
                            ->label('Nota mínima')
                            ->numeric(),
                        TextEntry::make('minimum_progress_percent')
                            ->label('Conclusão mínima')
                            ->suffix('%')
                            ->numeric(),
                        TextEntry::make('seat_limit')
                            ->label('Limite de vagas')
                            ->numeric()
                            ->placeholder('Sem limite'),
                        TextEntry::make('starts_at')
                            ->label('Início')
                            ->date('d/m/Y')
                            ->placeholder('-'),
                        TextEntry::make('ends_at')
                            ->label('Término')
                            ->date('d/m/Y')
                            ->placeholder('-'),
                    ])
                    ->columns(3),

                Section::make('Auditoria')
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Criado em')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('-'),
                        TextEntry::make('updated_at')
                            ->label('Atualizado em')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('-'),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }
}
