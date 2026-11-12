<?php

use App\Models\User;
use App\Models\WorkPermit;

function createPermitForOperatorAccessAudit(
    User $owner,
    string $status = 'diizinkan'
): WorkPermit {
    return $owner->workPermits()->create([
        'full_name' => 'Pekerja Audit Akses',
        'company_name' => 'PT Contoh Energi',
        'job_title' => 'Teknisi',
        'work_location' => 'Zona A',
        'work_description' => 'Pengujian pembatasan akses operator.',
        'start_date' => '2026-11-10',
        'end_date' => '2026-11-12',
        'status' => $status,
    ]);
}

it('denies operators direct access to ordinary permit details for every permit status', function (string $status) {
    $operator = User::factory()->create(['role' => 'operator']);
    $owner = User::factory()->create(['role' => 'pemohon']);
    $permit = createPermitForOperatorAccessAudit($owner, $status);

    $this->actingAs($operator)
        ->get(route('permits.show', $permit))
        ->assertForbidden();
})->with([
    'pending' => 'ditinjau',
    'approved' => 'diizinkan',
    'rejected' => 'ditolak',
]);

it('denies non-operators access to an approved ID card print preview', function (string $role) {
    $owner = User::factory()->create(['role' => 'pemohon']);
    $permit = createPermitForOperatorAccessAudit($owner);

    $user = User::factory()->create(['role' => $role]);

    $this->actingAs($user)
        ->get(route('operator.id-cards.show', $permit))
        ->assertForbidden();
})->with([
    'applicant' => 'pemohon',
    'humas' => 'humas',
    'k3' => 'k3',
    'security' => 'keamanan',
]);
