<?php

use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\WorkPermit;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('sends one notification to each approval role when a permit group is submitted', function () {
    Storage::fake('local');

    $applicant = User::factory()->create([
        'role' => 'pemohon',
    ]);

    $humas = User::factory()->create([
        'role' => 'humas',
    ]);

    $k3 = User::factory()->create([
        'role' => 'k3',
    ]);

    $keamanan = User::factory()->create([
        'role' => 'keamanan',
    ]);

    $this->actingAs($applicant)
        ->post(route('permits.store'), [
            'company_name' => 'PT Contoh',
            'work_location' => 'Parikesit',
            'job_title' => 'Teknisi',
            'work_description' => 'Pemeliharaan',
            'submission_token' => (string) \Illuminate\Support\Str::uuid(), 'work_starts_at' => '2026-11-10',
            'work_ends_at' => '2026-11-10',
            'foreman_name' => 'Mandor Uji',
            'foreman_photo' => UploadedFile::fake()->image('mandor.jpg'),
            'worker_count' => 1,
            'workers' => [
                [
                    'name' => 'Pekerja Uji',
                    'photo' => UploadedFile::fake()->image('pekerja.jpg'),
                ],
            ],
        ])
        ->assertRedirect(route('permits.index'));

    expect(WorkPermit::count())->toBe(2);

    $this->assertDatabaseHas('notifications', [
        'notifiable_id' => $humas->id,
        'notifiable_type' => User::class,
    ]);

    $this->assertDatabaseHas('notifications', [
        'notifiable_id' => $k3->id,
        'notifiable_type' => User::class,
    ]);

    $this->assertDatabaseHas('notifications', [
        'notifiable_id' => $keamanan->id,
        'notifiable_type' => User::class,
    ]);

    expect(
        DB::table('notifications')->count()
    )->toBe(3);
});
