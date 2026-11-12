<?php

use App\Models\User;

it('renders each role dashboard with real summary data', function (string $role) {
    $this->actingAs(User::factory()->create(['role' => $role]))
        ->get(route('dashboard'))->assertOk()
        ->assertViewHasAll(['counts', 'pending', 'recentPermits'])
        ->assertSee($role === 'operator' ? 'Pendaftaran terbaru' : 'Pekerjaan terbaru')
        ->assertHeader('X-Content-Type-Options', 'nosniff');
})->with(['pemohon', 'humas', 'k3', 'keamanan', 'operator']);

it('marks authenticated pages as not cacheable', function () {
    $response = $this->actingAs(User::factory()->create(['role' => 'pemohon']))->get(route('dashboard'));
    $response->assertOk();
    expect($response->headers->get('Cache-Control'))->toContain('no-store');
});

it('keeps applicant dashboards scoped to the logged-in account', function () {
    $owner = User::factory()->create(['role' => 'pemohon']);
    $other = User::factory()->create(['role' => 'pemohon']);
    $other->workPermits()->create([
        'full_name' => 'Data Pengguna Lain', 'company_name' => 'PT Uji',
        'job_title' => 'Teknisi', 'work_location' => 'Gardu', 'work_description' => 'Perawatan',
        'start_date' => '2026-10-29', 'end_date' => '2026-10-30', 'status' => 'ditinjau',
    ]);
    $this->actingAs($owner)->get(route('dashboard'))->assertOk()
        ->assertDontSee('Data Pengguna Lain')
        ->assertViewHas('counts', fn ($counts) => $counts->sum() === 0);
});

it('provides a friendly missing page', function () {
    $this->get('/missing-release-test')->assertNotFound()->assertSee('Halaman tidak ditemukan');
});
