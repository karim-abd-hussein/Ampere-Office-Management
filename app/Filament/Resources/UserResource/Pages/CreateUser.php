<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Spatie\Permission\Models\Role;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function afterCreate(): void
    {
        $roleId = data_get($this->form->getState(), 'role');
        if ($roleId && ($role = Role::find($roleId))) {
            $this->record->syncRoles([$role->name]);
        }
    }
}
