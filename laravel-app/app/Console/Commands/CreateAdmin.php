<?php

namespace App\Console\Commands;

use App\Models\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CreateAdmin extends Command
{
    protected $signature = 'admin:create';

    protected $description = 'Create an inventory dashboard administrator';

    public function handle(): int
    {
        $username = trim(text('Username admin'));
        $secret = password('Password admin');

        $validator = Validator::make(
            ['username' => $username, 'password' => $secret],
            [
                'username' => ['required', 'string', 'max:50', Rule::unique('admin', 'username')],
                'password' => ['required', 'string', 'min:8'],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        Admin::create([
            'username' => $username,
            'password_hash' => Hash::make($secret),
        ]);

        $this->components->info('Akun admin berhasil dibuat.');

        return self::SUCCESS;
    }
}
