<?php

namespace App\Filament\Resources\CertificateTemplates\Pages;

use App\Filament\Resources\CertificateTemplates\CertificateTemplateResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCertificateTemplate extends ViewRecord
{
    protected static string $resource = CertificateTemplateResource::class;

    public function getTitle(): string
    {
        return 'Detalhes do modelo de certificado';
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()->label('Editar'),
        ];
    }
}
