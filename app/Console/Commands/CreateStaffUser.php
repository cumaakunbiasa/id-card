<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class CreateStaffUser extends Command
{
    protected $signature = 'staff:create';
    protected $description = 'Buat akun petugas baru secara interaktif tanpa kata sandi default.';

    public function handle(): int
    {
        $data = [
            'name' => $this->ask('Nama petugas'),
            'email' => strtolower(trim((string) $this->ask('Email petugas'))),
            'role' => $this->choice('Bagian', ['humas', 'k3', 'keamanan', 'operator']),
            'password' => $this->secret('Kata sandi (minimal 12 karakter, huruf dan angka)'),
            'password_confirmation' => $this->secret('Ulangi kata sandi'),
        ];
        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(['humas', 'k3', 'keamanan', 'operator'])],
            'password' => ['required', 'confirmed', Password::min(12)->letters()->numbers()],
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return self::FAILURE;
        }
        $user = User::create([
            'name' => $data['name'], 'email' => $data['email'],
            'role' => $data['role'], 'password' => Hash::make($data['password']),
        ]);

        event(new Registered($user));

        $this->info('Akun petugas berhasil dibuat. Tautan verifikasi email telah dikirim sesuai konfigurasi mail.');
        return self::SUCCESS;
    }
}
