<?php

use App\Models\User;
use App\Models\WorkPermit;

function improvementPermit(User $owner, array $attributes = []): WorkPermit
{
    $permit = $owner->workPermits()->create(array_merge([
        'full_name' => 'Pekerja Uji', 'company_name' => 'PT Energi',
        'job_title' => 'Teknisi', 'work_location' => 'Gardu',
        'work_description' => 'Pemeliharaan', 'start_date' => '2026-10-29',
        'end_date' => '2026-10-30', 'status' => 'ditinjau',
    ], $attributes));
    foreach (['humas', 'k3', 'keamanan'] as $role) {
        $permit->approvals()->create(['admin_role' => $role, 'status' => 'menunggu']);
    }
    return $permit;
}

it('requires a rejection reason and preserves data on validation failure', function (string $role) {
    $permit = improvementPermit(User::factory()->create());
    $this->actingAs(User::factory()->create(['role' => $role]))
        ->post(route('admin.permohonan.decide', [$permit, 'tolak']), ['notes' => '   '])
        ->assertSessionHasErrors('notes');
    expect($permit->fresh()->status)->toBe('ditinjau');
    expect($permit->approvals()->where('status', 'menunggu')->count())->toBe(3);
    $this->post(route('admin.permohonan.decide', [$permit, 'tolak']), ['notes' => 'Dokumen belum lengkap'])
        ->assertSessionHasNoErrors();
    expect($permit->fresh()->status)->toBe('ditolak');
    $this->post(route('admin.permohonan.decide', [$permit, 'setuju']))->assertSessionHasErrors('decision');
    expect($permit->fresh()->status)->toBe('ditolak');
})->with(['humas', 'k3', 'keamanan']);

it('issues a permit only after all departments approve and rejects repeat decisions', function () {
    $permit = improvementPermit(User::factory()->create());
    foreach (['humas', 'k3', 'keamanan'] as $role) {
        $this->actingAs(User::factory()->create(['role' => $role]))
            ->post(route('admin.permohonan.decide', [$permit, 'setuju']))->assertSessionHasNoErrors();
        expect($permit->fresh()->status)->toBe($role === 'keamanan' ? 'diizinkan' : 'ditinjau');
        $this->post(route('admin.permohonan.decide', [$permit, 'setuju']))->assertSessionHasErrors('decision');
    }
});

it('searches history by permit number and includes the final day of a date range', function () {
    $owner = User::factory()->create();
    $permit = improvementPermit($owner, ['full_name' => 'Target Pencarian']);
    $permit->forceFill(['created_at' => '2026-10-29 23:59:59'])->save();
    $other = improvementPermit($owner, ['full_name' => 'Di Luar Rentang']);
    $other->forceFill(['created_at' => '2026-10-30 00:00:00'])->save();
    $this->actingAs(User::factory()->create(['role' => 'humas']))
        ->get(route('admin.permohonan.history', ['q' => 'IK-'.str_pad($permit->id, 6, '0', STR_PAD_LEFT)]))
        ->assertOk()->assertSee('Target Pencarian')->assertDontSee('Di Luar Rentang');
    $this->get(route('admin.permohonan.history', ['from' => '2026-10-29', 'to' => '2026-10-29']))
        ->assertOk()->assertSee('Target Pencarian')->assertDontSee('Di Luar Rentang');
    $this->getJson(route('admin.permohonan.history', ['from' => '2026-10-30', 'to' => '2026-10-29']))
        ->assertUnprocessable()->assertJsonValidationErrors('to');
});

it('paginates applicants without reducing totals to the current page', function () {
    $owner = User::factory()->create(['role' => 'pemohon']);
    for ($i = 0; $i < 21; $i++) {
        improvementPermit($owner);
    }
    $this->actingAs($owner)->get(route('permits.index', ['page' => 2]))
        ->assertOk()->assertViewHas('counts', fn ($counts) => (int) $counts->get('ditinjau') === 21)
        ->assertViewHas('permits', fn ($permits) => $permits->total() === 21 && $permits->count() === 1);
    $this->actingAs(User::factory()->create(['role' => 'k3']))
        ->get(route('admin.permohonan.index', ['page' => 2]))
        ->assertOk()->assertViewHas('permits', fn ($permits) => $permits->total() === 21 && $permits->count() === 1);
});

it('keeps accounts with permits or decisions and their history intact', function () {
    $owner = User::factory()->create();
    $permit = improvementPermit($owner);
    $reviewer = User::factory()->create(['role' => 'humas']);
    $permit->approvals()->where('admin_role', 'humas')->update([
        'admin_user_id' => $reviewer->id, 'status' => 'disetujui', 'decided_at' => now(),
    ]);
    foreach ([$owner, $reviewer] as $user) {
        $this->actingAs($user)->delete(route('profile.destroy'), ['password' => 'password'])
            ->assertSessionHasErrorsIn('userDeletion', 'account');
        expect($user->fresh())->not->toBeNull();
        expect($permit->fresh())->not->toBeNull();
        $this->assertAuthenticatedAs($user);
    }
});

it('does not accept an admin role from public registration', function () {
    $this->post(route('register'), [
        'name' => 'Pemohon Baru', 'email' => 'baru@example.test',
        'password' => 'password', 'password_confirmation' => 'password', 'role' => 'humas',
    ])->assertSessionHasNoErrors();
    expect(User::where('email', 'baru@example.test')->firstOrFail()->role)->toBe('pemohon');
});
