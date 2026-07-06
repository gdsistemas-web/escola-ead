<?php

namespace App\Filament\Resources\Certificates\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CertificateInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Resumo do certificado')
                    ->description('Dados principais para conferência rápida antes de abrir ou validar o PDF.')
                    ->schema([
                        TextEntry::make('status')
                            ->label('Situação')
                            ->badge()
                            ->color(fn (?string $state): string => $state === 'valid' ? 'success' : 'danger')
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'valid' => 'Válido',
                                'revoked' => 'Revogado',
                                default => (string) $state,
                            }),
                        TextEntry::make('code')
                            ->label('Código')
                            ->copyable()
                            ->weight('bold'),
                        TextEntry::make('student_name')
                            ->label('Aluno')
                            ->weight('bold'),
                        TextEntry::make('course_name')
                            ->label('Curso')
                            ->weight('bold'),
                    ])
                    ->columns(2),

                Section::make('Dados acadêmicos')
                    ->schema([
                        TextEntry::make('course.name')
                            ->label('Curso vinculado'),
                        TextEntry::make('user.name')
                            ->label('Usuário vinculado'),
                        TextEntry::make('template.name')
                            ->label('Modelo')
                            ->placeholder('Modelo institucional'),
                        TextEntry::make('workload_hours')
                            ->label('Carga horária')
                            ->suffix(' horas')
                            ->numeric(),
                        TextEntry::make('completed_at')
                            ->label('Conclusão')
                            ->date('d/m/Y'),
                        TextEntry::make('issued_at')
                            ->label('Emissão')
                            ->dateTime('d/m/Y H:i'),
                    ])
                    ->columns(3),

                Section::make('Validação e PDF')
                    ->description('Use estes dados para demonstrar a validação pública do certificado.')
                    ->schema([
                        TextEntry::make('pdf_path')
                            ->label('Arquivo PDF')
                            ->placeholder('PDF ainda não gerado')
                            ->formatStateUsing(fn (?string $state): string => $state ? 'Abrir certificado em PDF' : 'PDF ainda não gerado')
                            ->url(fn ($record): ?string => $record->pdf_path ? '/storage/'.$record->pdf_path : null)
                            ->openUrlInNewTab()
                            ->badge()
                            ->color(fn ($state): string => $state ? 'success' : 'gray'),
                        TextEntry::make('verification_url')
                            ->label('Link público de validação')
                            ->state(fn ($record): string => "/certificado/{$record->code}")
                            ->url(fn ($record): string => "/certificado/{$record->code}")
                            ->openUrlInNewTab()
                            ->copyable()
                            ->columnSpan(2),
                        TextEntry::make('verification_hash')
                            ->label('Hash de verificação')
                            ->placeholder('-')
                            ->copyable()
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                Section::make('Revogação')
                    ->schema([
                        TextEntry::make('revoked_at')
                            ->label('Revogado em')
                            ->dateTime('d/m/Y H:i')
                            ->placeholder('-'),
                        TextEntry::make('revoked_reason')
                            ->label('Motivo')
                            ->placeholder('-')
                            ->columnSpan(2),
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
