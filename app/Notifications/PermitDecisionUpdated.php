<?php
namespace App\Notifications;
use Illuminate\Notifications\Notification;
class PermitDecisionUpdated extends Notification {
    public function __construct(public int $permitId, public string $role, public string $decision) {}
    public function via(object $notifiable): array { return ['database']; }
    public function toDatabase(object $notifiable): array {
        return ['event' => 'permit_decision', 'title' => 'Keputusan izin kerja diperbarui',
            'message' => strtoupper($this->role).' memberikan keputusan '.$this->decision.' untuk izin IK-'.str_pad((string) $this->permitId, 6, '0', STR_PAD_LEFT).'.',
            'permit_id' => $this->permitId];
    }
}
