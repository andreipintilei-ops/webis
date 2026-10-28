<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class CreateUser extends Command
{
    protected $signature = 'user:create
        {email : Login email address}
        {--name= : Display name (defaults to the part before @)}
        {--role=admin : admin or editor}
        {--password= : Password; prompted for when omitted, generated when non-interactive}';

    protected $description = 'Create a staff account. Public registration is disabled, so this is the only way in.';

    public function handle(): int
    {
        $email = Str::lower((string) $this->argument('email'));
        $name = (string) ($this->option('name') ?: Str::before($email, '@'));
        $role = UserRole::tryFrom((string) $this->option('role'));

        if ($role === null) {
            $this->error('Invalid role. Use one of: '.implode(', ', array_column(UserRole::cases(), 'value')).'.');

            return self::FAILURE;
        }

        $password = $this->option('password');
        $generated = false;

        if ($password === null) {
            if ($this->input->isInteractive()) {
                $password = (string) $this->secret('Password');

                if ($password !== (string) $this->secret('Confirm password')) {
                    $this->error('Passwords do not match.');

                    return self::FAILURE;
                }
            } else {
                $password = Str::password(20);
                $generated = true;
            }
        }

        $validator = Validator::make(
            ['email' => $email, 'name' => $name, 'password' => $password],
            [
                'email' => ['required', 'email', 'max:255', Rule::unique(User::class)],
                'name' => ['required', 'string', 'max:255'],
                'password' => ['required', 'string', Password::default()],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $user = new User(['name' => $name, 'email' => $email, 'password' => $password]);
        $user->role = $role;
        // Staff accounts are created by an admin, so the address counts as verified.
        $user->email_verified_at = now();
        $user->save();

        $this->info("Created {$role->label()} {$email}.");

        if ($generated) {
            $this->line("Generated password (shown once): {$password}");
        }

        return self::SUCCESS;
    }
}
