<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

class CheckInstallation extends Command
{
    protected $signature = 'installation:check';
    protected $description = 'Periksa view profil dan route riwayat yang dibutuhkan instalasi.';

    public function handle(): int
    {
        $this->line('Folder aplikasi: '.base_path());
        $failed = false;
        foreach ([
            'profile.edit',
            'profile.partials.update-profile-information-form',
            'profile.partials.update-password-form',
            'profile.partials.delete-user-form',
            'admin.permohonan.index',
            'admin.permohonan.history',
        ] as $name) {
            if (View::exists($name)) {
                $this->info('OK view: '.$name);
            } else {
                $failed = true;
                $this->error('HILANG view: '.$name);
            }
        }
        foreach (['profile.edit', 'profile.update', 'profile.destroy', 'password.update',
            'admin.permohonan.index', 'admin.permohonan.history', 'admin.permohonan.decide', 'permits.show'] as $name) {
            if (Route::has($name)) {
                $this->info('OK route: '.$name);
            } else {
                $failed = true;
                $this->error('HILANG route: '.$name);
            }
        }
        if ($failed) {
            $this->error('Instalasi belum lengkap. Periksa folder aplikasi, file yang disalin, lalu jalankan php artisan optimize:clear.');
            return self::FAILURE;
        }
        $this->info('View dan route utama tersedia. Lanjutkan pengujian halaman dan alur pengguna.');
        return self::SUCCESS;
    }
}
