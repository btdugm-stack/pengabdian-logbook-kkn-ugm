<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Spatie\Permission\Middleware\RoleMiddleware;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Di belakang reverse proxy HTTPS request bisa sampai ke PHP sebagai HTTP;
        // tanpa ini asset, redirect OAuth Google, dan form action ter-generate
        // sebagai http:// (mixed content / redirect_uri_mismatch).
        URL::forceHttps($this->app->isProduction() && str_starts_with((string) config('app.url'), 'https://'));

        // Request update Livewire (/livewire/update) hanya menjalankan ulang
        // middleware "persistent" dari route asal. Tanpa baris ini, pembatasan
        // `role:mahasiswa` di route presensi/logbook tidak ikut ditegakkan pada
        // aksi wire:click - hanya pada saat halaman pertama dibuka.
        Livewire::addPersistentMiddleware([RoleMiddleware::class]);
    }
}
