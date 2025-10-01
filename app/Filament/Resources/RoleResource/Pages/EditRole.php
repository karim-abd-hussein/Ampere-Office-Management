<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use Filament\Resources\Pages\EditRecord;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['guard_name'] = $this->record->guard_name ?: config('auth.defaults.guard', 'web');
        return $data;
    }

    protected function afterSave(): void
    {
        cache()->forget('cached_permissions_list');
        cache()->forget('permission_translations');
        cache()->forget('roles_translations');
    }
}
