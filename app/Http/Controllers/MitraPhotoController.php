<?php
namespace App\Http\Controllers;
use App\Models\WorkPermit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
class MitraPhotoController extends Controller {
    public function show(Request $request, WorkPermit $workPermit) {
        $user = $request->user();
        $allowed = in_array($user->role, ['humas','k3','keamanan'], true)
            || ($user->role === 'pemohon' && (int) $workPermit->user_id === (int) $user->id)
            || ($user->role === 'operator' && $workPermit->status === 'diizinkan');
        abort_unless($allowed, 403);
        abort_unless($workPermit->photo_path && Storage::disk('local')->exists($workPermit->photo_path), 404);
        return Storage::disk('local')->response($workPermit->photo_path, null, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
}
