
<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function validPermitUploadPayload(): array
{
    return [
        'company_name' => 'PT Pengujian',
        'work_location' => 'Parikesit',
        'job_title' => 'Teknisi',
        'work_description' => 'Pengujian keamanan upload',
        'submission_token' => (string) \Illuminate\Support\Str::uuid(), 'work_starts_at' => '2026-11-10',
        'work_ends_at' => '2026-11-10',
        'foreman_name' => 'Mandor Pengujian',
        'foreman_photo' => UploadedFile::fake()->image('mandor.jpg'),
        'worker_count' => 1,
        'workers' => [
            [
                'name' => 'Pekerja Pengujian',
                'photo' => UploadedFile::fake()->image('pekerja.jpg'),
            ],
        ],
    ];
}

beforeEach(function () {
    Storage::fake('local');
});

it('rejects PDF uploads as foreman photos', function () {
    $data = validPermitUploadPayload();

    $data['foreman_photo'] = UploadedFile::fake()->create(
        'dokumen.pdf',
        100,
        'application/pdf'
    );
    $applicant = User::factory()->create([
        'role' => 'pemohon',
    ]);
    $this->actingAs($applicant)
        ->post(route('permits.store'), $data)
        ->assertSessionHasErrors('foreman_photo');

    $this->assertDatabaseCount('work_permits', 0);
});

it('rejects SVG uploads as worker photos', function () {
    $data = validPermitUploadPayload();

    $data['workers'][0]['photo'] = UploadedFile::fake()->create(
        'gambar.svg',
        100,
        'image/svg+xml'
    );
    $applicant = User::factory()->create([
        'role' => 'pemohon',
    ]);
    $this->actingAs($applicant)
        ->post(route('permits.store'), $data)
        ->assertSessionHasErrors('workers.0.photo');

    $this->assertDatabaseCount('work_permits', 0);
});

it('rejects PHP files disguised as JPG', function () {
    $data = validPermitUploadPayload();

    $data['foreman_photo'] = UploadedFile::fake()->create(
        'script.jpg',
        100,
        'text/plain'
    );
    $applicant = User::factory()->create([
        'role' => 'pemohon',
    ]);
    $this->actingAs($applicant)
        ->post(route('permits.store'), $data)
        ->assertSessionHasErrors('foreman_photo');

    $this->assertDatabaseCount('work_permits', 0);
});

it('rejects photos larger than 100 MB', function () {
    $data = validPermitUploadPayload();

    $data['foreman_photo'] = UploadedFile::fake()
        ->image('large.jpg')
        ->size(102401);
    $applicant = User::factory()->create([
        'role' => 'pemohon',
    ]);
    $this->actingAs($applicant)
        ->post(route('permits.store'), $data)
        ->assertSessionHasErrors('foreman_photo');

    $this->assertDatabaseCount('work_permits', 0);
});

it('rejects invalid worker photo formats', function () {
    $data = validPermitUploadPayload();
    $applicant = User::factory()->create([
        'role' => 'pemohon',
    ]);
    $data['workers'][0]['photo'] = UploadedFile::fake()->create(
        'dokumen.zip',
        100,
        'application/zip'
    );

    $this->actingAs($applicant)
        ->post(route('permits.store'), $data)
        ->assertSessionHasErrors('workers.0.photo');

    $this->assertDatabaseCount('work_permits', 0);
});
