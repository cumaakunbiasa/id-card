
<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PermitApprovalController;
use App\Http\Controllers\OperatorIdCardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WorkPermitController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Halaman Utama
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Dashboard
|--------------------------------------------------------------------------
*/

Route::get('/dashboard', [
    DashboardController::class,
    'index',
])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

/*
|--------------------------------------------------------------------------
| Profil Pengguna
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::get('/profile', [
        ProfileController::class,
        'edit',
    ])->name('profile.edit');

    Route::patch('/profile', [
        ProfileController::class,
        'update',
    ])->name('profile.update');

    Route::delete('/profile', [
        ProfileController::class,
        'destroy',
    ])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Permohonan Izin Kerja - Pemohon
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified', 'role:pemohon'])->group(function () {
    Route::get('/permohonan', [
        WorkPermitController::class,
        'index',
    ])->name('permits.index');

    Route::get('/permohonan/buat', [
        WorkPermitController::class,
        'create',
    ])->name('permits.create');

    Route::post('/permohonan', [
        WorkPermitController::class,
        'store',
    ])->name('permits.store');
});

/*
|--------------------------------------------------------------------------
| Persetujuan Izin Kerja - Humas, K3, Keamanan
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth',
    'verified',
    'role:humas,k3,keamanan',
])->group(function () {
    Route::get('/admin/permohonan', [
        PermitApprovalController::class,
        'index',
    ])->name('admin.permohonan.index');
    Route::get('/admin/permohonan/riwayat', [PermitApprovalController::class, 'history'])
        ->name('admin.permohonan.history');

    Route::post(
        '/admin/permohonan/{workPermit}/keputusan/{decision}',
        [PermitApprovalController::class, 'decide']
    )
        ->where('decision', 'setuju|tolak')
        ->name('admin.permohonan.decide');
});

/*
|--------------------------------------------------------------------------
| Operator ID Card
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified', 'role:operator'])->prefix('operator')->name('operator.')->group(function () {
    Route::get('/id-card', [OperatorIdCardController::class, 'index'])
        ->name('id-cards.index');

    Route::get('/id-card/tercetak', [OperatorIdCardController::class, 'printed'])
        ->name('id-cards.printed');

    Route::get('/id-card/{workPermit}', [OperatorIdCardController::class, 'show'])
        ->name('id-cards.show');

    Route::patch('/id-card/{workPermit}/zona', [OperatorIdCardController::class, 'updateZone'])
        ->name('id-cards.update-zone');

    Route::patch('/id-card/{workPermit}/sudah-dicetak', [OperatorIdCardController::class, 'markPrinted'])
        ->name('id-cards.mark-printed');
});

Route::get('/permohonan/{workPermit}', [
    WorkPermitController::class,
    'show',
])
    ->middleware([
        'auth',
        'verified',
        'role:pemohon,humas,k3,keamanan',
    ])
    ->name('permits.show');
Route::get('/permohonan/{workPermit}/foto', [
    WorkPermitController::class,
    'photo',
])->middleware([
    'auth',
    'verified',
    'role:pemohon,humas,k3,keamanan,operator',
])->name('permits.photo');


/*
|--------------------------------------------------------------------------
| Autentikasi Laravel Breeze
|--------------------------------------------------------------------------
*/

Route::get('/mitra/{workPermit}/foto', [WorkPermitController::class, 'photo'])
    ->middleware(['auth', 'verified'])->name('mitra.photo');
Route::get('/admin/pekerja/export', [\App\Http\Controllers\WorkerExportController::class, 'index'])
    ->middleware(['auth', 'verified', 'role:humas,k3,keamanan'])->name('admin.workers.export');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/notifikasi', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifikasi/{notification}/baca', [\App\Http\Controllers\NotificationController::class, 'read'])->name('notifications.read');
});

require __DIR__ . '/auth.php';
