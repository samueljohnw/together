<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

#[Signature('together:add-member {email : The email address they will sign in with} {name : The name shown on their messages}')]
#[Description('Add a household member who can sign in to Together')]
class AddHouseholdMember extends Command
{
    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $name = (string) $this->argument('name');

        $validator = Validator::make(['email' => $email, 'name' => $name], [
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $password = (string) $this->secret('Password (at least 8 characters)');

        if (mb_strlen($password) < 8) {
            $this->error('The password must be at least 8 characters.');

            return self::FAILURE;
        }

        if ($password !== (string) $this->secret('Confirm password')) {
            $this->error('The passwords did not match.');

            return self::FAILURE;
        }

        User::create(['email' => $email, 'name' => $name, 'password' => $password]);

        $this->info("{$name} can now sign in as {$email}.");

        return self::SUCCESS;
    }
}
