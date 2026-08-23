<?php

namespace Database\Seeders;

use App\Models\Instructor;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoAccountsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@mail.com'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('Password123@'),
                'role' => 'admin',
                'status' => 1,
                'email_verified_at' => now(),
            ],
        );

        foreach ([
            ['name' => 'Demo Student One', 'email' => 'student1@mail.com'],
            ['name' => 'Demo Student Two', 'email' => 'student2@mail.com'],
        ] as $student) {
            User::updateOrCreate(
                ['email' => $student['email']],
                [
                    'name' => $student['name'],
                    'password' => Hash::make('Password123@'),
                    'role' => 'student',
                    'status' => 1,
                    'email_verified_at' => now(),
                ],
            );
        }

        foreach ([
            ['name' => 'Demo Instructor One', 'email' => 'instructor1@mail.com'],
            ['name' => 'Demo Instructor Two', 'email' => 'instructor2@mail.com'],
        ] as $instructorData) {
            $user = User::updateOrCreate(
                ['email' => $instructorData['email']],
                [
                    'name' => $instructorData['name'],
                    'password' => Hash::make('Password123@'),
                    'role' => 'instructor',
                    'status' => 1,
                    'email_verified_at' => now(),
                ],
            );

            Instructor::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'skills' => ['teaching', 'course design'],
                    'biography' => 'Demo instructor account for local testing.',
                    'resume' => 'https://example.com/resume.pdf',
                    'designation' => 'Instructor',
                    'status' => 'approved',
                ],
            );
        }
    }
}
