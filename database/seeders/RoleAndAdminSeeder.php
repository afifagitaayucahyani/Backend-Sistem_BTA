<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use App\Models\User;
use App\Models\Mahasiswa;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

class RoleAndAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // create roles
        $roles = [
            'Kepala Pusat',
            'Admin',
            'Tutor',
            'Mahasiswa',
            'Rektorat'
        ];

        foreach ($roles as $roleName) {
            Role::create(['name' => $roleName]);
        }

        // akun kepala pusat
        $SuperAdmin = User::create([
            'name' => 'Test Admin Kepala Pusat',
            'email' => 'testadminkepalapusat@example.com',
            'password' => Hash::make('adminPusat123'),
        ]);

        $SuperAdmin->assignRole('Kepala Pusat');

        // akun tutor
        $tutor = User::create([
            'name' => 'Test Tutor',
            'email' => 'testtutor@example.com',
            'password' => Hash::make('tutor123'),
        ]);
        $tutor->assignRole('Tutor');

        // akun admin/staff
        $admin = User::create([
            'name' => 'Test Admin',
            'email' => 'testadmin@example.com',
            'password' => Hash::make('admin123'),
        ]);
        $admin->assignRole('Admin');

        // akun mahasiswa
        $mahasiswa = User::create([
            'name' => 'Gita',
            'email' => 'gita@example.com',
            'password' => Hash::make('gita123'),
        ]);
        $mahasiswa->assignRole('Mahasiswa');

        Mahasiswa::create([
            'user_id' => $mahasiswa->id,
            'nim' => '2235223262',
            'program_studi' => 'Teknik Informatika',
            'fakultas' => 'Fakultas Sains dan Teknologi',
        ]);

    }
}
