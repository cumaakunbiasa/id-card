<?php

use App\Models\User;
use App\Models\WorkPermit;
use Illuminate\Support\Facades\Storage;

function createSecurityPhotoPermit(
    User $owner,
    string $status = 'ditinjau'
): WorkPermit {
    $image = imagecreatetruecolor(8, 8);
    $background = imagecolorallocate($image, 30, 90, 160);
    imagefill($image, 0, 0, $background);
    ob_start();
    imagejpeg($image);
    $jpeg = ob_get_clean();
    imagedestroy($image);

    Storage::disk('local')->put('mitra/security/photo.jpg', $jpeg);

    return $owner->workPermits()->create([
        'full_name' => 'Pekerja Pengujian',
        'company_name' => 'PT Contoh',
        'job_title' => 'Teknisi',
        'work_location' => 'Zona A',
        'work_description' => 'Pengujian keamanan',
        'start_date' => '2026-11-10',
        'end_date' => '2026-11-10',
        'status' => $status,
        'photo_path' => 'mitra/security/photo.jpg',
    ]);
}

beforeEach(function () {
    Storage::fake('local');
});

it('requires email verification before accessing permit photos', function () {
    $owner = User::factory()->unverified()->create([
        'role' => 'pemohon',
    ]);

    $permit = createSecurityPhotoPermit($owner);

    $this->actingAs($owner)
        ->get(route('permits.photo', $permit))
        ->assertRedirect(route('verification.notice'));
});

it('allows the owner to view their own photo', function () {
    $owner = User::factory()->create([
        'role' => 'pemohon',
    ]);

    $permit = createSecurityPhotoPermit($owner);

    $response = $this->actingAs($owner)->get(route('mitra.photo', $permit));

    $response->assertOk()->assertHeader('Content-Type', 'image/jpeg');
    expect(substr($response->getContent(), 0, 3))->toBe("\xFF\xD8\xFF");
});

it('prevents another applicant from viewing someone elses photo', function () {
    $owner = User::factory()->create([
        'role' => 'pemohon',
    ]);

    $other = User::factory()->create([
        'role' => 'pemohon',
    ]);

    $permit = createSecurityPhotoPermit($owner);

    $this->actingAs($other)
        ->get(route('mitra.photo', $permit))
        ->assertForbidden();
});

it('allows operators to view approved worker photos', function () {
    $owner = User::factory()->create([
        'role' => 'pemohon',
    ]);

    $operator = User::factory()->create([
        'role' => 'operator',
    ]);

    $permit = createSecurityPhotoPermit($owner, 'diizinkan');

    $response = $this->actingAs($operator)->get(route('permits.photo', $permit));

    $response->assertOk()->assertHeader('Content-Type', 'image/jpeg');
    expect(substr($response->getContent(), 0, 3))->toBe("\xFF\xD8\xFF")
        ->and(getimagesizefromstring($response->getContent()))->not->toBeFalse();
});

it('prevents operators from viewing unapproved worker photos', function () {
    $owner = User::factory()->create([
        'role' => 'pemohon',
    ]);

    $operator = User::factory()->create([
        'role' => 'operator',
    ]);

    $permit = createSecurityPhotoPermit($owner);

    $this->actingAs($operator)
        ->get(route('mitra.photo', $permit))
        ->assertForbidden();
});


it('shows the private worker photo on permit details for Humas, K3, and Security', function (string $role) {
    $owner = User::factory()->create(['role' => 'pemohon']);
    $admin = User::factory()->create(['role' => $role]);
    $permit = createSecurityPhotoPermit($owner);

    $this->actingAs($admin)
        ->get(route('permits.show', $permit))
        ->assertOk()
        ->assertSee('Foto pekerja')
        ->assertSee(route('permits.photo', $permit), false);

    $response = $this->get(route('permits.photo', $permit));

    $response->assertOk()->assertHeader('Content-Type', 'image/jpeg');
    expect(substr($response->getContent(), 0, 3))->toBe("\xFF\xD8\xFF");
})->with([
    'Humas' => 'humas',
    'K3' => 'k3',
    'Keamanan' => 'keamanan',
]);
