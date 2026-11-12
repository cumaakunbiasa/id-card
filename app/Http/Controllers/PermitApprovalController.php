<?php

namespace App\Http\Controllers;

use App\Notifications\PermitDecisionUpdated;
use App\Notifications\PermitGroupDecisionSummary;
use App\Models\WorkPermit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PermitApprovalController extends Controller
{
    public function index(Request $request)
    {
        $role = $request->user()->role;

        abort_unless(
            in_array($role, ['humas', 'k3', 'keamanan'], true),
            403
        );

        // Include every member in the summary, but only groups with actionable members in the queue.
        $permits = \App\Services\PermitGroups::query(WorkPermit::query(), $role)
            ->having('pending_for_role', '>', 0)
            ->orderByDesc('id')->paginate(20)->withQueryString();

        return view('admin.permohonan.index', compact('permits'));
    }
    public function history(Request $request)
    {
        abort_unless(in_array($request->user()->role, ['humas', 'k3', 'keamanan'], true), 403);

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', ...($request->filled('from') ? ['after_or_equal:from'] : [])],
            'status' => ['nullable', 'in:ditinjau,diizinkan,ditolak'],
            'decision' => ['nullable', 'in:menunggu,disetujui,ditolak'],
        ]);

        $permits = WorkPermit::query()
            ->with(['user', 'approvals.admin'])
            ->when($filters['q'] ?? null, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('full_name', 'like', '%' . $search . '%')
                        ->orWhere('company_name', 'like', '%' . $search . '%');
                    if (preg_match('/^(?:IK-)?(\d+)$/i', $search, $matches)) {
                        $query->orWhere('id', (int) $matches[1]);
                    }
                });
            })
            ->when($filters['from'] ?? null, function ($query, $from) {
                $query->where('created_at', '>=', $from . ' 00:00:00');
            })
            ->when($filters['to'] ?? null, function ($query, $to) {
                $query->where('created_at', '<', \Carbon\CarbonImmutable::parse($to)->addDay()->startOfDay());
            })
            ->when($filters['status'] ?? null, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($filters['decision'] ?? null, function ($query, $decision) use ($request) {
                $query->whereHas('approvals', function ($approval) use ($request, $decision) {
                    $approval->where('admin_role', $request->user()->role)
                        ->where('status', $decision);
                });
            })
            ->latest()
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.permohonan.history', compact('permits', 'filters'));
    }


    public function decide(
        Request $request,
        WorkPermit $workPermit,
        string $decision
    ) {
        $user = $request->user();

        $allowedRoles = ['humas', 'k3', 'keamanan'];

        abort_unless(
            in_array($user->role, $allowedRoles, true),
            403,
            'Kamu tidak memiliki akses untuk menyetujui izin.'
        );

        abort_unless(
            in_array($decision, ['setuju', 'tolak'], true),
            404
        );

        $validated = $request->validate([
            'notes' => [
                $decision === 'tolak' ? 'required' : 'nullable',
                'string',
                'max:2000'
            ],
        ], [
            'notes.required' => 'Alasan penolakan wajib diisi.'
        ]);

        DB::transaction(function () use (
            $workPermit,
            $user,
            $decision,
            $validated
        ) {

            // 1. Ambil dan kunci seluruh anggota kelompok.
            $membersQuery = WorkPermit::query()
                ->where('user_id', $workPermit->user_id);

            if ($workPermit->group_id !== null) {
                $membersQuery->where('group_id', $workPermit->group_id);
            } else {
                // Permohonan lama tanpa group_id dianggap satu kelompok sendiri.
                $membersQuery->whereKey($workPermit->id);
            }

            $members = $membersQuery
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $permit = $members->firstWhere('id', $workPermit->id);

            abort_if($permit === null, 404);

            // 2. Pastikan permohonan masih dalam proses.
            if ($permit->status !== 'ditinjau') {
                throw ValidationException::withMessages([
                    'decision' => 'Permohonan sudah tidak dalam status ditinjau.',
                ]);
            }

            // 3. Ambil persetujuan sesuai role admin.
            $approval = $permit->approvals()
                ->where('admin_role', $user->role)
                ->lockForUpdate()
                ->firstOrFail();

            if ($approval->status !== 'menunggu') {
                throw ValidationException::withMessages([
                    'decision' => 'Kamu sudah memberikan keputusan untuk permohonan ini.',
                ]);
            }

            // 4. Simpan keputusan admin.
            $approval->update([
                'admin_user_id' => $user->id,
                'status' => $decision === 'setuju'
                    ? 'disetujui'
                    : 'ditolak',
                'notes' => $validated['notes'] ?? null,
                'decided_at' => now(),
            ]);

            // 5. Tentukan status akhir setiap anggota.
            if ($decision === 'tolak') {

                // Satu penolakan langsung memfinalkan anggota tersebut.
                $permit->update([
                    'status' => 'ditolak',
                ]);
            } else {

                // Semua tiga bagian harus menyetujui.
                $approvedCount = $permit->approvals()
                    ->where('status', 'disetujui')
                    ->count();

                if ($approvedCount === 3) {
                    $permit->update([
                        'status' => 'diizinkan',
                    ]);
                }
            }

            // 6. Pertahankan notifikasi database yang sudah ada.
            $permit->user->notify(
                new PermitDecisionUpdated(
                    $permit->id,
                    $user->role,
                    $approval->status
                )
            );

            // 7. Periksa apakah seluruh anggota sudah final.
            $allMembersFinal = $members->isNotEmpty()
                && $members->every(
                    fn(WorkPermit $member): bool => in_array(
                        $member->status,
                        ['diizinkan', 'ditolak'],
                        true
                    )
                );

            // Record pertama menjadi penanda pengiriman email kelompok.
            $groupMarker = $members->first();

            // 8. Kirim email hanya jika seluruh anggota sudah final
            // dan email kelompok belum pernah dimasukkan ke antrean.
            if (
                $allMembersFinal
                && $groupMarker->decision_email_queued_at === null
            ) {

                $members->load('approvals');

                // Siapkan rekap seluruh anggota.
                $emailMembers = $members->map(
                    function (WorkPermit $member): array {

                        $rejectionReasons = $member->approvals
                            ->where('status', 'ditolak')
                            ->map(function ($rejectedApproval): string {

                                $reason = trim(
                                    (string) $rejectedApproval->notes
                                );

                                $role = strtoupper(
                                    $rejectedApproval->admin_role
                                );

                                return $reason === ''
                                    ? $role
                                    : $role . ': ' . $reason;
                            })
                            ->values()
                            ->all();

                        return [
                            'full_name' => $member->full_name,
                            'person_type' => $member->person_type ?? 'anggota',
                            'status' => $member->status,
                            'rejection_reasons' => $rejectionReasons,
                        ];
                    }
                )->values()->all();

                // 9. Tandai email sudah dimasukkan ke antrean.
                $groupMarker->forceFill([
                    'decision_email_queued_at' => now(),
                ])->save();

                // 10. Kirim satu email rekap ke pemohon.
                $permit->user->notify(
                    new PermitGroupDecisionSummary(
                        groupId: $permit->group_id,
                        companyName: $permit->company_name,
                        firstPermitId: (int) $groupMarker->id,
                        members: $emailMembers,
                    )
                );
            }
        });

        return back()->with(
            'success',
            'Keputusan berhasil disimpan.'
        );
    }
}
