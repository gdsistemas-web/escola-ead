<?php

namespace App\Filament\Resources\ForumTopics\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ForumTopicsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Tópico')
                    ->description(fn ($record) => str($record->body)->stripTags()->limit(80))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label('Categoria')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('author.name')
                    ->label('Autor')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Situação')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'open' => 'Aberto',
                        'closed' => 'Encerrado',
                        'resolved' => 'Resolvido',
                        'pinned' => 'Fixado',
                        'hidden' => 'Oculto',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'resolved' => 'success',
                        'pinned' => 'info',
                        'hidden', 'closed' => 'gray',
                        default => 'warning',
                    }),
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge(),
                TextColumn::make('replies_count')
                    ->label('Respostas')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('views_count')
                    ->label('Visualizações')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_pinned')
                    ->label('Fixado')
                    ->boolean(),
                IconColumn::make('is_closed')
                    ->label('Encerrado')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Situação')
                    ->options([
                        'open' => 'Aberto',
                        'closed' => 'Encerrado',
                        'resolved' => 'Resolvido',
                        'pinned' => 'Fixado',
                        'hidden' => 'Oculto',
                    ]),
                SelectFilter::make('forum_category_id')
                    ->label('Categoria')
                    ->relationship('category', 'name'),
            ])
            ->recordActions([
                ViewAction::make()->label('Ver'),
                EditAction::make()->label('Editar'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
