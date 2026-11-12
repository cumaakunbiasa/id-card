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

        $rows = (function () {
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

            foreach (WorkPermit::with('approvals')->lazyById(200) as $permit) {
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

        if (class_exists(\ZipArchive::class)) {
            return response()->download(
                $workbook->create($rows),
                $fileBase . '.xlsx',
                [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'Cache-Control' => 'private, no-store',
                ]
            )->deleteFileAfterSend(true);
        }

        // Excel can open this UTF-8 CSV when the PHP zip extension is unavailable.
        return response()->download(
            $workbook->createCsv($rows),
            $fileBase . '.csv',
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Cache-Control' => 'private, no-store',
            ]
        )->deleteFileAfterSend(true);
    }
}
