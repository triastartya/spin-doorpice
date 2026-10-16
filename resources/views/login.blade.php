<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Panitia · Spin Doorprize</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen font-sans text-slate-800">
<div class="flex min-h-screen">
    <section class="relative hidden overflow-hidden bg-blue-600 p-12 lg:flex lg:w-[45%] lg:flex-col lg:justify-between">
        <div class="absolute -right-24 -top-24 h-80 w-80 rounded-full bg-white/5"></div>
        <div class="absolute -bottom-20 -left-20 h-64 w-64 rounded-full bg-white/5"></div>
        <div class="absolute right-8 top-1/2 h-32 w-32 rounded-full bg-white/10"></div>
        <div class="relative flex items-center gap-3">
            <div class="flex items-center gap-3 rounded-xl bg-white px-5 py-3">
                <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-blue-600 text-3xl text-white">🎡</div>
                <div><p class="text-xl font-extrabold tracking-tight text-blue-700">Spin Doorprize</p><p class="text-xs leading-tight text-slate-400">Sistem Undian Karyawan</p></div>
            </div>
        </div>
        <div class="relative">
            <div class="mb-6 inline-flex items-center gap-2 rounded-full bg-white/15 px-4 py-1.5"><span class="h-1.5 w-1.5 animate-pulse rounded-full bg-green-300"></span><span class="text-xs font-medium text-white/80">Sistem Siap Digunakan</span></div>
            <h1 class="mb-4 text-4xl font-extrabold leading-tight text-white">Kelola<br>Doorprize<br>dengan <span class="text-yellow-300">Mudah</span></h1>
            <p class="max-w-xs text-sm leading-relaxed text-white/60">Platform terpadu untuk absensi, pengundian, pengelolaan hadiah, dan penyerahan doorprize karyawan.</p>
        </div>
        <div class="relative space-y-3">
            <div class="flex items-center gap-3"><div class="flex h-7 w-7 items-center justify-center rounded-lg bg-white/15 text-sm text-white">✓</div><span class="text-sm text-white/80">Absensi karyawan berbasis GPS</span></div>
            <div class="flex items-center gap-3"><div class="flex h-7 w-7 items-center justify-center rounded-lg bg-white/15 text-sm text-white">✓</div><span class="text-sm text-white/80">Pengundian transparan dan aman</span></div>
            <div class="flex items-center gap-3"><div class="flex h-7 w-7 items-center justify-center rounded-lg bg-white/15 text-sm text-white">✓</div><span class="text-sm text-white/80">Kelola hadiah dan pemenang</span></div>
        </div>
    </section>

    <section class="flex flex-1 items-center justify-center bg-slate-50 p-8">
        <div class="w-full max-w-sm">
            <div class="mb-8 flex items-center gap-3 lg:hidden"><div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-600 text-2xl text-white shadow-md shadow-blue-200">🎡</div><p class="text-lg font-extrabold text-blue-700">Spin Doorprize</p></div>
            <div class="mb-8"><h2 class="text-2xl font-extrabold text-slate-800">Masuk ke Akun Anda</h2><p class="mt-1.5 text-sm text-slate-500">Gunakan kredensial yang diberikan admin sistem</p></div>
            @if($errors->any())<div class="mb-5 flex items-center gap-2.5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600">⚠ <span>{{ $errors->first() }}</span></div>@endif
            <form method="post" action="{{ url('login') }}" class="space-y-4">@csrf
                <div><label class="mb-1.5 block text-xs font-semibold text-slate-600">Email</label><div class="relative"><span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">@</span><input type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="admin@perusahaan.com" autocomplete="username" class="w-full rounded-xl border border-slate-200 bg-white py-3 pl-10 pr-4 text-sm shadow-sm outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100"></div></div>
                <div><label class="mb-1.5 block text-xs font-semibold text-slate-600">Kata Sandi</label><div class="relative"><span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">●</span><input type="password" name="password" required placeholder="Kata sandi" autocomplete="current-password" class="w-full rounded-xl border border-slate-200 bg-white py-3 pl-10 pr-4 text-sm shadow-sm outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-100"></div></div>
                <label class="flex items-center gap-2.5"><input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-slate-300 accent-blue-600"><span class="text-sm text-slate-500">Ingat saya</span></label>
                <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 py-3.5 text-sm font-bold text-white shadow-lg shadow-blue-200 transition hover:bg-blue-700 active:scale-[.98]">→ <span>Masuk Sekarang</span></button>
            </form>
            <div class="mt-6 rounded-2xl border border-dashed border-blue-200 bg-blue-50 p-4"><p class="text-xs font-semibold text-blue-700">Akun demo</p><p class="mt-1 text-xs text-blue-600"> operator@example.com</p><p class="text-xs text-blue-600">Password: password</p></div>
            <p class="mt-8 text-center text-[11px] text-slate-400">© 2026 Spin Doorprize · Internal Perusahaan</p>
        </div>
    </section>
</div>
</body>
</html>
