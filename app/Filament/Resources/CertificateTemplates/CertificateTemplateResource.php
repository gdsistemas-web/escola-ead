<?php

namespace App\Filament\Resources\CertificateTemplates;

use App\Filament\Resources\CertificateTemplates\Pages\CreateCertificateTemplate;
use App\Filament\Resources\CertificateTemplates\Pages\EditCertificateTemplate;
use App\Filament\Resources\CertificateTemplates\Pages\ListCertificateTemplates;
use App\Filament\Resources\CertificateTemplates\Pages\ViewCertificateTemplate;
use App\Filament\Resources\CertificateTemplates\Schemas\CertificateTemplateForm;
use App\Filament\Resources\CertificateTemplates\Schemas\CertificateTemplateInfolist;
use App\Filament\Resources\CertificateTemplates\Tables\CertificateTemplatesTable;
use App\Models\CertificateTemplate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CertificateTemplateResource extends Resource
{
    protected static ?string $model = CertificateTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Modelos de certificado';

    protected static ?string $modelLabel = 'modelo de certificado';

    protected static ?string $pluralModelLabel = 'modelos de certificado';

    protected static \UnitEnum|string|null $navigationGroup = 'Acadêmico';

    public static function form(Schema $schema): Schema
    {
        return CertificateTemplateForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CertificateTemplateInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CertificateTemplatesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCertificateTemplates::route('/'),
            'create' => CreateCertificateTemplate::route('/create'),
            'view' => ViewCertificateTemplate::route('/{record}'),
            'edit' => EditCertificateTemplate::route('/{record}/edit'),
        ];
    }
}
