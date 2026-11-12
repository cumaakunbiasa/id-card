<?php

use App\Models\User;
use App\Models\WorkPermit;

function createHistoryPermit(string $status): WorkPermit
{
    return User::factory()->create(['role' => 'pemohon'])->workPermits()->create([
        'full_name' => 'Pemohon '.$status,
        'company_name' => 'PT Uji',
        'job_title' => 'Teknisi',
        'work_location' => 'Gardu',
        'work_description' => 'Pemeliharaan',
        'start_date' => '2026-10-29',
        'end_date' => '2026-10-30',
        'status' => $status,
    ]);
}

it('shows every applicant and final status to each reviewer', function (string $role) {
    $reviewer = User::factory()->create(['role' => $role]);
    foreach (['ditinjau', 'diizinkan', 'ditolak'] as $status) {
        createHistoryPermit($status);
    }
    $this->actingAs($reviewer)->get(route('admin.permohonan.history'))
        ->assertOk()->assertSee('Pemohon ditinjau')->assertSee('Pemohon diizinkan')->assertSee('Pemohon ditolak');
    $this->get(route('admin.permohonan.history', ['status' => 'diizinkan']))
        ->assertOk()->assertSee('Pemohon diizinkan')->assertDontSee('Pemohon ditinjau')->assertDontSee('Pemohon ditolak');
    $this->get(route('admin.permohonan.history', ['status' => 'ditolak']))
        ->assertOk()->assertSee('Pemohon ditolak')->assertDontSee('Pemohon diizinkan');
})->with(['humas', 'k3', 'keamanan']);

it('keeps partial approvals visible and filters by the current department', function (string $role) {
    $reviewer = User::factory()->create(['role' => $role]);
    $permit = createHistoryPermit('ditinjau');
    foreach (['humas', 'k3', 'keamanan'] as $department) {
        $permit->approvals()->create([
            'admin_role' => $department,
            'status' => $department === $role ? 'disetujui' : 'menunggu',
            'admin_user_id' => $department === $role ? $reviewer->id : null,
            'notes' => $department === $role ? 'Pemeriksaan selesai' : null,
            'decided_at' => $department === $role ? now() : null,
        ]);
    }
    $this->actingAs($reviewer)->get(route('admin.permohonan.history', ['decision' => 'disetujui']))
        ->assertOk()->assertSee($permit->full_name)->assertSee('Pemeriksaan selesai')->assertSee($reviewer->name);
    $this->get(route('admin.permohonan.history', ['decision' => 'menunggu']))
        ->assertOk()->assertDontSee($permit->full_name);
    $this->get(route('permits.show', $permit))->assertOk()->assertSee('Pemeriksaan selesai');
    $this->get(route('admin.permohonan.index'))->assertOk()->assertDontSee($permit->full_name);
})->with(['humas', 'k3', 'keamanan']);

it('denies history access to other roles', function (string $role) {
    $this->actingAs(User::factory()->create(['role' => $role]))
        ->get(route('admin.permohonan.history'))->assertForbidden();
})->with(['pemohon', 'operator']);

it('does not let applicants see another applicants registration', function () {
    $permit = createHistoryPermit('diizinkan');
    $this->actingAs(User::factory()->create(['role' => 'pemohon']))
        ->get(route('permits.show', $permit))->assertForbidden();
});

it('rejects invalid history filters', function () {
    $this->actingAs(User::factory()->create(['role' => 'humas']))
        ->getJson(route('admin.permohonan.history', ['status' => 'invalid']))
        ->assertUnprocessable()->assertJsonValidationErrors('status');
});
