<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\User;

class UserTableSeeder extends Seeder
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
        User::truncate();      
        //DB::table('profile_role')->truncate();

        $avatars[0] = 'test0';
        $avatars[1] = 'test1';
        $avatars[2] = 'test2';
        $avatars[3] = 'test3';
        $avatars[4] = 'test4';
 
        $usersCount = 10;


        for ($i = 0; $i < $usersCount; $i++) {
            $phone = '06';
            for ($j = 0; $j<8; $j++) {
                $phone .= mt_rand(0,9);
            }
            $user = User::create([
                'firstName' => $faker->firstName,
                'lastName' => $faker->lastName,
                'mobile' => $phone,
                'email' => 'sergi.redorta' . $i . '@kubiiks.com',
                'isEmailValidated' => 1,
                'emailValidationKey' => Str::random(30)
            ]);
        }        
    }
}
