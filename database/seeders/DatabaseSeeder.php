<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            MenuSeeder::class,
        ]);

        User::updateOrCreate(
            ['email' => 'admin@genzfoods.pk'],
            [
                'name' => 'Gen Z Admin',
                'phone' => '03000911000',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ],
        );

        User::updateOrCreate(
            ['email' => 'customer@genzfoods.pk'],
            [
                'name' => 'Test Customer',
                'phone' => '03001234567',
                'password' => Hash::make('password'),
                'role' => 'customer',
            ],
        );
    }
}
