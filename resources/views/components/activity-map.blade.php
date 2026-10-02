@props([
  // Hasil App\Support\ActivityMap::byHealth() / byTheme().
  'map',
  'emptyText' => 'Belum ada logbook dengan koordinat lokasi.',
])
{{-- Peta kegiatan dengan legenda yang sekaligus menjadi filter: klik satu
     kategori untuk menyembunyikan/menampilkan titiknya. Logikanya di
     window.renderActivityMap pada resources/js/app.js. --}}
<div class="activity-map">
  @if ($map['markers'] === [])
    <p class="hint" style="margin:0 0 12px">{{ $emptyText }}</p>
  @else
    <div class="map-legend" id="map-legend" role="group" aria-label="Filter peta menurut {{ mb_strtolower($map['legendLabel']) }}">
      <span class="map-legend-title">{{ $map['legendLabel'] }}</span>
      {{-- Tombol legenda diisi JavaScript; daftar ini tetap terbaca bila JavaScript mati. --}}
      <noscript>
        @foreach ($map['groups'] as $group)
          <span class="map-legend-item"><span class="map-legend-swatch" style="background:{{ $group['color'] }}"></span>{{ $group['label'] }}</span>
        @endforeach
      </noscript>
    </div>
    <p class="hint" id="map-status" role="status" style="margin:0 0 10px"></p>
  @endif
  <div id="map"></div>
</div>

@once
  @push('activity-map-scripts')
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        window.renderActivityMap({ markers: @json($map['markers']), groups: @json($map['groups']) });
      });
    </script>
  @endpush
@endonce
