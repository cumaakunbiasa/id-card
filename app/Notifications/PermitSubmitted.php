<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PermitSubmitted extends Notification implements \Illuminate\Contracts\Queue\ShouldQueue
{
    use Queueable;

    public $tries = 5;
    public $backoff = [30, 120, 300, 900];

    public function __construct(
        public string $groupId,
        public string $companyName,
        public int $peopleCount,
    ) { $this->beforeCommit(); }

    public function viaConnections(): array
    {
        return ['database' => 'sync', 'mail' => 'permit_mail'];
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): \Illuminate\Notifications\Messages\MailMessage
    {
        return (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject('Pengajuan izin kerja baru - '.$this->companyName)
            ->greeting('Yth. '.$notifiable->name.',')
            ->line('Pengajuan baru dari '.$this->companyName.' berisi '.$this->peopleCount.' orang termasuk PIC.')
            ->line('Nomor kelompok: '.$this->groupId)
            ->action('Tinjau permohonan', route('admin.permohonan.index'))
            ->line('Silakan masuk menggunakan akun petugas Anda.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'event' => 'permit_submitted',
            'title' => 'Pengajuan izin kerja baru',
            'message' => sprintf(
                'Pengajuan dari %s telah diterima. Terdapat %d orang dalam kelompok ini.',
                $this->companyName,
                $this->peopleCount
            ),
            'group_id' => $this->groupId,
            'company_name' => $this->companyName,
            'people_count' => $this->peopleCount,
            'url' => route('admin.permohonan.index'),
        ];
    }
}
