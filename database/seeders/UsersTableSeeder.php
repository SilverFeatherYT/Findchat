<?php

namespace Database\Seeders;

use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class UsersTableSeeder extends Seeder
{
    public function run()
    {
        $users = [
            [
                'id' => 1,
                'firebase_uid' => 'rigN0zsIeNUzOGV3d6CfKqVzRep2',
                'name' => 'Jason',
                'email' => 'jason@findhouse.admin',
                'password' => bcrypt('password'),
                'email_verified_at' => Carbon::now()->toDateTimeString(),


            ],
            [
                'id' => 2,
                'firebase_uid' => 'RL2je7YCVYO11z2qvw9BnmDx1EF3',
                'name' => 'John',
                'email' => 'john@findhouse.agent',
                'password' => bcrypt('password'),
                'email_verified_at' => Carbon::now()->toDateTimeString(),

            ],
            [
                'id' => 3,
                'firebase_uid' => 'KutGIlNGZoYYHGzuZlBSRYtwb0D3',
                'name' => 'Sam',
                'email' => 'sam@findhouse.agent',
                'password' => bcrypt('password'),
                'email_verified_at' => Carbon::now()->toDateTimeString(),

            ],
            [
                'id' => 4,
                'firebase_uid' => 'SezWZ1QDYydiTJ6RCuACb65scfE2',
                'name' => 'mike',
                'email' => 'mike@findhouse.agent',
                'password' => bcrypt('password'),
                'email_verified_at' => Carbon::now()->toDateTimeString(),

            ],
            [
                'id' => 5,
                'firebase_uid' => 'dH6QfkLt8Hag7Ps2szG3OCuDVTb2',
                'name' => 'Jane',
                'email' => 'jane@findhouse.customer',
                'password' => bcrypt('password'),
                'email_verified_at' => Carbon::now()->toDateTimeString(),

            ],
            [
                'id' => 6,
                'firebase_uid' => 'PnBlBf8v2cQMMLTlNZnFO05naNQ2',
                'name' => 'Jack',
                'email' => 'jack@findhouse.customer',
                'password' => bcrypt('password'),
                'email_verified_at' => Carbon::now()->toDateTimeString(),

            ],
            [
                'id' => 7,
                'firebase_uid' => 'iVx5AIeuomYJde3Fh6eweS0oOhq2',
                'name' => 'Adam',
                'email' => 'adam@findhouse.customer',
                'password' => bcrypt('password'),
                'email_verified_at' => Carbon::now()->toDateTimeString(),

            ],
            [
                'id' => 8,
                'firebase_uid' => 'wxOpOVVX73bV9WFRMkG1wMBLIxj1',
                'name' => '銀色羽毛',
                'email' => 'yinyuminecraft@gmail.com',
                'password' => bcrypt('password'),
                'email_verified_at' => Carbon::now()->toDateTimeString(),

            ],
            [
                'id' => 9,
                'firebase_uid' => 'vBRTCsqtrSW8ch8rCx4jgt8bpIs1',
                'name' => 'yu yan',
                'email' => 'yuyan5547@gmail.com',
                'password' => bcrypt('password'),
                'email_verified_at' => Carbon::now()->toDateTimeString(),

            ],
            [
                'id' => 10,
                'firebase_uid' => 'qGK7qLVBqmNFBGcR3zU69le6Zkr1',
                'name' => 'Findhouse Developers',
                'email' => 'findhouse.devs@gmail.com',
                'password' => bcrypt('password'),
                'email_verified_at' => Carbon::now()->toDateTimeString(),

            ],
        ];

        User::insert($users);

        // Ensure the users table has enough users
        if (User::count() < 2) {
            echo "Need at least 2 users in the users table to seed messages.\n";
            return;
        }

        // Get all user firebase_uids
        $userIds = User::pluck('firebase_uid')->all();

        // Seed 50 messages
        for ($i = 0; $i < 50; $i++) {
            // Randomly select sender and receiver
            $senderId = $userIds[array_rand($userIds)];
            $receiverId = $userIds[array_rand($userIds)];

            // Ensure sender and receiver are not the same
            while ($senderId === $receiverId) {
                $receiverId = $userIds[array_rand($userIds)];
            }

            // Create a new message
            Message::create([
                'receiver_id' => $receiverId,
                'sender_id' => $senderId,
                'message' => Str::random(100),
                'read_at' => now()->addMinutes(rand(0, 60))
            ]);
        }
    }
}
