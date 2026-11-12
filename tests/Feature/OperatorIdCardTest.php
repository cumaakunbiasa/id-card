<?php

use App\Models\User;
use App\Models\WorkPermit;

function createOperatorIdCardPermit(User $owner, string $status, string $fullName): WorkPermit
{
    return $owner->workPermits()->create([
        'full_name' => $fullName,
        'company_name' => 'PT Contoh Energi',
        'job_title' => 'Teknisi',
        'work_location' => 'Area Gardu Induk',
        'work_description' => 'Pemeriksaan dan pemeliharaan peralatan.',
        'start_date' => '2026-10-28',
        'end_date' => '2026-10-30',
        'status' => $status,
    ]);
}

it('shows only approved and not-yet-printed permits in the operator list', function () {
    $operator = User::factory()->create(['role' => 'operator']);
    $owner = User::factory()->create(['role' => 'pemohon']);

    $ready = createOperatorIdCardPermit($owner, 'diizinkan', 'Pekerja Siap Cetak 123');
    $printed = createOperatorIdCardPermit($owner, 'diizinkan', 'Pekerja Sudah Cetak 456');
    $printed->forceFill(['printed_at' => now()])->save();
    createOperatorIdCardPermit($owner, 'ditinjau', 'Pekerja Masih Ditinjau 789');
    createOperatorIdCardPermit($owner, 'ditolak', 'Pekerja Ditolak 987');

    $this->actingAs($operator)
        ->get(route('operator.id-cards.index'))
        ->assertOk()
        ->assertSee($ready->full_name)
        ->assertDontSee($printed->full_name)
        ->assertDontSee('Pekerja Masih Ditinjau 789')
        ->assertDontSee('Pekerja Ditolak 987')
        ->assertSee(route('operator.id-cards.printed'));
});

it('keeps zone selection in the operator preview without custom color controls', function () {
    $operator = User::factory()->create(['role' => 'operator']);
    $owner = User::factory()->create(['role' => 'pemohon']);
    $permit = createOperatorIdCardPermit($owner, 'diizinkan', 'Pekerja Layak Cetak');

    $this->actingAs($operator)
        ->get(route('operator.id-cards.show', $permit))
        ->assertOk()
        ->assertSee('Pekerja Layak Cetak')
        ->assertSee('window.print()')
        ->assertSee('size: 55mm 85mm', false)
        ->assertSee('Pilih Zona Header')
        ->assertSee('name="header_zone"', false)
        ->assertSee('ZONA HIJAU')
        ->assertSee('ZONA BIRU')
        ->assertSee('ZONA KUNING')
        ->assertSee('ZONA MERAH')
        ->assertSee('onchange="this.form.submit()"', false)
        ->assertSee('Tandai Sudah Dicetak')
        ->assertDontSee('Warna khusus')
        ->assertDontSee('type="color"', false)
        ->assertDontSee('Simpan warna header')
        ->assertDontSee('Pengaturan warna header');
});

it('does not expose a print preview for a permit that is not approved', function () {
    $operator = User::factory()->create(['role' => 'operator']);
    $owner = User::factory()->create(['role' => 'pemohon']);
    $permit = createOperatorIdCardPermit($owner, 'ditinjau', 'Pekerja Belum Disetujui');

    $this->actingAs($operator)
        ->get(route('operator.id-cards.show', $permit))
        ->assertNotFound();
});

it('denies non-operators access to the operator ID card list', function () {
    $applicant = User::factory()->create(['role' => 'pemohon']);

    $this->actingAs($applicant)
        ->get(route('operator.id-cards.index'))
        ->assertForbidden();
});

it('uses the configured automatic zone color for a detected applicant location', function () {
    $operator = User::factory()->create(['role' => 'operator']);
    $owner = User::factory()->create(['role' => 'pemohon']);
    $permit = createOperatorIdCardPermit($owner, 'diizinkan', 'Pekerja Parikesit');
    $permit->update([
        'work_location' => 'Parikesit',
        'zone_key' => 'zona-merah',
        'zone_color' => '#155e75',
    ]);

    $this->actingAs($operator)
        ->get(route('operator.id-cards.show', $permit))
        ->assertOk()
        ->assertSee('#dc2626')
        ->assertSee('ZONA MERAH');
});

