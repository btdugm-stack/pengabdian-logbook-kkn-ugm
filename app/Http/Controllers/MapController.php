<?php

namespace App\Http\Controllers;

use App\Models\Logbook;
use App\Support\ActivityMap;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MapController extends Controller
{
    public function mine(): View
    {
        return $this->render(Auth::user()->logbooks(), 'Peta Saya');
    }

    public function public(): View
    {
        return $this->render(Logbook::visibleToPublic(), 'Peta Sebaran', public: true);
    }

    /**
     * Mode publik (audit §privasi): titik diwarnai menurut tema kegiatan dan
     * kondisi kesehatan tidak dikirim sama sekali, konsisten dengan Search
     * Logbook publik yang menyembunyikan kolom Kondisi. Peta milik sendiri
     * diwarnai menurut kondisi kesehatan.
     */
    private function render($query, string $title, bool $public = false): View
    {
        $logbooks = $query->with(['student', 'theme', 'program', 'location'])->get();

        return view('map', [
            'map' => $public ? ActivityMap::byTheme($logbooks) : ActivityMap::byHealth($logbooks),
            'title' => $title,
            'public' => $public,
        ]);
    }
}
