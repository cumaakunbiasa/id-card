<?php
namespace App\Http\Controllers;
use App\Models\WorkPermit;
use App\Services\WorkerWorkbook;
use Illuminate\Http\Request;
class WorkerExportController extends Controller {
    public function index(Request $request, WorkerWorkbook $workbook) {
        abort_unless(in_array($request->user()->role, ['humas','k3','keamanan'], true), 403);
        if (!class_exists(\ZipArchive::class)) {
            return back()->withErrors(['export' => 'Aktifkan ekstensi zip di php.ini Laragon lalu restart PHP untuk mengunduh Excel.']);
        }
        $rows = (function () {
            yield ['Nomor','Kelompok','Jenis','Nama','Mandor','Perusahaan','Zona','Pekerjaan','Mulai','Selesai','Status','Humas','K3','Keamanan','Didaftarkan'];
            foreach (WorkPermit::with('approvals')->lazyById(200) as $p) {
                yield ['IK-'.str_pad((string) $p->id, 6, '0', STR_PAD_LEFT), $p->group_id, $p->person_type ?? 'pekerja', $p->full_name, $p->foreman_name, $p->company_name, $p->work_location, $p->job_title,
                    $p->start_date->format('Y-m-d'),
                    $p->end_date->format('Y-m-d'), $p->status,
                    $p->approvals->firstWhere('admin_role','humas')?->status,
                    $p->approvals->firstWhere('admin_role','k3')?->status,
                    $p->approvals->firstWhere('admin_role','keamanan')?->status, $p->created_at?->format('Y-m-d H:i')];
            }
        })();
        return response()->download($workbook->create($rows), 'mitra-kerja-'.now()->format('Ymd-His').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'private, no-store',
        ])->deleteFileAfterSend(true);
    }
}