it('lets the operator choose a configured zone and applies its color immediately', function () {
    $operator = User::factory()->create(['role' => 'operator']);
    $owner = User::factory()->create(['role' => 'pemohon']);
    $permit = createOperatorIdCardPermit($owner, 'diizinkan', 'Pekerja Pilih Zona B');

    $this->actingAs($operator)
        ->patch(route('operator.id-cards.update-zone', $permit), [
            'header_zone' => 'zona-biru',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('operator.id-cards.show', $permit));

    expect($permit->fresh()->id_card_header_color)->toBe(config('mitra.zones.zona-biru.color'));

    $this->get(route('operator.id-cards.show', $permit))
        ->assertOk()
        ->assertSee('--zone:' . config('mitra.zones.zona-biru.color'), false)
        ->assertSee('background-color: ' . config('mitra.zones.zona-biru.color'), false)
        ->assertSee('value="zona-biru" selected', false);
});

it('lets the operator select ZONA MERAH', function () {
    $operator = User::factory()->create(['role' => 'operator']);
    $owner = User::factory()->create(['role' => 'pemohon']);
    $permit = createOperatorIdCardPermit($owner, 'diizinkan', 'Pekerja Ikuti Zona Lokasi');
    $permit->forceFill([
        'zone_key' => 'zona-merah',
        'zone_color' => '#155e75',
        'id_card_header_color' => config('mitra.zones.zona-biru.color'),
    ])->save();

    $this->actingAs($operator)
        ->patch(route('operator.id-cards.update-zone', $permit), [
            'header_zone' => 'zona-merah',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('operator.id-cards.show', $permit));

    expect($permit->fresh()->id_card_header_color)->toBe(config('mitra.zones.zona-merah.color'));

    $this->get(route('operator.id-cards.show', $permit))
        ->assertOk()
        ->assertSee('--zone:#dc2626', false)
        ->assertSee('value="zona-merah" selected', false);
});

it('rejects a zone that is not configured', function () {
    $operator = User::factory()->create(['role' => 'operator']);
    $owner = User::factory()->create(['role' => 'pemohon']);
    $permit = createOperatorIdCardPermit($owner, 'diizinkan', 'Pekerja Zona Tidak Valid');

    $this->actingAs($operator)
        ->patch(route('operator.id-cards.update-zone', $permit), [
            'header_zone' => 'zona-f',
        ])
        ->assertSessionHasErrors('header_zone');

    expect($permit->fresh()->id_card_header_color)->toBe(config('mitra.zones.zona-merah.color'));
});

it('shows the company, job title, location, unique id, and date-only schedule in the card preview', function () {
    $operator = User::factory()->create(['role' => 'operator']);
    $owner = User::factory()->create(['role' => 'pemohon']);
    $permit = createOperatorIdCardPermit($owner, 'diizinkan', 'Pekerja Kartu Lengkap');
    $permit->update([
        'work_starts_at' => '2026-10-28 08:00:00',
        'work_ends_at' => '2026-10-30 17:30:00',
        'zone_color' => '#155e75',
        'photo_path' => 'mitra/preview/pekerja.jpg',
    ]);

    $this->actingAs($operator)
        ->get(route('operator.id-cards.show', $permit))
        ->assertOk()
        ->assertSee('SISTEM PENGAJUAN ID CARD')
        ->assertSee('Tanggal mulai')
        ->assertSee('Tanggal selesai')
        ->assertSee('PT: PT Contoh Energi')
        ->assertSee('Jabatan: Teknisi')
        ->assertSee('Lokasi kerja')
        ->assertSee('Pekerja Kartu Lengkap')
        ->assertSee('PT Contoh Energi')
        ->assertSee('Teknisi')
        ->assertSee('Area Gardu Induk')
        ->assertSee('IK-' . str_pad((string) $permit->id, 6, '0', STR_PAD_LEFT))
        ->assertSee('28/10/2026')
        ->assertSee('30/10/2026')
        ->assertDontSee('08:00')
        ->assertDontSee('17:30')
        ->assertSee(route('permits.photo', $permit));
});

it('marks an approved card as printed and moves it to the printed cards list', function () {
    $operator = User::factory()->create(['role' => 'operator']);
    $owner = User::factory()->create(['role' => 'pemohon']);
    $permit = createOperatorIdCardPermit($owner, 'diizinkan', 'Pekerja Baru Dicetak');

    $this->actingAs($operator)
        ->patch(route('operator.id-cards.mark-printed', $permit))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('operator.id-cards.show', $permit));

    expect($permit->fresh()->printed_at)->not->toBeNull();

    $this->get(route('operator.id-cards.index'))
        ->assertOk()
        ->assertDontSee($permit->full_name);

    $this->get(route('operator.id-cards.printed'))
        ->assertOk()
        ->assertSee('Kartu Sudah Dicetak')
        ->assertSee($permit->full_name)
        ->assertSee('Sudah dicetak');

    $this->get(route('operator.id-cards.show', $permit))
        ->assertOk()
        ->assertSee('Sudah dicetak pada')
        ->assertDontSee('Tandai Sudah Dicetak');
});

it('does not overwrite the original print timestamp when a card is marked printed again', function () {
    $operator = User::factory()->create(['role' => 'operator']);
    $owner = User::factory()->create(['role' => 'pemohon']);
    $printedAt = now()->subDay()->startOfMinute();
    $permit = createOperatorIdCardPermit($owner, 'diizinkan', 'Pekerja Cetak Ulang');
    $permit->forceFill(['printed_at' => $printedAt])->save();

    $this->actingAs($operator)
        ->patch(route('operator.id-cards.mark-printed', $permit))
        ->assertRedirect(route('operator.id-cards.show', $permit));

    expect($permit->fresh()->printed_at->equalTo($printedAt))->toBeTrue();
});

it('shows only approved permits with a print timestamp in the printed cards page', function () {
    $operator = User::factory()->create(['role' => 'operator']);
    $owner = User::factory()->create(['role' => 'pemohon']);

    $printed = createOperatorIdCardPermit($owner, 'diizinkan', 'Kartu Sudah Dicetak 123');
    $printedAt = now()->startOfMinute();
    $printed->forceFill(['printed_at' => $printedAt])->save();
    createOperatorIdCardPermit($owner, 'diizinkan', 'Kartu Belum Dicetak 456');
    $unapproved = createOperatorIdCardPermit($owner, 'ditinjau', 'Kartu Belum Disetujui 789');
    $unapproved->forceFill(['printed_at' => now()])->save();

    $this->actingAs($operator)
        ->get(route('operator.id-cards.printed'))
        ->assertOk()
        ->assertSee($printed->full_name)
        ->assertSee($printedAt->format('d/m/Y H:i'))
        ->assertDontSee('Kartu Belum Dicetak 456')
        ->assertDontSee($unapproved->full_name);
});

it('labels the applicant work area as the work location', function () {
    $applicant = User::factory()->create(['role' => 'pemohon']);

    $this->actingAs($applicant)
        ->get(route('permits.create'))
        ->assertOk()
        ->assertSee('Lokasi kerja')
        ->assertSee('type="text"', false)
        ->assertSee('name="work_location"', false)
        ->assertSee('type="date"', false)
        ->assertDontSee('datetime-local');
});

it('denies non-operators and rejects marking an unapproved permit as printed', function () {
    $owner = User::factory()->create(['role' => 'pemohon']);
    $approved = createOperatorIdCardPermit($owner, 'diizinkan', 'Pekerja Terlindungi');
    $pending = createOperatorIdCardPermit($owner, 'ditinjau', 'Pekerja Belum Disetujui');
    $applicant = User::factory()->create(['role' => 'pemohon']);
    $operator = User::factory()->create(['role' => 'operator']);

    $this->actingAs($applicant)
        ->patch(route('operator.id-cards.mark-printed', $approved))
        ->assertForbidden();

    $this->actingAs($applicant)
        ->patch(route('operator.id-cards.update-zone', $approved), [
            'header_zone' => 'zona-biru',
        ])
        ->assertForbidden();

    $this->actingAs($operator)
        ->patch(route('operator.id-cards.mark-printed', $pending))
        ->assertNotFound();

    $this->actingAs($operator)
        ->patch(route('operator.id-cards.update-zone', $pending), [
            'header_zone' => 'zona-biru',
        ])
        ->assertNotFound();

    expect($approved->fresh()->printed_at)->toBeNull();
    expect($pending->fresh()->printed_at)->toBeNull();
    expect($approved->fresh()->id_card_header_color)->toBeNull();
    expect($pending->fresh()->id_card_header_color)->toBeNull();
});
