<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LogbookController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OverviewController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicSearchController;
use App\Livewire\AssistAttendanceForm;
use App\Livewire\AssistAttendanceList;
use App\Livewire\AttendanceCheckIn;
use Illuminate\Support\Facades\Route;

// Landing page (tiga jalur masuk). `/login` dipertahankan sebagai nama route
// karena dipakai `redirectGuestsTo('/login')` di bootstrap/app.php.
Route::get('/', [AuthController::class, 'landing'])->name('home');
Route::get('/login', [AuthController::class, 'landing'])->name('login');

Route::get('/beranda', [HomeController::class, 'index'])->name('public.home');

Route::get('/auth/google', [AuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');
Route::post('/demo-login', [AuthController::class, 'demoLogin'])->name('demo-login');
Route::post('/tamu', [AuthController::class, 'enterGuest'])->name('guest.enter');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/search/mahasiswa', [PublicSearchController::class, 'students'])->name('search.students');
Route::get('/search/logbook', [PublicSearchController::class, 'logbooks'])->name('search.logbooks');
Route::get('/map/public', [MapController::class, 'public'])->name('map.public');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/data-kkn', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/data-kkn', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/logbook/create', [LogbookController::class, 'create'])->name('logbooks.create');
    Route::get('/logbook', [LogbookController::class, 'index'])->name('logbooks.index');
    Route::get('/logbook/export', [LogbookController::class, 'export'])->name('logbooks.export');

    Route::get('/map', [MapController::class, 'mine'])->name('map.mine');

    Route::get('/presensi', AttendanceCheckIn::class)->name('attendance.check-in');
    Route::get('/presensi-bantuan/create', AssistAttendanceForm::class)->name('assist-attendances.create');
    Route::get('/presensi-bantuan', AssistAttendanceList::class)->name('assist-attendances.index');

    Route::get('/overview', [OverviewController::class, 'index'])->name('overview');

    Route::get('/notifikasi', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifikasi/{id}/dibaca', [NotificationController::class, 'markRead'])->name('notifications.mark-read');
    Route::post('/notifikasi/dibaca-semua', [NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
});
