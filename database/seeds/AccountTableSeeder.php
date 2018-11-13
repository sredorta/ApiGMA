<?php

use Illuminate\Database\Seeder;
use App\User;
use App\Account;

class AccountTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = \Faker\Factory::create();
        // Let's truncate our existing records to start from scratch.
        Account::truncate();

        //We first find all users and create one user with 'standard' access
        $count = User::all()->count();
        for ($i = 1; $i <= $count; $i++) {
            $user = User::find($i);
            $account = Account::create([
                'user_id' => $i,
                'email' => $user->email,
                'password' => Hash::make('Secure0', ['rounds' => 12]),
                'access' => "standard" //Config::get('constants.ACCESS_DEFAULT')
            ]);
        }       
    }
}
