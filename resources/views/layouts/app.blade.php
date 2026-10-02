<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  @php
    $pageTitle = trim($__env->yieldContent('title'));
  @endphp
  <title>{{ $pageTitle !== '' ? $pageTitle.' · ' : '' }}Logbook KKN UGM</title>
  {{-- viewport-fit=cover mengizinkan konten mengalir ke area notch/status bar
       bulat (iPhone dsb.); paddingnya ditangani lewat env(safe-area-inset-*)
       di resources/css/app.css, bukan dibiarkan konten mentok/terpotong. --}}
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <meta name="theme-color" content="#003D7C">
  {{-- Semua path lewat asset() supaya tetap benar saat dipasang di subpath
       (APP_URL=https://dts-lab.web.id/pengabdian-kkn), bukan di root domain. --}}
  <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
  <link rel="icon" href="{{ asset('icons/icon-192.png') }}">
  <link rel="apple-touch-icon" href="{{ asset('icons/icon-192.png') }}">
  <meta name="sw-url" content="{{ asset('sw.js') }}">
  {{-- iOS mengabaikan sebagian besar manifest.webmanifest untuk "Add to Home
       Screen" - butuh meta khusus ini supaya jadi standalone app, bukan tab
       Safari biasa dengan chrome browser penuh. --}}
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="Logbook KKN">
  {{-- Pasang state sidebar sebelum CSS dirender supaya tidak ada kedip lebar. --}}
  <script>
    try {
      if (localStorage.getItem('logbook-kkn:sidebar-collapsed') === '1') {
        document.documentElement.classList.add('sidebar-collapsed');
      }
    } catch (e) {}
  </script>
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  @livewireStyles
</head>
<body>
@php
  $activeUser = auth()->user();
  $isGuestMode = ! $activeUser && session('guest_mode');
  $isMahasiswa = (bool) $activeUser?->isParticipant();
  $isSupervisor = (bool) $activeUser?->hasAnyRole(\App\Models\Student::SUPERVISORY_ROLES);
  $canManageAccounts = (bool) $activeUser?->hasAnyRole(\App\Models\Student::ACCOUNT_MANAGER_ROLES);
  $subLabel = $activeUser ? ($isMahasiswa ? $activeUser->faculty : $activeUser->region?->name) : null;
  $pendingApprovals = $isMahasiswa ? $activeUser->assistAttendancesAsHost()->where('approval_status', 'Menunggu')->count() : 0;
  $seesIndividuals = $isSupervisor && ! $activeUser->seesAggregateOnly();
  $unread = $seesIndividuals ? $activeUser->unreadNotifications()->count() : 0;
  $canReviewLogbooks = (bool) $activeUser?->hasAnyRole(\App\Models\Student::REVIEWER_ROLES);
  $logbooksToReview = $canReviewLogbooks
    ? \App\Models\Logbook::where('status', \App\Models\Logbook::STATUS_SUBMITTED)->whereIn('student_id', $activeUser->supervisedStudents()->select('id'))->count()
    : 0;
  $canViewMasterData = (bool) $activeUser?->hasAnyRole(\App\Models\Student::MASTER_DATA_VIEWER_ROLES);
  $pendingRegistrations = $canManageAccounts ? \App\Models\RegistrationRequest::awaitingReview()->count() : 0;
  $feedbackUrl = config('app.feedback_url');
@endphp
<div class="shell">
  <div class="drawer-overlay" id="drawer-overlay"></div>

  {{-- .sidebar (aside) cuma pembawa latar biru - dibuat height:auto supaya
       stretch mengikuti tinggi baris grid (= tinggi .content persis), jadi
       latarnya tidak pernah "bocor"/terputus di halaman panjang. Semua isi
       & perilaku sticky-nya ada di .sidebar-inner (lihat catatan di app.css). --}}
  <aside class="sidebar">
  <div class="sidebar-inner">
    <div class="brand">
      <div class="logo"><img src="{{ asset('icons/logo-ugm.png') }}" alt="Logo Universitas Gadjah Mada"></div>
      <div class="brand-text">
        <h1>Logbook KKN</h1>
        <p>KKN-PPM UGM <span class="ea-tag">Early Access</span></p>
      </div>
      <button type="button" class="sidebar-toggle" id="sidebar-toggle" title="Ciutkan / lebarkan menu" aria-label="Ciutkan atau lebarkan menu">
        <x-icon name="menu" :size="17" />
      </button>
    </div>

    @auth
      <div class="role-card">
        <div class="avatar">{{ $activeUser->initials() }}</div>
        <div style="min-width:0">
          <div class="name">{{ $activeUser->name }}</div>
          <div class="desc">{{ $activeUser->roleLabel() }}@if ($subLabel) &middot; {{ $subLabel }} @endif</div>
        </div>
      </div>
    @else
      <div class="role-card">
        <div class="avatar" style="background:rgba(255,255,255,.14);color:#fff"><x-icon name="user" :size="17" /></div>
        <div style="min-width:0">
          <div class="name">{{ $isGuestMode ? 'Mode Tamu' : 'Pengunjung' }}</div>
          <div class="desc">{{ $isGuestMode ? 'Akses data publik' : 'Belum masuk' }}</div>
        </div>
      </div>
    @endauth

    @if ($isMahasiswa)
      <div class="nav-group">
        <div class="nav-title">Kegiatan KKN</div>
        <nav class="menu">
          <a class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}" title="Dashboard"><span class="icon"><x-icon name="dashboard" /></span> <span class="label">Dashboard</span></a>
          <a class="{{ request()->routeIs('attendance.check-in') ? 'active' : '' }}" href="{{ route('attendance.check-in') }}" title="Presensi Harian"><span class="icon"><x-icon name="check" /></span> <span class="label">Presensi Harian</span></a>
          <a class="{{ request()->routeIs('logbooks.create') ? 'active' : '' }}" href="{{ route('logbooks.create') }}" title="Input Logbook"><span class="icon"><x-icon name="pen" /></span> <span class="label">Input Logbook</span></a>
          <a class="{{ request()->routeIs('logbooks.index', 'logbooks.edit') ? 'active' : '' }}" href="{{ route('logbooks.index') }}" title="Logbook Saya"><span class="icon"><x-icon name="book" /></span> <span class="label">Logbook Saya</span></a>
          <a class="{{ request()->routeIs('assist-attendances.*') ? 'active' : '' }}" href="{{ route('assist-attendances.index') }}" title="Presensi Bantuan">
            <span class="icon"><x-icon name="users" /></span> <span class="label">Presensi Bantuan</span>
            @if ($pendingApprovals)
              <span class="badge" title="{{ $pendingApprovals }} menunggu persetujuan Anda">{{ $pendingApprovals }}</span>
            @endif
          </a>
          <a class="{{ request()->routeIs('map.mine') ? 'active' : '' }}" href="{{ route('map.mine') }}" title="Peta Saya"><span class="icon"><x-icon name="map" /></span> <span class="label">Peta Saya</span></a>
        </nav>
      </div>
    @endif

    @if ($isSupervisor)
      <div class="nav-group">
        <div class="nav-title">{{ $isMahasiswa ? 'Pantauan Kelompok' : 'Panel Supervisi' }}</div>
        <nav class="menu">
          <a class="{{ request()->routeIs('overview', 'overview.*') ? 'active' : '' }}" href="{{ route('overview') }}" title="Overview Wilayah"><span class="icon"><x-icon name="compass" /></span> <span class="label">{{ $isMahasiswa ? 'Overview Kelompok' : 'Overview Wilayah' }}</span></a>
          <a class="{{ request()->routeIs('logbooks.browse.*') ? 'active' : '' }}" href="{{ route('logbooks.browse.index') }}" title="Logbook dalam Cakupan"><span class="icon"><x-icon name="search" /></span> <span class="label">{{ $isMahasiswa ? 'Logbook Kelompok' : 'Cari Logbook Cakupan' }}</span></a>
          @if ($canReviewLogbooks)
            <a class="{{ request()->routeIs('logbooks.reviews.*') ? 'active' : '' }}" href="{{ route('logbooks.reviews.index') }}" title="Reviu Logbook">
              <span class="icon"><x-icon name="book" /></span> <span class="label">Reviu Logbook</span>
              @if ($logbooksToReview)
                <span class="badge" title="{{ $logbooksToReview }} logbook menunggu reviu">{{ $logbooksToReview }}</span>
              @endif
            </a>
          @endif
          @if ($activeUser->hasAnyRole(\App\Models\Student::ASSIGNED_SCOPE_ROLES))
            <a class="{{ request()->routeIs('advisees.*') ? 'active' : '' }}" href="{{ route('advisees.edit') }}" title="Mahasiswa Bimbingan"><span class="icon"><x-icon name="users" /></span> <span class="label">Mahasiswa Bimbingan</span></a>
          @endif
          @if ($seesIndividuals)
            <a class="{{ request()->routeIs('notifications.*') ? 'active' : '' }}" href="{{ route('notifications.index') }}" title="Notifikasi">
              <span class="icon"><x-icon name="bell" /></span> <span class="label">Notifikasi</span>
              @if ($unread)
                <span class="badge">{{ $unread }}</span>
              @endif
            </a>
          @endif
        </nav>
      </div>
    @endif

    @if ($canManageAccounts || $canViewMasterData)
      <div class="nav-group">
        <div class="nav-title">Administrasi</div>
        <nav class="menu">
          <a class="{{ request()->routeIs('admin.master-data.*') ? 'active' : '' }}" href="{{ route('admin.master-data.index') }}" title="Master Data"><span class="icon"><x-icon name="file" /></span> <span class="label">Master Data</span></a>
          @if ($canManageAccounts)
          <a class="{{ request()->routeIs('admin.participants.*') ? 'active' : '' }}" href="{{ route('admin.participants.index') }}" title="Kelola Peserta"><span class="icon"><x-icon name="user-plus" /></span> <span class="label">Kelola Peserta</span></a>
          <a class="{{ request()->routeIs('admin.dpl-assignments.*') ? 'active' : '' }}" href="{{ route('admin.dpl-assignments.index') }}" title="Penugasan DPL"><span class="icon"><x-icon name="users" /></span> <span class="label">Penugasan DPL</span></a>
          <a class="{{ request()->routeIs('admin.registrations.*') ? 'active' : '' }}" href="{{ route('admin.registrations.index') }}" title="Permintaan Pendaftaran">
            <span class="icon"><x-icon name="file" /></span> <span class="label">Permintaan Pendaftaran</span>
            @if ($pendingRegistrations)
              <span class="badge" title="{{ $pendingRegistrations }} menunggu persetujuan">{{ $pendingRegistrations }}</span>
            @endif
          </a>
          @endif
        </nav>
      </div>
    @endif

    {{-- Halaman publik hanya ditawarkan di menu untuk yang belum login (tamu).
         Akun terdaftar sudah punya menu perannya sendiri; alamat publiknya
         tetap bisa dibuka langsung. --}}
    @guest
    <div class="nav-group">
      <div class="nav-title">Data Publik</div>
      <nav class="menu">
        <a class="{{ request()->routeIs('public.home') ? 'active' : '' }}" href="{{ route('public.home') }}" title="Beranda"><span class="icon"><x-icon name="home" /></span> <span class="label">Beranda</span></a>
        <a class="{{ request()->routeIs('search.students') ? 'active' : '' }}" href="{{ route('search.students') }}" title="Cari Mahasiswa"><span class="icon"><x-icon name="search" /></span> <span class="label">Cari Mahasiswa</span></a>
        <a class="{{ request()->routeIs('search.logbooks') ? 'active' : '' }}" href="{{ route('search.logbooks') }}" title="Cari Logbook"><span class="icon"><x-icon name="file" /></span> <span class="label">Cari Logbook</span></a>
        <a class="{{ request()->routeIs('map.public') ? 'active' : '' }}" href="{{ route('map.public') }}" title="Peta Sebaran"><span class="icon"><x-icon name="pin" /></span> <span class="label">Peta Sebaran</span></a>
      </nav>
    </div>
    @endguest

    @auth
      <div class="nav-group">
        <div class="nav-title">Akun</div>
        <nav class="menu">
          <a class="{{ request()->routeIs('profile.*') ? 'active' : '' }}" href="{{ route('profile.edit') }}" title="{{ $isMahasiswa ? 'Data KKN' : 'Profil' }}"><span class="icon"><x-icon name="user" /></span> <span class="label">{{ $isMahasiswa ? 'Data KKN' : 'Profil' }}</span></a>
        </nav>
      </div>
    @endauth

    <div class="nav-group" style="margin-top:auto">
      <nav class="menu">
        {{-- Tombol instal PWA - disembunyikan default, hanya muncul lewat JS
             saat browser menembak event `beforeinstallprompt` (lihat app.js). --}}
        <button type="button" id="pwa-install-btn" hidden title="Instal Aplikasi">
          <span class="icon"><x-icon name="phone-app" /></span> <span class="label">Instal Aplikasi</span>
        </button>
        @if ($feedbackUrl)
          <a href="{{ $feedbackUrl }}" target="_blank" rel="noopener" title="Kirim Masukan"><span class="icon"><x-icon name="message" /></span> <span class="label">Kirim Masukan</span></a>
        @endif
        @auth
          <form method="post" action="{{ route('logout') }}">
            @csrf
            <button type="submit" title="Keluar"><span class="icon"><x-icon name="logout" /></span> <span class="label">Keluar</span></button>
          </form>
        @elseif ($isGuestMode)
          <form method="post" action="{{ route('logout') }}">
            @csrf
            <button type="submit" title="Keluar dari mode tamu"><span class="icon"><x-icon name="logout" /></span> <span class="label">Keluar Mode Tamu</span></button>
          </form>
        @else
          <a href="{{ route('login') }}" title="Masuk"><span class="icon"><x-icon name="login" /></span> <span class="label">Masuk</span></a>
        @endauth
      </nav>
    </div>
  </div>
  </aside>

  <section class="content">
    <div class="topbar">
      <div class="topbar-left">
        <button type="button" class="topbar-menu-btn" id="drawer-toggle" aria-label="Buka menu">
          <x-icon name="menu" :size="19" />
        </button>
        <div style="min-width:0">
          <h2>@yield('title')</h2>
          <p>@yield('description')</p>
        </div>
      </div>
      <div class="top-actions">
        @if ($isMahasiswa && ! request()->routeIs('logbooks.create', 'logbooks.edit'))
          <a class="btn btn-soft" href="{{ route('logbooks.create') }}"><x-icon name="plus" :size="16" /> Input Logbook</a>
        @elseif (! $activeUser)
          <a class="btn btn-primary" href="{{ route('login') }}">Masuk</a>
        @endif
      </div>
    </div>
    <main class="container">
      <div id="offline-banner" class="alert alert-error" hidden>
        Anda sedang offline. Presensi dan logbook belum bisa disimpan sampai koneksi kembali.
      </div>
      @if (session('flash_success'))
        <div class="alert alert-success" role="status">{{ session('flash_success') }}</div>
      @endif
      @if (session('flash_error'))
        <div class="alert alert-error" role="alert">{{ session('flash_error') }}</div>
      @endif

      @yield('content')
    </main>
  </section>
</div>
@livewireScripts
@stack('activity-map-scripts')
@yield('scripts')
</body>
</html>
