<?php

namespace App\Http\Controllers;

use App\Models\WorkPermit;
use App\Services\WorkerWorkbook;
use Illuminate\Http\Request;

class WorkerExportController extends Controller
{
    public function index(Request $request, WorkerWorkbook $workbook)
    {
        abort_unless(
            in_array($request->user()->role, ['humas', 'k3', 'keamanan'], true),
            403
        );

        $filters = $request->validate([
            'period' => ['required', 'in:range,all'],
            'from' => ['required_if:period,range', 'nullable', 'date_format:Y-m-d'],
            'to' => ['required_if:period,range', 'nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $useDateRange = $filters['period'] === 'range';

        if (!class_exists(\ZipArchive::class)) {
            return back()->withErrors([
                'export' => 'Ekspor Excel membutuhkan ekstensi PHP zip. Aktifkan extension=zip pada php.ini Laragon, lalu restart Apache/PHP.',
            ]);
        }

        // Gunakan tanggal pendaftaran agar konsisten dengan filter Riwayat Pendaftaran.
        $permits = WorkPermit::query()
            ->with('approvals')
            ->when($useDateRange && ($filters['from'] ?? null), function ($query, $from) {
                $query->where('created_at', '>=', $from . ' 00:00:00');
            })
            ->when($useDateRange && ($filters['to'] ?? null), function ($query, $to) {
                $query->where('created_at', '<', \Carbon\CarbonImmutable::parse($to)->addDay()->startOfDay());
            });

        $rows = (function () use ($permits) {
            yield [
                'Nomor',
                'Kelompok',
                'Jenis',
                'Nama',
                'Mandor',
                'Perusahaan',
                'Lokasi Kerja',
                'Zona',
                'Pekerjaan',
                'Mulai',
                'Selesai',
                'Status',
                'Humas',
                'K3',
                'Keamanan',
                'Didaftarkan',
            ];

            foreach ($permits->lazyById(200) as $permit) {
                $zoneLabel = $permit->zone_key
                    ? (config('mitra.zones.' . $permit->zone_key . '.label') ?? 'Tidak diketahui')
                    : 'Belum ditentukan';

                yield [
                    'IK-' . str_pad((string) $permit->id, 6, '0', STR_PAD_LEFT),
                    $permit->group_id,
                    $permit->person_type ?? 'pekerja',
                    $permit->full_name,
                    $permit->foreman_name,
                    $permit->company_name,
                    $permit->work_location,
                    $zoneLabel,
                    $permit->job_title,
                    $permit->start_date?->format('Y-m-d') ?? '',
                    $permit->end_date?->format('Y-m-d') ?? '',
                    $permit->status,
                    $permit->approvals->firstWhere('admin_role', 'humas')?->status,
                    $permit->approvals->firstWhere('admin_role', 'k3')?->status,
                    $permit->approvals->firstWhere('admin_role', 'keamanan')?->status,
                    $permit->created_at?->format('Y-m-d H:i') ?? '',
                ];
            }
        })();

        $fileBase = 'mitra-kerja-' . now()->format('Ymd-His');
        if ($useDateRange) {
            $fileBase .= '-' . $filters['from'] . '-sampai-' . $filters['to'];
        } else {
            $fileBase .= '-semua-periode';
        }

        return response()->download(
            $workbook->create($rows),
            $fileBase . '.xlsx',
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Cache-Control' => 'private, no-store',
            ]
        )->deleteFileAfterSend(true);
    }
}
