<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\PermitSubmitted;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use App\Models\WorkPermit;
use App\Services\WorkLocationZoneResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkPermitController extends Controller
{
    public function index(Request $request)
    {
        $query = WorkPermit::where('user_id', $request->user()->id);
        $counts = (clone $query)->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')->pluck('total', 'status');
        $permits = \App\Services\PermitGroups::query($query)->orderByDesc('id')->paginate(20);

        return view('permits.index', compact('permits', 'counts'));
    }

    public function create()
    {
        return view('permits.create');
    }

    public function store(Request $request)
    {
        abort_unless(
            $request->user()->role === 'pemohon',
            403
        );

        $validated = $request->validate([
            'submission_token' => ['required', 'uuid'],
            'company_name' => ['required', 'string', 'max:120'],
            'work_location' => ['required', 'string', 'max:255'],
            'job_title' => ['required', 'string', 'max:80'],
            'work_description' => ['required', 'string', 'max:5000'],
            'work_starts_at' => ['required', 'date_format:Y-m-d'],
            'work_ends_at' => ['required', 'date_format:Y-m-d', 'after_or_equal:work_starts_at'],
            'foreman_name' => ['required', 'string', 'max:80'],
            'foreman_photo' => ['required', 'image', 'mimes:jpg,jpeg,png', 'extensions:jpg,jpeg,png', 'max:102400', 'dimensions:max_width=6000,max_height=6000'],
            'worker_count' => ['required', 'integer', 'min:1'],
            'workers' => ['required', 'array', 'min:1'],
            'workers.*.name' => ['required', 'string', 'max:80'],
            'workers.*.photo' => ['required', 'image', 'mimes:jpg,jpeg,png', 'extensions:jpg,jpeg,png', 'max:102400', 'dimensions:max_width=6000,max_height=6000'],
        ]);
        if (count($validated['workers']) !== (int) $validated['worker_count']) {
            throw \Illuminate\Validation\ValidationException::withMessages(['worker_count' => 'Jumlah pekerja harus sama dengan jumlah isian pekerja.']);
        }
        $totalBytes = $request->file('foreman_photo')->getSize();
        foreach ($validated['workers'] as $index => $worker) {
            $totalBytes += $request->file('workers.' . $index . '.photo')->getSize();
        }
        if ($totalBytes > 100 * 1024 * 1024) {
            throw \Illuminate\Validation\ValidationException::withMessages(['workers' => 'Total foto maksimal 100 MB per pengajuan.']);
        }
        $zoneKey = app(WorkLocationZoneResolver::class)->resolve($validated['work_location']);
        $zone = $zoneKey ? config('mitra.zones.' . $zoneKey) : null;
        $group = (string) \Illuminate\Support\Str::uuid();
        $paths = [];
        try {
            $created = DB::transaction(function () use ($request, $validated, $zoneKey, $zone, $group, &$paths) {
                // Serialize submissions for this applicant; the unique key is a second guard.
                User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
                if (DB::table('permit_submissions')->where('user_id', $request->user()->id)->where('token', $validated['submission_token'])->exists()) {
                    return false;
                }
                DB::table('permit_submissions')->insert([
                    'user_id' => $request->user()->id, 'token' => $validated['submission_token'],
                    'group_id' => $group, 'created_at' => now(),
                ]);
                $people = [['name' => $validated['foreman_name'], 'photo' => $request->file('foreman_photo'), 'type' => 'mandor']];
                foreach ($validated['workers'] as $index => $worker) {
                    $people[] = ['name' => $worker['name'], 'photo' => $request->file('workers.' . $index . '.photo'), 'type' => 'pekerja'];
                }
                foreach ($people as $person) {
                    $path = $person['photo']->store('mitra/' . $group, 'local');
                    if (!$path) {
                        throw new \RuntimeException('Foto gagal disimpan.');
                    }
                    $paths[] = $path;
                    $permit = $request->user()->workPermits()->create([
                        'group_id' => $group,
                        'person_type' => $person['type'],
                        'foreman_name' => $validated['foreman_name'],
                        'full_name' => $person['name'],
                        'company_name' => $validated['company_name'],
                        'job_title' => $person['type'] === 'mandor' ? 'Mandor' : $validated['job_title'],
                        'work_location' => $validated['work_location'],
                        'zone_key' => $zoneKey,
                        'zone_color' => $zone['color'] ?? null,
                        'work_description' => $validated['work_description'],
                        'photo_path' => $path,
                        'start_date' => substr($validated['work_starts_at'], 0, 10),
                        'end_date' => substr($validated['work_ends_at'], 0, 10),
                        'work_starts_at' => $validated['work_starts_at'] . ' 00:00:00',
                        'work_ends_at' => $validated['work_ends_at'] . ' 00:00:00',
                        'status' => 'ditinjau',
                    ]);
                    foreach (['humas', 'k3', 'keamanan'] as $role) {
                        $permit->approvals()->create(['admin_role' => $role, 'status' => 'menunggu']);
                    }
                }
                Notification::send(
                    User::whereIn('role', ['humas', 'k3', 'keamanan'])->get(),
                    new PermitSubmitted($group, $validated['company_name'], count($validated['workers']) + 1)
                );
                return true;
            });
        } catch (\Throwable $exception) {
            \Illuminate\Support\Facades\Storage::disk('local')->delete($paths);
            throw $exception;
        }

        if (!$created) {
            return redirect()->route('permits.index')->with('success', 'Pengajuan ini sudah diterima sebelumnya.');
        }

        return redirect()->route('permits.index')->with(
            'success',
            'Pengajuan kelompok berhasil: ' . count($validated['workers']) . ' pekerja dan 1 PIC.'
        );
    }

    public function show(Request $request, WorkPermit $workPermit)
    {
        $user = $request->user();

        abort_unless(
            in_array($user->role, [
                'pemohon',
                'humas',
                'k3',
                'keamanan',
            ], true),
            403
        );

        // Pemohon hanya boleh melihat permohonannya sendiri.
        if ($user->role === 'pemohon') {
            abort_unless(
                $workPermit->user_id === $user->id,
                403
            );
        }

        $membersQuery = WorkPermit::query()->where('user_id', $workPermit->user_id);
        if ($workPermit->group_id !== null) {
            $membersQuery->where('group_id', $workPermit->group_id);
        } else {
            $membersQuery->whereKey($workPermit->id);
        }
        $memberCounts = (clone $membersQuery)->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')->pluck('total', 'status');
        $jobTitle = (clone $membersQuery)->where('person_type', 'pekerja')->orderBy('id')->value('job_title')
            ?? $workPermit->job_title;
        $pic = (clone $membersQuery)->where('person_type', 'mandor')->orderBy('id')->value('full_name')
            ?? $workPermit->foreman_name;
        $members = $membersQuery->with('approvals.admin')
            ->orderByRaw("CASE WHEN person_type = 'mandor' THEN 0 ELSE 1 END")
            ->orderBy('id')->paginate(50)->withQueryString();

        return view('permits.show', compact('workPermit', 'members', 'memberCounts', 'jobTitle', 'pic'));
    }

    public function photo(Request $request, WorkPermit $workPermit)
    {
        $user = $request->user();

        // Batasi role yang dapat mengakses foto.
        abort_unless(
            in_array($user->role, [
                'pemohon',
                'humas',
                'k3',
                'keamanan',
                'operator',
            ], true),
            403
        );

        // Pemohon hanya boleh melihat foto miliknya.
        if ($user->role === 'pemohon') {
            abort_unless(
                (int) $workPermit->user_id === (int) $user->id,
                403
            );
        }

        // Operator hanya boleh melihat foto izin yang disetujui.
        if ($user->role === 'operator') {
            abort_unless(
                $workPermit->status === 'diizinkan',
                403
            );
        }

        $path = $workPermit->photo_path;

        abort_unless($path, 404);

        $disk = Storage::disk('local');

        abort_unless($disk->exists($path), 404);

        $absolutePath = $disk->path($path);

        return response()->file($absolutePath, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
