<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateDefaultUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create-default 
                            {--email= : Email address for the default user}
                            {--password= : Password for the default user}
                            {--name= : Name for the default user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a default admin user account';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Get credentials from options or environment
        $email = $this->option('email') ?: env('DEFAULT_USER_EMAIL', 'admin@avinash-eye.local');
        $password = $this->option('password') ?: env('DEFAULT_USER_PASSWORD', 'Admin@123');
        $name = $this->option('name') ?: env('DEFAULT_USER_NAME', 'Administrator');

        // Check if user already exists
        $existingUser = User::where('email', $email)->first();

        if ($existingUser) {
            $this->error("User with email '{$email}' already exists!");
            
            if (!$this->confirm('Do you want to update the password for this user?', false)) {
                return Command::FAILURE;
            }

            $existingUser->update([
                'password' => Hash::make($password),
                'password_changed_at' => now(),
                'login_attempts' => 0,
                'locked_until' => null,
                'is_active' => true,
            ]);

            $this->info("✅ User password updated successfully!");
            $this->line("   Email: {$email}");
            $this->line("   New Password: {$password}");
            return Command::SUCCESS;
        }

        // Create new user
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

        $this->info("✅ Default user created successfully!");
        $this->line("   Name: {$name}");
        $this->line("   Email: {$email}");
        $this->line("   Password: {$password}");
        $this->newLine();
        $this->warn("   ⚠️  IMPORTANT: Please change the default password after first login!");

        return Command::SUCCESS;
    }
}
