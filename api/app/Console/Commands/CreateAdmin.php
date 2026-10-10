<?php

namespace App\Console\Commands;

use App\Models\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Adds an admin with their own login. Asks for the details; the password is
 * typed hidden and twice. There is no screen for managing admins yet.
 */
class CreateAdmin extends Command
{
    protected $signature = 'admin:create';

    protected $description = 'Add an admin who can sign in to the admin panel';

    /** Wrong answers allowed per question before giving up (a script, say, can't answer). */
    private const TRIES = 3;

    public function handle(): int
    {
        try {
            $name = $this->askValid(fn () => $this->ask('Name'), ['required', 'string', 'max:255'], 'name');
            $email = Str::lower($this->askValid(fn () => $this->ask('Email'), ['required', 'email', 'max:255', 'unique:admins,email'], 'email'));
            $password = $this->askPassword();
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $admin = Admin::create(['name' => $name, 'email' => $email, 'password' => $password]); // hashed by the model's cast

        $this->info("Admin {$admin->name} <{$admin->email}> can now sign in.");

        return self::SUCCESS;
    }

    /** The password, typed hidden and then once more to confirm; at most TRIES times. */
    private function askPassword(): string
    {
        for ($try = 1; $try <= self::TRIES; $try++) {
            $password = (string) $this->secret('Password (at least 10 characters)');

            $validator = Validator::make(['password' => $password], ['password' => ['required', 'string', 'min:10', 'max:255']]);
            if ($validator->fails()) {
                $this->error($validator->errors()->first('password'));

                continue;
            }

            if ((string) $this->secret('Password again') !== $password) {
                $this->error("The two passwords don't match.");

                continue;
            }

            return $password;
        }

        throw new RuntimeException('No admin created: no valid password after '.self::TRIES.' tries.');
    }

    /** Asks until the answer passes the rules, at most TRIES times. */
    private function askValid(callable $ask, array $rules, string $name, array $messages = []): string
    {
        for ($try = 1; $try <= self::TRIES; $try++) {
            $answer = trim((string) $ask());
            $validator = Validator::make([$name => $answer], [$name => $rules], $messages);
            if ($validator->passes()) {
                return $answer;
            }
            $this->error($validator->errors()->first($name));
        }

        throw new RuntimeException("No admin created: no valid {$name} after ".self::TRIES.' tries.');
    }
}
