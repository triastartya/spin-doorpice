<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cara Absen · PT AST Indonesia</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 font-sans text-slate-800 antialiased">
<main class="mx-auto min-h-screen w-full max-w-[430px] overflow-hidden bg-white shadow-2xl">
    <section class="relative overflow-hidden bg-[#078d4b] px-6 pb-8 pt-7 text-white">
        <div class="absolute -right-16 -top-20 h-56 w-56 rounded-full bg-white/10"></div>
        <div class="absolute -bottom-24 -left-20 h-48 w-48 rounded-full bg-emerald-300/10"></div>
        <div class="relative flex items-center gap-3">
            <div class="flex h-12 w-14 items-center justify-center rounded-xl bg-white p-1 shadow-lg"><img src="{{ asset('images/astindo-logo.png') }}" alt="PT AST Indonesia" class="h-full w-full object-contain"></div>
            <div><p class="text-sm font-extrabold">PT AST Indonesia</p><p class="text-[10px] text-emerald-100">Sumitomo Forestry Group</p></div>
        </div>
        <div class="relative mt-9">
            <p class="text-xs font-bold uppercase tracking-[.28em] text-emerald-100">Panduan Peserta</p>
            <h1 class="mt-2 text-4xl font-black leading-[1.05]">Cara Absen<br><span class="text-yellow-300 &">& Dapatkan Kupon</span></h1>
            <p class="mt-4 max-w-xs text-sm leading-relaxed text-white/80">Ikuti 3 langkah mudah berikut sebelum ikut Spin Doorprize.</p>
        </div>
    </section>

    <section class="space-y-4 px-5 py-6">
        <div class="flex gap-4 rounded-2xl border border-slate-100 bg-slate-50 p-4 shadow-sm">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-emerald-100 text-xl font-black text-[#078d4b]">1</div>
            <div><h2 class="font-extrabold">Buka halaman absensi</h2><p class="mt-1 text-sm leading-relaxed text-slate-500">Scan QR Code absensi atau buka link yang dibagikan panitia.</p><div class="mt-3 inline-flex items-center gap-2 rounded-lg bg-white px-3 py-2 text-xs font-bold text-[#078d4b] shadow-sm"><span class="text-base">▣</span> absensi</div></div>
        </div>
        <div class="flex gap-4 rounded-2xl border border-slate-100 bg-slate-50 p-4 shadow-sm">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-emerald-100 text-xl font-black text-[#078d4b]">2</div>
            <div><h2 class="font-extrabold">Masukkan NIK</h2><p class="mt-1 text-sm leading-relaxed text-slate-500">Ketik Nomor Induk Karyawan, lalu tekan <b class="text-slate-700">Lanjutkan</b>. Pastikan nama dan departemen Anda sudah benar.</p></div>
        </div>
        <div class="flex gap-4 rounded-2xl border border-slate-100 bg-slate-50 p-4 shadow-sm">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-emerald-100 text-xl font-black text-[#078d4b]">3</div>
            <div><h2 class="font-extrabold">Izinkan lokasi & absen</h2><p class="mt-1 text-sm leading-relaxed text-slate-500">Tekan <b class="text-slate-700">Izinkan Lokasi & Absen</b>. Setelah berhasil, kode kupon akan tampil di layar.</p></div>
        </div>
    </section>

    <section class="mx-5 rounded-2xl bg-amber-50 p-4 text-sm text-amber-900 ring-1 ring-amber-200">
        <p class="font-extrabold">Perhatian</p>
        <ul class="mt-2 list-inside list-disc space-y-1 text-xs leading-relaxed"><li>Aktifkan GPS/lokasi pada HP.</li><li>Gunakan browser Chrome atau Safari.</li><li>Absen hanya dapat dilakukan di area acara dan selama jadwal dibuka.</li></ul>
    </section>
    <footer class="px-5 pb-7 pt-6 text-center text-[11px] text-slate-400">Simpan kode kupon Anda untuk proses pengundian.<br><span class="font-semibold text-[#078d4b]">Selamat mengikuti Spin Doorprize!</span></footer>
</main>
</body>
</html>
