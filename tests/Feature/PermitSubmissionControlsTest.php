<?php

use App\Models\User;
use App\Models\WorkPermit;
use App\Notifications\PermitSubmitted;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

function submissionControlsPayload(int $count = 1): array
{
    return [
        'submission_token' => (string) Str::uuid(),
        'company_name' => 'PT Uji', 'work_location' => 'Parikesit',
        'job_title' => 'Teknisi', 'work_description' => 'Pemeliharaan',
        'work_starts_at' => '2026-11-10', 'work_ends_at' => '2026-11-10',
        'foreman_name' => 'Mandor', 'foreman_photo' => UploadedFile::fake()->image('mandor.jpg'),
        'worker_count' => $count,
        'workers' => collect(range(1, $count))->map(fn ($i) => [
            'name' => 'Pekerja '.$i, 'photo' => UploadedFile::fake()->image('pekerja-'.$i.'.png'),
        ])->all(),
    ];
}

beforeEach(function () {
    Storage::fake('local');
});

it('accepts more than ten workers', function () {
    $this->actingAs(User::factory()->create(['role' => 'pemohon']))
        ->post(route('permits.store'), submissionControlsPayload(25))
        ->assertSessionHasNoErrors()->assertRedirect(route('permits.index'));
    $this->assertDatabaseCount('work_permits', 26);
    $this->assertDatabaseCount('permit_approvals', 78);
});

it('creates one group and one email job per reviewer when a token is retried', function () {
    $applicant = User::factory()->create(['role' => 'pemohon']);
    $reviewers = collect(['humas', 'k3', 'keamanan'])->map(fn ($role) => User::factory()->create(['role' => $role]));
    User::factory()->create(['role' => 'operator']);
    $data = submissionControlsPayload();
    $this->actingAs($applicant)->post(route('permits.store'), $data)->assertSessionHasNoErrors();
    $retry = submissionControlsPayload();
    $retry['submission_token'] = $data['submission_token'];
    $this->post(route('permits.store'), $retry)->assertSessionHasNoErrors();
    $this->assertDatabaseCount('work_permits', 2);
    $this->assertDatabaseCount('permit_submissions', 1);
    $this->assertDatabaseCount('notifications', 3);
    expect(DB::table('jobs')->where('queue', 'permit-mail')->count())->toBe(3);
    expect(Storage::disk('local')->allFiles())->toHaveCount(2);
    foreach ($reviewers as $reviewer) {
        expect($reviewer->notifications()->count())->toBe(1);
    }
});

it('rejects missing or invalid submission tokens', function (string $token) {
    $data = submissionControlsPayload(); $data['submission_token'] = $token;
    $this->actingAs(User::factory()->create(['role' => 'pemohon']))
        ->post(route('permits.store'), $data)->assertSessionHasErrors('submission_token');
    $this->assertDatabaseCount('work_permits', 0);
})->with(['', 'invalid-token']);

it('enforces the total photo budget and leaves no partial data', function () {
    $data = submissionControlsPayload();
    $data['foreman_photo'] = UploadedFile::fake()->image('mandor.jpg')->size(60000);
    $data['workers'][0]['photo'] = UploadedFile::fake()->image('worker.png')->size(60000);
    $this->actingAs(User::factory()->create(['role' => 'pemohon']))
        ->post(route('permits.store'), $data)->assertSessionHasErrors('workers');
    $this->assertDatabaseCount('work_permits', 0);
    $this->assertDatabaseCount('permit_submissions', 0);
    expect(Storage::disk('local')->allFiles())->toBe([]);
});

it('accepts exactly 100 MB total', function () {
    $data = submissionControlsPayload();
    $data['foreman_photo'] = UploadedFile::fake()->image('mandor.jpg')->size(51200);
    $data['workers'][0]['photo'] = UploadedFile::fake()->image('worker.png')->size(51200);
    $this->actingAs(User::factory()->create(['role' => 'pemohon']))
        ->post(route('permits.store'), $data)->assertSessionHasNoErrors();
    $this->assertDatabaseCount('work_permits', 2);
});

it('rejects disallowed photo extensions', function (string $extension) {
    $data = submissionControlsPayload();
    // Valid image content with a disallowed filename must still be rejected.
    $data['foreman_photo'] = UploadedFile::fake()->image('photo.'.$extension);
    $this->actingAs(User::factory()->create(['role' => 'pemohon']))
        ->post(route('permits.store'), $data)->assertSessionHasErrors('foreman_photo');
    $this->assertDatabaseCount('work_permits', 0);
})->with(['gif', 'webp', 'txt']);

it('renders reviewer email with a protected review link', function () {
    $reviewer = User::factory()->create(['role' => 'k3']);
    $notification = new PermitSubmitted((string) Str::uuid(), 'PT Uji', 26);
    expect($notification->via($reviewer))->toBe(['database', 'mail']);
    expect($notification->viaConnections())->toBe(['database' => 'sync', 'mail' => 'permit_mail']);
    $mail = $notification->toMail($reviewer);
    expect($mail->subject)->toContain('PT Uji');
    expect($mail->actionUrl)->toBe(route('admin.permohonan.index'));
});

it('allows only the recipient to mark a notification as read', function () {
    $owner = User::factory()->create(['role' => 'humas']);
    $other = User::factory()->create(['role' => 'k3']);
    $id = (string) Str::uuid();
    $owner->notifications()->create(['id' => $id, 'type' => PermitSubmitted::class, 'data' => ['title' => 'Pengajuan baru']]);
    $this->actingAs($other)->patch(route('notifications.read', $id))->assertNotFound();
    $this->actingAs($owner)->get(route('notifications.index'))->assertOk()->assertSee('Pengajuan baru');
    $this->patch(route('notifications.read', $id))->assertRedirect();
    expect($owner->notifications()->findOrFail($id)->read_at)->not->toBeNull();
});


it('rolls back the submission and photos if notification persistence fails', function () {
    \Illuminate\Support\Facades\Notification::shouldReceive('send')->once()->andThrow(new \RuntimeException('Queue unavailable'));
    $this->withoutExceptionHandling();
    try {
        $this->actingAs(User::factory()->create(['role' => 'pemohon']))
            ->post(route('permits.store'), submissionControlsPayload());
        $this->fail('Expected notification persistence failure');
    } catch (\RuntimeException $exception) {
        expect($exception->getMessage())->toBe('Queue unavailable');
    }
    $this->assertDatabaseCount('work_permits', 0);
    $this->assertDatabaseCount('permit_submissions', 0);
    $this->assertDatabaseCount('permit_approvals', 0);
    expect(Storage::disk('local')->allFiles())->toBe([]);
});
