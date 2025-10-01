<?php

namespace App\Filament\Resources\CycleResource\Pages;

use App\Filament\Resources\CycleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCycle extends EditRecord
{
    protected static string $resource = CycleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => CycleResource::canDelete($this->record)),
        ];
    }
}
