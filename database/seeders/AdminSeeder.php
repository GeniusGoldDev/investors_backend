<?php

namespace Database\Seeders;

use App\Models\Investor;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Database\Seeder;
use Illuminate\Foundation\Auth\User as AuthUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        Investor::create([
            'fname' => 'Admin',
            'lname' => 'AtLifecard',
            'email' => 'admin@admin.com',
            'user_type' => 'admin',
            'password' => Hash::make(12345678)
        ]);
    }
}
