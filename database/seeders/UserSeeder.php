<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder {
    public function run() {
        User::create([
            'name' => 'Admin PKL',
            'email' => 'admin@attendance.test',
            'password' => Hash::make('password'), // default password
            'role' => 'admin'
        ]);
    }
}
