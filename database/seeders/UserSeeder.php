<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Default user credentials from environment or use defaults
        $email = env('DEFAULT_USER_EMAIL', 'admin@avinash-eye.local');
        $password = env('DEFAULT_USER_PASSWORD', 'Admin@123');
        $name = env('DEFAULT_USER_NAME', 'Administrator');

        // Check if user already exists
        $user = User::where('email', $email)->first();

        if ($user) {
            $this->command->info("User with email '{$email}' already exists. Skipping...");
            return;
        }

        // Create default user
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'email_verified_at' => now(),
            'is_active' => true,
            'login_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => null,
            'last_login_ip' => null,
            'two_factor_secret' => null,
            'two_factor_enabled' => false,
            'password_changed_at' => now(),
            'remember_token' => Str::random(60),
        ]);

        $this->command->info("✅ Default user created successfully!");
        $this->command->info("   Email: {$email}");
        $this->command->info("   Password: {$password}");
        $this->command->warn("   ⚠️  Please change the default password after first login!");
    }
}
