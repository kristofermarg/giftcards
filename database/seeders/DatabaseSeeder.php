<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure admin user always exists with known credentials
        User::updateOrCreate(
            ['email' => 'kristo@tactica.is'],
            [
                'name' => 'Admin',
                'password' => Hash::make('broskall1'),
            ]
        );

        $this->call([
            GiftcardSeeder::class,
        ]);
    }
}
