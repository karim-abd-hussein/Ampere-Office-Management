<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\EditRecord;
use Spatie\Permission\Models\Role;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function afterSave(): void
    {
        $roleId = data_get($this->form->getState(), 'role');
        if ($roleId && ($role = Role::find($roleId))) {
            $this->record->syncRoles([$role->name]);
        }
    }
}
