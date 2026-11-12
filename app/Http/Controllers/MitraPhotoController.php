<?php

namespace App\Http\Controllers;

use App\Models\WorkPermit;
use Illuminate\Http\Request;

class MitraPhotoController extends Controller
{
    public function show(Request $request, WorkPermit $workPermit)
    {
        // Gunakan validasi akses dan normalisasi foto yang sama dengan endpoint foto izin.
        return app(WorkPermitController::class)->photo($request, $workPermit);
    }
}
