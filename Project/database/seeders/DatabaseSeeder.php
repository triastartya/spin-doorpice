<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(['email' => 'admin@example.com'], ['name' => 'Admin HR', 'password' => 'password', 'role' => 'admin']);
        User::updateOrCreate(['email' => 'operator@example.com'], ['name' => 'Operator', 'password' => 'password', 'role' => 'operator']);
        $this->command?->info('admin@example.com / operator@example.com (password: password)');
    }
}
