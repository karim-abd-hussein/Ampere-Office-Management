<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // سييدر الصلاحيات والأدوار
        $this->call(RolesAndPermissionsSeeder::class);

        // إنشاء مستخدم أدمن افتراضي
        User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'), // غيّرها لكلمة سر قوية
        ]);
    }
}
