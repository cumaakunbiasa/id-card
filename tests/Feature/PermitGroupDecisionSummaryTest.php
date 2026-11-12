<?php

use App\Models\User;
use App\Models\WorkPermit;
use App\Notifications\PermitDecisionUpdated;
use App\Notifications\PermitGroupDecisionSummary;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

function makeDecisionEmailGroup(User $owner): array
{
    $groupId = (string) Str::uuid();

    $people = [
        ['person_type' => 'mandor', 'full_name' => 'Mandor Pengujian'],
        ['person_type' => 'pekerja', 'full_name' => 'Pekerja Ditolak'],
        ['person_type' => 'pekerja', 'full_name' => 'Pekerja Diterima'],
    ];

    $members = [];

    foreach ($people as $person) {
        $permit = $owner->workPermits()->create([
            'group_id' => $groupId,
            'person_type' => $person['person_type'],
            'foreman_name' => 'Mandor Pengujian',
            'full_name' => $person['full_name'],
            'company_name' => 'PT Pengujian Email',
            'job_title' => $person['person_type'] === 'mandor' ? 'Mandor' : 'Pekerja Proyek',
            'work_location' => 'Zona Pengujian',
            'work_description' => 'Pengujian alur keputusan izin kerja',
            'start_date' => '2026-11-10',
            'end_date' => '2026-11-10',
            'status' => 'ditinjau',
        ]);

        foreach (['humas', 'k3', 'keamanan'] as $role) {
            $permit->approvals()->create([
                'admin_role' => $role,
                'status' => 'menunggu',
            ]);
        }

        $members[] = $permit;
    }

    return $members;
}

function postDecisionAs(
    $test,
    User $admin,
    WorkPermit $permit,
    string $decision,
    ?string $notes = null,
): void {
    $payload = $notes === null ? [] : ['notes' => $notes];

    $test->actingAs($admin)
        ->from(route('permits.show', $permit))
        ->post(route('admin.permohonan.decide', [$permit, $decision]), $payload)
        ->assertSessionHasNoErrors();
}

it('sends exactly one group summary after every member has a final decision', function () {
    Notification::fake();

    $owner = User::factory()->create(['role' => 'pemohon']);
    $members = makeDecisionEmailGroup($owner);

    $admins = collect(['humas', 'k3', 'keamanan'])
        ->mapWithKeys(fn (string $role) => [
            $role => User::factory()->create(['role' => $role]),
        ]);

    // Mandor is accepted only after all three approval roles approve.
    foreach (['humas', 'k3', 'keamanan'] as $role) {
        postDecisionAs($this, $admins[$role], $members[0], 'setuju');
    }

    // One worker is rejected. The other worker is still under review.
    postDecisionAs(
        $this,
        $admins['humas'],
        $members[1],
        'tolak',
        'Dokumen pekerja belum lengkap.',
    );

    expect($members[0]->fresh()->status)->toBe('diizinkan');
    expect($members[1]->fresh()->status)->toBe('ditolak');
    expect($members[2]->fresh()->status)->toBe('ditinjau');

    Notification::assertNotSentTo($owner, PermitGroupDecisionSummary::class);

    // The last worker is accepted after all three roles approve.
    foreach (['humas', 'k3', 'keamanan'] as $role) {
        postDecisionAs($this, $admins[$role], $members[2], 'setuju');
    }

    Notification::assertSentToTimes($owner, PermitGroupDecisionSummary::class, 1);
    Notification::assertSentToTimes($owner, PermitDecisionUpdated::class, 7);

    $summary = Notification::sent($owner, PermitGroupDecisionSummary::class)->sole();
    $summaryMembers = collect($summary->members)->keyBy('full_name');
    $mail = $summary->toMail($owner);

    expect($mail->subject)->toBe('Hasil keputusan izin kerja kelompok - PT Pengujian Email')
        ->and($mail->introLines)->toContain(
            'Perusahaan: PT Pengujian Email',
            'Jumlah anggota: 3',
            '1. Mandor Pengujian (Mandor) - DITERIMA',
            '2. Pekerja Ditolak (Pekerja) - DITOLAK',
            'Alasan penolakan: HUMAS: Dokumen pekerja belum lengkap.',
            '3. Pekerja Diterima (Pekerja) - DITERIMA',
        );

    expect($summary->companyName)->toBe('PT Pengujian Email')
        ->and($summary->groupId)->toBe($members[0]->group_id)
        ->and($summaryMembers)->toHaveCount(3)
        ->and($summaryMembers['Mandor Pengujian']['status'])->toBe('diizinkan')
        ->and($summaryMembers['Pekerja Ditolak']['status'])->toBe('ditolak')
        ->and($summaryMembers['Pekerja Ditolak']['rejection_reasons'])
            ->toContain('HUMAS: Dokumen pekerja belum lengkap.')
        ->and($summaryMembers['Pekerja Diterima']['status'])->toBe('diizinkan')
        ->and($summary->afterCommit)->toBeTrue()
        ->and($summary->viaConnections()['mail'])->toBe('permit_mail')
        ->and($members[0]->fresh()->decision_email_queued_at)->not->toBeNull();

    // A repeated decision against a finalized member must not queue another summary.
    $this->actingAs($admins['humas'])
        ->from(route('permits.show', $members[2]))
        ->post(route('admin.permohonan.decide', [$members[2], 'setuju']))
        ->assertSessionHasErrors('decision');

    Notification::assertSentToTimes($owner, PermitGroupDecisionSummary::class, 1);
});

it('keeps per-decision database notifications while a group is still pending', function () {
    $owner = User::factory()->create(['role' => 'pemohon']);
    $members = makeDecisionEmailGroup($owner);
    $humas = User::factory()->create(['role' => 'humas']);

    postDecisionAs(
        $this,
        $humas,
        $members[1],
        'tolak',
        'Dokumen pekerja belum lengkap.',
    );

    $this->assertDatabaseCount('notifications', 1);
    $this->assertDatabaseHas('notifications', [
        'notifiable_type' => User::class,
        'notifiable_id' => $owner->id,
        'type' => PermitDecisionUpdated::class,
    ]);

    expect($members[1]->fresh()->status)->toBe('ditolak')
        ->and($members[0]->fresh()->status)->toBe('ditinjau')
        ->and($members[2]->fresh()->status)->toBe('ditinjau');
});
