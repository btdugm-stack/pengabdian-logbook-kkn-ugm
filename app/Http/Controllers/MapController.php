<?php

namespace App\Http\Controllers;

use App\Models\Logbook;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MapController extends Controller
{
    /** Warna netral untuk peta publik - kondisi kesehatan TIDAK boleh tampil di sana. */
    private const PUBLIC_MARKER_COLOR = '#3B82F6';

    public function mine(): View
    {
        return $this->render(Auth::user()->logbooks(), 'Peta Saya');
    }

    public function public(): View
    {
        return $this->render(Logbook::query(), 'View Peta', public: true);
    }

    /**
     * Mode publik (audit §privasi): 'health' dikosongkan & warna marker dinetralkan
     * supaya data kesehatan per-individu tidak bocor lewat peta tanpa login,
     * konsisten dengan Search Logbook publik yang menyembunyikan kolom Kondisi.
     */
    private function render($query, string $title, bool $public = false): View
    {
        $logbooks = $query->with(['student', 'program', 'location'])->get();

        $markers = $logbooks->filter(fn ($l) => $l->location->latitude && $l->location->longitude)
            ->map(fn ($l) => [
                'lat' => (float) $l->location->latitude,
                'lng' => (float) $l->location->longitude,
                'student' => $l->student->name,
                'title' => $l->program->name,
                'location' => $l->location->name,
                'health' => $public ? null : $l->health_status,
                'status' => $l->status,
                'count' => $l->community_count,
                'color' => $public ? self::PUBLIC_MARKER_COLOR : $l->markerColor(),
            ])->values();

        return view('map', ['markers' => $markers, 'title' => $title, 'public' => $public]);
    }
}
