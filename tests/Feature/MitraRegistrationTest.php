<?php
use App\Models\User;
use App\Models\WorkPermit;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function mitraPayload(int $count = 2): array {
    return [
        'company_name' => 'PT Contoh', 'work_location' => 'Parikesit', 'job_title' => 'Teknisi',
        'work_description' => 'Pemeliharaan', 'submission_token' => (string) \Illuminate\Support\Str::uuid(), 'work_starts_at' => '2026-11-01',
        'work_ends_at' => '2026-11-01', 'foreman_name' => 'Mandor Uji',
        'foreman_photo' => UploadedFile::fake()->image('mandor.jpg'), 'worker_count' => $count,
        'workers' => collect(range(1, $count))->map(fn ($i) => [
            'name' => 'Pekerja '.$i, 'photo' => UploadedFile::fake()->image('pekerja'.$i.'.jpg'),
        ])->all(),
    ];
}
it('creates ten workers and one foreman with private photos and separate approvals', function () {
    Storage::fake('local');
    $this->actingAs(User::factory()->create(['role' => 'pemohon']))
        ->post(route('permits.store'), mitraPayload(10))->assertSessionHasNoErrors()->assertRedirect(route('permits.index'));
    expect(WorkPermit::count())->toBe(11);
    expect(WorkPermit::distinct()->count('group_id'))->toBe(1);
    expect(WorkPermit::where('person_type', 'mandor')->count())->toBe(1);
    foreach (WorkPermit::all() as $permit) {
        Storage::disk('local')->assertExists($permit->photo_path);
        expect($permit->approvals()->count())->toBe(3);
        expect($permit->status)->toBe('ditinjau');
    }
});
it('stores date-only work schedules for same-day and multi-day permits', function (string $startsAt, string $endsAt) {
    Storage::fake('local');
    $data = mitraPayload(1);
    $data['work_starts_at'] = $startsAt;
    $data['work_ends_at'] = $endsAt;

    $this->actingAs(User::factory()->create(['role' => 'pemohon']))
        ->post(route('permits.store'), $data)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('permits.index'));

    expect(WorkPermit::count())->toBe(2);
    foreach (WorkPermit::all() as $permit) {
        expect($permit->work_starts_at->format('Y-m-d'))->toBe(substr($startsAt, 0, 10));
        expect($permit->work_ends_at->format('Y-m-d'))->toBe(substr($endsAt, 0, 10));
        expect($permit->work_starts_at->format('H:i'))->toBe('00:00');
        expect($permit->work_ends_at->format('H:i'))->toBe('00:00');
        expect($permit->start_date->format('Y-m-d'))->toBe(substr($startsAt, 0, 10));
        expect($permit->end_date->format('Y-m-d'))->toBe(substr($endsAt, 0, 10));
    }
})->with([
    'same day' => ['2026-11-10', '2026-11-10'],
    'multiple days' => ['2026-11-10', '2026-11-12'],
]);

it('stores the applicant-entered location and detects Parikesit as ZONA MERAH', function () {
    Storage::fake('local');
    $data = mitraPayload(1);
    $data['work_location'] = 'Area Parikesit, Kalimantan Timur';

    $this->actingAs(User::factory()->create(['role' => 'pemohon']))
        ->post(route('permits.store'), $data)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('permits.index'));

    $permit = WorkPermit::where('person_type', 'pekerja')->firstOrFail();
    expect($permit->work_location)->toBe('Area Parikesit, Kalimantan Timur')
        ->and($permit->zone_key)->toBe('zona-merah')
        ->and($permit->zone_color)->toBe('#dc2626');
});

it('keeps an unknown applicant-entered location without assigning a zone', function () {
    Storage::fake('local');
    $data = mitraPayload(1);
    $data['work_location'] = 'Lokasi Proyek yang Belum Terdaftar';

    $this->actingAs(User::factory()->create(['role' => 'pemohon']))
        ->post(route('permits.store'), $data)
        ->assertSessionHasNoErrors();

    $permit = WorkPermit::where('person_type', 'pekerja')->firstOrFail();
    expect($permit->work_location)->toBe('Lokasi Proyek yang Belum Terdaftar')
        ->and($permit->zone_key)->toBeNull()
        ->and($permit->zone_color)->toBeNull();
});

it('rejects invalid work dates without storing permits or photos', function (string $startsAt, string $endsAt, string $field) {
    Storage::fake('local');
    $data = mitraPayload(1);
    $data['work_starts_at'] = $startsAt;
    $data['work_ends_at'] = $endsAt;

    $this->actingAs(User::factory()->create(['role' => 'pemohon']))
        ->post(route('permits.store'), $data)
        ->assertSessionHasErrors($field);

    $this->assertDatabaseCount('work_permits', 0);
    $this->assertDatabaseCount('permit_approvals', 0);
    expect(Storage::disk('local')->allFiles())->toBe([]);
})->with([
    'previous day' => ['2026-11-10', '2026-11-09', 'work_ends_at'],
    'start contains a time' => ['2026-11-10T08:00', '2026-11-11', 'work_starts_at'],
    'end contains a time' => ['2026-11-10', '2026-11-11T17:00', 'work_ends_at'],
    'invalid date' => ['2026-02-30', '2026-03-01', 'work_starts_at'],
    'missing start' => ['', '2026-11-10', 'work_starts_at'],
    'missing end' => ['2026-11-10', '', 'work_ends_at'],
]);

it('rejects mismatched counts without creating partial registrations', function () {
    Storage::fake('local'); $data = mitraPayload(); $data['worker_count'] = 3;
    $this->actingAs(User::factory()->create(['role' => 'pemohon']))->post(route('permits.store'), $data)
        ->assertSessionHasErrors('worker_count');
    expect(WorkPermit::count())->toBe(0);
    expect(Storage::disk('local')->allFiles())->toBe([]);
});
it('denies photos to other applicants and to operators before approval', function () {
    Storage::fake('local'); $owner = User::factory()->create(['role' => 'pemohon']);
    $this->actingAs($owner)->post(route('permits.store'), mitraPayload(1))->assertSessionHasNoErrors();
    $permit = WorkPermit::firstOrFail();
    $this->get(route('mitra.photo', $permit))->assertOk();
    foreach (['pemohon', 'operator'] as $role) {
        $this->actingAs(User::factory()->create(['role' => $role]))->get(route('mitra.photo', $permit))->assertForbidden();
    }
});
it('denies worker export to non admins', function (string $role) {
    $this->actingAs(User::factory()->create(['role' => $role]))->get(route('admin.workers.export'))->assertForbidden();
})->with(['pemohon','operator']);
it('writes formula-like names as Excel strings', function () {
    if (!class_exists(ZipArchive::class)) $this->markTestSkipped('PHP zip required');
    $path = (new App\Services\WorkerWorkbook())->create([['Nama'], ['=1+1']]);
    try {
        $zip = new ZipArchive(); expect($zip->open($path))->toBeTrue();
        $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
        expect($xml)->toContain('t="inlineStr"')->toContain('=1+1')->not->toContain('<f>');
        $zip->close();
    } finally { unlink($path); }
});
