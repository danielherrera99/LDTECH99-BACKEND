<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::create([
            'name' => 'ldtech99',
            'username' => 'ldtech',
            'email' => 'contacto@ldtech99.com',
            'password' => bcrypt('tramun15'),
            'role' => 'Administrador / Principal SysAdmin',
            'avatar' => 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=150&h=150&q=80',
            'credits' => '∞',
        ]);

        User::create([
            'name' => 'Cliente Premium',
            'username' => 'cliente',
            'email' => 'cliente@ldtech99.com',
            'password' => bcrypt('cliente2026'),
            'role' => 'Consultor Premium / Cliente',
            'avatar' => 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=crop&w=150&h=150&q=80',
            'credits' => '150',
        ]);

        User::create([
            'name' => 'Usuario Demo',
            'username' => 'demo',
            'email' => 'demo@ldtech99.com',
            'password' => bcrypt('demo123'),
            'role' => 'Usuario de Pruebas / Demo',
            'avatar' => 'https://images.unsplash.com/photo-1570295999919-56ceb5ecca61?auto=format&fit=crop&w=150&h=150&q=80',
            'credits' => '20',
        ]);
    }
}
