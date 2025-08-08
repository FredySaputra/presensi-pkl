<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
      $admin = [
        [
            'name' => 'Fredy Dwi Saputra',
            'email' => '2311500140@student.budiluhur.ac.id',
            'password' => Hash::make('spv12345'),
            'role' => 'admin'
        ]
        ];

        foreach($admin as $a){
            User::create($a);
        }
    }
}
