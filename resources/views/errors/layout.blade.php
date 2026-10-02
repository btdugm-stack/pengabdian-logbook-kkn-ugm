{{-- Halaman error mandiri: CSS inline, tanpa Vite/DB/auth, supaya tetap bisa
     dirender walau penyebab error-nya adalah salah satu dari hal tersebut. --}}
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <meta name="theme-color" content="#003D7C">
  <title>@yield('title') · Logbook KKN UGM</title>
  <style>
    *{box-sizing:border-box}
    body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;background:#F4F7FB;color:#16283D;font-family:'Figtree','Segoe UI',Arial,sans-serif;text-align:center}
    .box{max-width:460px;width:100%;background:#fff;border:1px solid #E1E8F0;border-radius:20px;padding:34px 28px;box-shadow:0 8px 24px rgba(0,41,79,.06)}
    .logo{width:56px;height:56px;margin:0 auto 18px;border-radius:14px;background:#fff;border:1px solid #E1E8F0;padding:6px}
    .logo img{width:100%;height:100%;object-fit:contain}
    .code{font-size:13px;font-weight:800;letter-spacing:.12em;color:#8A5A00;background:#FFF6DC;display:inline-block;padding:4px 12px;border-radius:999px}
    h1{font-size:22px;color:#003D7C;margin:14px 0 8px;letter-spacing:-.02em}
    p{color:#5D6B7E;line-height:1.6;margin:0 0 22px;font-size:14.5px}
    .actions{display:flex;gap:10px;justify-content:center;flex-wrap:wrap}
    a{display:inline-flex;align-items:center;justify-content:center;min-height:44px;padding:10px 18px;border-radius:11px;font-weight:700;font-size:14px;text-decoration:none}
    .primary{background:#003D7C;color:#fff}
    .outline{border:1px solid #E1E8F0;color:#003D7C;background:#fff}
  </style>
</head>
<body>
  <div class="box">
    <div class="logo"><img src="{{ asset('icons/logo-ugm.png') }}" alt="Logo Universitas Gadjah Mada"></div>
    <span class="code">@yield('code')</span>
    <h1>@yield('title')</h1>
    <p>@yield('message')</p>
    <div class="actions">
      <a class="primary" href="{{ url('/') }}">Ke Halaman Utama</a>
      <a class="outline" href="javascript:history.back()">Kembali</a>
    </div>
  </div>
</body>
</html>
