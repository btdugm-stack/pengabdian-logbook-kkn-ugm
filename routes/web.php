<?php

use App\Http\Controllers\Admin\DplAssignmentController;
use App\Http\Controllers\Admin\LogbookEntryController;
use App\Http\Controllers\Admin\MasterDataController;
use App\Http\Controllers\Admin\ParticipantController;
use App\Http\Controllers\Admin\RegistrationRequestController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\RegistrationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LogbookBrowseController;
use App\Http\Controllers\LogbookController;
use App\Http\Controllers\LogbookReviewController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OverviewController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicSearchController;
use App\Livewire\AssistAttendanceForm;
use App\Livewire\AssistAttendanceList;
use App\Livewire\AttendanceCheckIn;
use App\Models\Student;
use Illuminate\Support\Facades\Route;

// Landing page (tiga jalur masuk). Route `login` adalah tujuan redirect tamu
// yang membuka halaman ber-auth (lihat redirectGuestsTo di bootstrap/app.php).
Route::get('/', [AuthController::class, 'landing'])->name('home');
Route::get('/login', [AuthController::class, 'landing'])->name('login');

Route::get('/beranda', [HomeController::class, 'index'])->name('public.home');

Route::get('/auth/google', [AuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');
// Pendaftaran mandiri: identitas diambil dari login Google (lihat RegistrationController).
Route::get('/daftar', [RegistrationController::class, 'create'])->name('register.create');
Route::post('/daftar', [RegistrationController::class, 'store'])->middleware('throttle:10,1')->name('register.store');

Route::post('/demo-login', [AuthController::class, 'demoLogin'])->middleware('throttle:10,1')->name('demo-login');
Route::post('/tamu', [AuthController::class, 'enterGuest'])->name('guest.enter');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Pencarian publik menjalankan query LIKE lintas tabel - dibatasi per IP.
Route::middleware('throttle:120,1')->group(function () {
    Route::get('/search/mahasiswa', [PublicSearchController::class, 'students'])->name('search.students');
    Route::get('/search/logbook', [PublicSearchController::class, 'logbooks'])->name('search.logbooks');
    Route::get('/map/public', [MapController::class, 'public'])->name('map.public');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/data-kkn', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/data-kkn', [ProfileController::class, 'update'])->name('profile.update');

    // Fitur lapangan peserta KKN (mahasiswa dan ketua kelompok). Peran lain
    // tidak punya presensi/logbook sendiri, jadi route-nya ditutup (bukan cuma
    // disembunyikan dari menu).
    Route::middleware('role:'.implode('|', Student::FIELD_ROLES))->group(function () {
        Route::get('/logbook/create', [LogbookController::class, 'create'])->name('logbooks.create');
        Route::get('/logbook', [LogbookController::class, 'index'])->name('logbooks.index');
        Route::get('/logbook/export', [LogbookController::class, 'export'])->name('logbooks.export');
        Route::get('/logbook/{logbook}/edit', [LogbookController::class, 'edit'])->name('logbooks.edit');
        Route::delete('/logbook/{logbook}', [LogbookController::class, 'destroy'])->name('logbooks.destroy');

        Route::get('/map', [MapController::class, 'mine'])->name('map.mine');

        Route::get('/presensi', AttendanceCheckIn::class)->name('attendance.check-in');
        Route::get('/presensi-bantuan/create', AssistAttendanceForm::class)->name('assist-attendances.create');
        Route::get('/presensi-bantuan', AssistAttendanceList::class)->name('assist-attendances.index');
    });

    Route::middleware('role:'.implode('|', Student::SUPERVISORY_ROLES))->group(function () {
        Route::get('/overview', [OverviewController::class, 'index'])->name('overview');
        Route::get('/overview/mahasiswa/{student}', [OverviewController::class, 'student'])->name('overview.student');

    });

    // Pencarian dan export logbook sesuai cakupan peran.
    Route::middleware('role:'.implode('|', Student::SUPERVISORY_ROLES))
        ->prefix('logbook-cakupan')
        ->name('logbooks.browse.')
        ->controller(LogbookBrowseController::class)
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/export', 'export')->name('export');
        });

    // Eskalasi kesehatan memuat data per mahasiswa, jadi tidak untuk peran agregat (Pimpinan).
    Route::middleware('role:'.implode('|', array_diff(Student::SUPERVISORY_ROLES, Student::AGGREGATE_ONLY_ROLES)))->group(function () {
        Route::get('/notifikasi', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifikasi/{id}/dibaca', [NotificationController::class, 'markRead'])->name('notifications.mark-read');
        Route::post('/notifikasi/dibaca-semua', [NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
    });

    Route::middleware('role:'.implode('|', Student::REVIEWER_ROLES))
        ->name('logbooks.reviews.')
        ->controller(LogbookReviewController::class)
        ->group(function () {
            Route::get('/reviu-logbook', 'index')->name('index');
            Route::get('/logbook/{logbook}/reviu', 'show')->name('show');
            Route::post('/logbook/{logbook}/reviu', 'store')->name('store');
        });

    Route::middleware('role:'.implode('|', Student::ACCOUNT_MANAGER_ROLES))
        ->prefix('admin/peserta')
        ->name('admin.participants.')
        ->controller(ParticipantController::class)
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/tambah', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/impor', 'importForm')->name('import');
            Route::post('/impor', 'import')->name('import.store');
            Route::get('/impor/template', 'template')->name('import.template');
            Route::get('/{student}/edit', 'edit')->name('edit');
            Route::put('/{student}', 'update')->name('update');
            Route::delete('/{student}', 'destroy')->middleware('role:'.Student::SUPER_ADMIN_ROLE)->name('destroy');
        });

    Route::prefix('admin/master-data')->name('admin.master-data.')->controller(MasterDataController::class)->group(function () {
        Route::get('/', 'index')->middleware('role:'.implode('|', Student::MASTER_DATA_VIEWER_ROLES))->name('index');

        Route::middleware('role:'.implode('|', Student::ACCOUNT_MANAGER_ROLES))->group(function () {
            Route::put('/{type}/{id}', 'update')->whereNumber('id')->name('update');
            Route::post('/{type}/{id}/gabung', 'merge')->whereNumber('id')->name('merge');
            Route::delete('/{type}/{id}', 'destroy')->whereNumber('id')->name('destroy');
        });
    });

    // DPL memilih sendiri mahasiswa bimbingannya.
    Route::middleware('role:'.implode('|', Student::ASSIGNED_SCOPE_ROLES))->controller(DplAssignmentController::class)->group(function () {
        Route::get('/mahasiswa-bimbingan', 'editOwn')->name('advisees.edit');
        Route::put('/mahasiswa-bimbingan', 'updateOwn')->name('advisees.update');
    });

    Route::middleware('role:'.implode('|', Student::ACCOUNT_MANAGER_ROLES))
        ->prefix('admin/penugasan-dpl')
        ->name('admin.dpl-assignments.')
        ->controller(DplAssignmentController::class)
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/{dpl}', 'edit')->name('edit');
            Route::put('/{dpl}', 'update')->name('update');
        });

    // Admin menginput atau mengoreksi logbook atas nama peserta.
    Route::middleware('role:'.implode('|', Student::ACCOUNT_MANAGER_ROLES))
        ->prefix('admin/logbook')
        ->name('admin.logbooks.')
        ->controller(LogbookEntryController::class)
        ->group(function () {
            Route::get('/tambah/{student}', 'create')->name('create');
            Route::get('/{logbook}/ubah', 'edit')->name('edit');
        });

    Route::middleware('role:'.implode('|', Student::ACCOUNT_MANAGER_ROLES))
        ->prefix('admin/pendaftaran')
        ->name('admin.registrations.')
        ->controller(RegistrationRequestController::class)
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/{registrationRequest}/setujui', 'approve')->name('approve');
            Route::post('/{registrationRequest}/tolak', 'reject')->name('reject');
        });
});
