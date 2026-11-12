<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PermitGroupDecisionSummary extends Notification implements ShouldQueue
{
    use Queueable;

    public $tries = 5;

    public $backoff = [30, 120, 300, 900];

    public function __construct(
        public ?string $groupId,
        public string $companyName,
        public int $firstPermitId,
        public array $members,
    ) {
        // Job masuk antrean dalam transaksi yang sama.
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function viaConnections(): array
    {
        return [
            'mail' => 'permit_mail',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject(
                'Hasil keputusan izin kerja kelompok - ' . $this->companyName
            )
            ->greeting('Yth. ' . $notifiable->name . ',')
            ->line(
                'Seluruh anggota kelompok proyek telah memperoleh keputusan akhir.'
            )
            ->line('Perusahaan: ' . $this->companyName)
            ->line('Jumlah anggota: ' . count($this->members));

        if ($this->groupId !== null) {
            $mail->line('ID kelompok: ' . $this->groupId);
        }

        $mail->line('Rekap keputusan anggota:');

        foreach ($this->members as $index => $member) {
            $status = match ($member['status']) {
                'diizinkan' => 'DITERIMA',
                'ditolak' => 'DITOLAK',
                default => strtoupper($member['status']),
            };

            $personType = match ($member['person_type']) {
                'mandor' => 'Mandor',
                'pekerja' => 'Pekerja',
                default => 'Anggota',
            };

            $mail->line(
                ($index + 1) . '. ' . $member['full_name'] .
                    ' (' . $personType . ') - ' . $status
            );

            foreach ($member['rejection_reasons'] as $reason) {
                $mail->line('Alasan penolakan: ' . $reason);
            }
        }

        return $mail
            ->action(
                'Lihat detail kelompok',
                route('permits.show', $this->firstPermitId)
            )
            ->line('Terima kasih telah menggunakan Sistem Pengajuan ID Card.');
    }
}
