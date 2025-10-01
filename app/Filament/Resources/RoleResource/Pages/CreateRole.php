<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['name']       = trim((string) ($data['name'] ?? ''));
        $data['guard_name'] = config('auth.defaults.guard', 'web');
        return $data;
    }

    protected function afterCreate(): void
    {
        cache()->forget('cached_permissions_list');
        cache()->forget('permission_translations');
        cache()->forget('roles_translations');
    }
}
