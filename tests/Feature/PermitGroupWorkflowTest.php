<?php

use App\Models\User;
use App\Models\WorkPermit;
use App\Services\PermitGroups;
use Illuminate\Support\Str;

function workflowGroup(User $owner, ?string $groupId = null): array
{
    $groupId ??= (string) Str::uuid();
    $people = [];
    foreach (['mandor' => 'PIC Kelompok', 'pekerja' => 'Pekerja Kelompok'] as $type => $name) {
        $permit = $owner->workPermits()->create([
            'group_id' => $groupId, 'person_type' => $type, 'foreman_name' => 'PIC Kelompok',
            'full_name' => $name, 'company_name' => 'PT Kelompok',
            'job_title' => $type === 'mandor' ? 'Mandor' : 'Pemeliharaan Panel',
            'work_location' => 'Zona A', 'work_description' => 'Pemeriksaan panel listrik',
            'start_date' => '2026-11-10', 'end_date' => '2026-11-10', 'status' => 'ditinjau',
        ]);
        foreach (['humas', 'k3', 'keamanan'] as $role) {
            $permit->approvals()->create(['admin_role' => $role, 'status' => 'menunggu']);
        }
        $people[] = $permit;
    }
    return $people;
}

it('lists one job per group and expands its people only in details', function (string $role) {
    $owner = User::factory()->create(['role' => 'pemohon']);
    [$pic, $worker] = workflowGroup($owner);
    $viewer = $role === 'pemohon' ? $owner : User::factory()->create(['role' => $role]);
    $this->actingAs($viewer)->get(route('dashboard'))->assertOk()
        ->assertSee('Pemeliharaan Panel')->assertSee('PIC Kelompok')->assertSee('PT Kelompok')
        ->assertDontSee('Pekerja Kelompok')
        ->assertViewHas('recentPermits', fn ($rows) => $rows->count() === 1 && (int) $rows->first()->people_count === 2);
    $route = $role === 'pemohon' ? 'permits.index' : 'admin.permohonan.index';
    $this->get(route($route))->assertOk()->assertDontSee('Pekerja Kelompok')
        ->assertDontSee('Izinkan orang ini')
        ->assertViewHas('permits', fn ($rows) => $rows->total() === 1);
    $response = $this->get(route('permits.show', $worker))->assertOk()
        ->assertSeeInOrder(['PIC Kelompok', 'Pekerja Kelompok'])
        ->assertSee('Pemeriksaan panel listrik')->assertSee('10/11/2026')->assertSee('Zona A')
        ->assertViewHas('members', fn ($rows) => $rows->first()->id === $pic->id && $rows->total() === 2);
    if ($role === 'pemohon') {
        $response->assertDontSee('Izinkan orang ini');
    } else {
        $response->assertSee('Izinkan orang ini')->assertSee('Tolak orang ini');
    }
})->with(['pemohon', 'humas', 'k3', 'keamanan']);

it('rejects only the selected person and keeps their group actionable', function (string $role, int $targetIndex) {
    $owner = User::factory()->create(['role' => 'pemohon']);
    $members = workflowGroup($owner);
    $target = $members[$targetIndex]; $other = $members[1 - $targetIndex];
    $reviewer = User::factory()->create(['role' => $role]);
    $this->actingAs($reviewer)
        ->from(route('permits.show', $members[0]))
        ->post(route('admin.permohonan.decide', [$target, 'tolak']), ['notes' => 'Dokumen orang ini belum lengkap'])
        ->assertSessionHasNoErrors()->assertRedirect(route('permits.show', $members[0]));
    expect($target->fresh()->status)->toBe('ditolak');
    expect($other->fresh()->status)->toBe('ditinjau');
    expect($other->approvals()->where('status', 'menunggu')->count())->toBe(3);
    $this->get(route('admin.permohonan.index'))->assertOk()
        ->assertViewHas('permits', fn ($rows) => $rows->total() === 1
            && (int) $rows->first()->pending_for_role === 1
            && (int) $rows->first()->rejected_people === 1);
    foreach (['humas', 'k3', 'keamanan'] as $approverRole) {
        $approver = $approverRole === $role ? $reviewer : User::factory()->create(['role' => $approverRole]);
        $this->actingAs($approver)->post(route('admin.permohonan.decide', [$other, 'setuju']))->assertSessionHasNoErrors();
    }
    expect($other->fresh()->status)->toBe('diizinkan');
    expect($target->fresh()->status)->toBe('ditolak');
    $this->actingAs($reviewer)->get(route('admin.permohonan.index'))
        ->assertViewHas('permits', fn ($rows) => $rows->total() === 0);
})->with(['humas', 'k3', 'keamanan'])->with([0, 1]);

it('removes a group from one reviewers queue only after all their actionable people are decided', function () {
    $members = workflowGroup(User::factory()->create());
    $reviewer = User::factory()->create(['role' => 'humas']);
    $this->actingAs($reviewer);
    foreach ($members as $index => $member) {
        $this->post(route('admin.permohonan.decide', [$member, 'setuju']))->assertSessionHasNoErrors();
        $this->get(route('admin.permohonan.index'))
            ->assertViewHas('permits', fn ($rows) => $rows->total() === ($index === 0 ? 1 : 0));
    }
    $this->actingAs(User::factory()->create(['role' => 'k3']))->get(route('admin.permohonan.index'))
        ->assertViewHas('permits', fn ($rows) => $rows->total() === 1 && (int) $rows->first()->pending_for_role === 2);
});

it('isolates applicant groups and forbids viewing another owners details', function () {
    $owner = User::factory()->create(); $other = User::factory()->create();
    $groupId = (string) Str::uuid();
    [$pic] = workflowGroup($owner, $groupId);
    [$foreignPic] = workflowGroup($other, $groupId);
    $this->actingAs($owner)->get(route('permits.index'))
        ->assertViewHas('permits', fn ($rows) => $rows->total() === 1 && (int) $rows->first()->people_count === 2);
    $this->get(route('permits.show', $pic))
        ->assertViewHas('members', fn ($rows) => $rows->total() === 2 && $rows->every(fn ($p) => $p->user_id === $owner->id));
    $this->get(route('permits.show', $foreignPic))->assertForbidden();
});

it('keeps old ungrouped permits separate and paginates groups instead of people', function () {
    $owner = User::factory()->create();
    for ($i = 0; $i < 21; $i++) { workflowGroup($owner); }
    $this->actingAs($owner)->get(route('permits.index', ['page' => 2]))
        ->assertViewHas('permits', fn ($rows) => $rows->total() === 21 && $rows->count() === 1);
    $old = workflowGroup($owner);
    foreach ($old as $permit) { $permit->update(['group_id' => null]); }
    $this->get(route('permits.index'))->assertViewHas('permits', fn ($rows) => $rows->total() === 23);
    $this->get(route('permits.show', $old[0]))->assertViewHas('members', fn ($rows) => $rows->total() === 1);
});
