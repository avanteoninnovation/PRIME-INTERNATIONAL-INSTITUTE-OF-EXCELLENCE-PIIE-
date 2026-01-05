<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class CreateUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = [
            [
               'name'=>'Superadmin',
               'email'=>'superadmin@example.com',
               'role_id'=>'1',
               'password'=> bcrypt('1234'),
            ],
            [
               'name'=>'Admin',
               'email'=>'admin@example.com',
               'role_id'=>'2',
               'password'=> bcrypt('1234'),
            ],
            [
               'name'=>'User',
               'email'=>'student@example.com',
               'role_id'=>'3',
               'password'=> bcrypt('1234'),
            ],
        ];
  
        foreach ($user as $key => $value) {
            User::create($value);
        }
    }

}
