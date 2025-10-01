<?php

namespace App\Filament\Resources\GeneratorResource\Pages;

use App\Filament\Resources\GeneratorResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGenerator extends EditRecord
{
    protected static string $resource = GeneratorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->visible(fn () => GeneratorResource::canDelete($this->record)),
        ];
    }
}
