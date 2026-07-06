<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Resumo do usuário')
                    ->description('Dados principais de acesso à plataforma.')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Nome')
                            ->weight('bold'),
                        TextEntry::make('email')
                            ->label('E-mail')
                            ->copyable(),
                        TextEntry::make('roles.name')
                            ->label('Perfis')
                            ->badge()
                            ->separator(', ')
                            ->placeholder('-'),
                        TextEntry::make('email_verified_at')
                            ->label('E-mail verificado em')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('-'),
                    ])
                    ->columns(2),

                Section::make('Perfil complementar')
                    ->schema([
                        TextEntry::make('profile.document')
                            ->label('Documento')
                            ->placeholder('-'),
                        TextEntry::make('profile.phone')
                            ->label('Telefone')
                            ->placeholder('-'),
                        TextEntry::make('profile.city')
                            ->label('Cidade')
                            ->placeholder('-'),
                        TextEntry::make('profile.state')
                            ->label('UF')
                            ->placeholder('-'),
                        TextEntry::make('profile.lgpd_consent_at')
                            ->label('Consentimento LGPD')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('-'),
                        TextEntry::make('profile.terms_accepted_at')
                            ->label('Termos aceitos em')
                            ->dateTime('d/m/Y H:i')
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
