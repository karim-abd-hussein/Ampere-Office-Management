<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
           // إنشاء الصلاحيات (including those from RolePolicy)
           $permissions = [
            'view subscribers',
            'edit readings',
            'view invoices',
            'export reports',
            'view reports',
            'manage settings',
            'manage generators',
            // Permissions from RolePolicy:
            'عرض الأدوار',    // view roles
            'إضافة الأدوار',   // add roles
            'تعديل الأدوار',   // edit roles
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // إنشاء الأدوار
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $assistant = Role::firstOrCreate(['name' => 'assistant']);

        // ربط الصلاحيات
        $admin->syncPermissions($permissions);
        $assistant->syncPermissions(['view subscribers', 'edit readings']);
    }
}
