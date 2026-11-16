<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $event?->name ?? 'Spin Doorprize' }} · PT AST Indonesia</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-800 antialiased">
<header class="absolute left-0 right-0 top-0 z-20"><div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-6 lg:px-8">
    <a href="{{ url('/') }}" class="flex items-center gap-3 text-white"><div class="flex h-14 w-16 items-center justify-center rounded-xl bg-white p-1.5 shadow-lg"><img src="{{ asset('images/astindo-logo.png') }}" alt="PT AST Indonesia" class="h-full w-full object-contain"/></div><div><p class="text-base font-extrabold leading-tight">PT AST Indonesia</p><p class="text-xs text-white/70">Sumitomo Forestry Group</p></div></a>
    <a href="{{ route('login') }}" class="rounded-xl border border-white/30 bg-white/10 px-4 py-2.5 text-sm font-bold text-white backdrop-blur transition hover:bg-white hover:text-blue-700">Login Panitia</a>
</div></header>

<main>
<section class="relative isolate overflow-hidden bg-[#089850] text-white">
    <div class="absolute inset-0 bg-[url("{{ asset('images/landing-bg.svg') }}")] bg-cover bg-center"></div><div class="absolute inset-0 bg-slate-950/20"></div>
    <div class="relative mx-auto max-w-7xl px-6 pb-20 pt-40 lg:px-8 lg:pb-28 lg:pt-48">
        @if($event)
        <div class="max-w-3xl">
            <!-- <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-emerald-300/40 bg-emerald-400/15 px-4 py-2 text-sm font-semibold text-emerald-100 backdrop-blur"><span class="h-2 w-2 animate-pulse rounded-full bg-emerald-300"></span>{{ $event->status === 'aktif' ? 'Pendaftaran sedang dibuka' : 'Acara sedang berlangsung' }}</div> -->
            <p class="text-sm font-bold uppercase tracking-[.25em] text-blue-100">Event Start In</p>
            <div x-data="countdown('{{ $event->absensi_buka?->toIso8601String() }}')" x-init="start()" class="mt-3 flex gap-3 sm:gap-5"><template x-for="unit in units" :key="unit.label"><div class="min-w-[65px] rounded-2xl border border-white/20 bg-white/10 px-3 py-3 text-center shadow-xl backdrop-blur sm:min-w-[88px] sm:px-4 sm:py-4"><strong class="block text-3xl font-extrabold tracking-tight sm:text-4xl" x-text="unit.value"></strong><span class="mt-1 block text-[10px] font-bold uppercase tracking-widest text-white/70" x-text="unit.label"></span></div></template></div>
            <h1 class="mt-10 max-w-3xl text-4xl font-extrabold leading-tight tracking-tight sm:text-5xl lg:text-6xl">{{ $event->name }}</h1>
            <p class="mt-5 max-w-xl text-base leading-relaxed text-white/80 sm:text-lg">Siapkan diri Anda untuk memenangkan hadiah menarik. Absen melalui HP dan dapatkan kupon undian Anda.</p>
            <div class="mt-9 flex flex-col gap-3 sm:flex-row"><a href="{{ url('absensi') }}" class="rounded-2xl bg-white px-7 py-4 text-center font-bold text-blue-700 shadow-xl transition hover:bg-blue-50">Absen & Ambil Kupon</a></div>
        </div>
        @else
        <div class="max-w-2xl py-12"><p class="text-sm font-bold uppercase tracking-[.25em] text-blue-100">Spin Doorprize</p><h1 class="mt-5 text-5xl font-extrabold leading-tight">Rayakan momen,<br><span class="text-yellow-300">menangkan hadiah.</span></h1><p class="mt-5 text-lg text-white/80">Belum ada acara aktif saat ini. Nantikan keseruan acara doorprize berikutnya.</p><a href="{{ route('login') }}" class="mt-9 inline-block rounded-2xl bg-white px-7 py-4 font-bold text-blue-700 shadow-xl">Login Panitia</a></div>
        @endif
    </div>
</section>

@if($event)
<section class="mx-auto max-w-7xl px-6 py-16 lg:px-8"><div class="flex flex-wrap items-end justify-between gap-4"><div><p class="text-sm font-bold uppercase tracking-widest text-blue-600">Yang bisa dimenangkan</p><h2 class="mt-2 text-3xl font-extrabold text-slate-800">Hadiah Menarik</h2></div><div class="text-sm text-slate-500">{{ $prizes->sum('stock') }} hadiah tersedia</div></div><div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">@forelse($prizes as $prize)<article class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-xl">@if($prize->image_path)<img src="{{ asset($prize->image_path) }}" alt="{{ $prize->name }}" class="h-48 w-full object-cover transition duration-500 group-hover:scale-105">@else<div class="flex h-48 items-center justify-center bg-gradient-to-br from-blue-50 to-green-100 text-6xl font-extrabold text-blue-300">{{ mb_substr($prize->name, 0, 1) }}</div>@endif<div class="p-5"><div class="flex items-start justify-between gap-2"><h3 class="font-bold text-slate-800">{{ $prize->name }}</h3><span class="badge shrink-0 {{ $prize->category === 'utama' ? 'bg-amber-50 text-amber-700' : 'bg-blue-50 text-blue-700' }}">{{ ucfirst($prize->category) }}</span></div><p class="mt-3 text-sm text-slate-500">Stok tersedia <strong class="text-slate-700">{{ $prize->stock - $prize->taken }}</strong></p></div></article>@empty<p class="text-slate-500">Hadiah akan segera diumumkan.</p>@endforelse</div></section>
<section class="border-t border-slate-200 bg-white"><div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-5 px-6 py-10 text-center sm:flex-row sm:text-left lg:px-8"><div><h2 class="text-xl font-bold text-slate-800">Sudah hadir di lokasi acara?</h2><p class="mt-1 text-sm text-slate-500">Lakukan absensi sekarang untuk mendapatkan kupon undian.</p></div><a href="{{ url('absensi') }}" class="rounded-xl bg-blue-600 px-6 py-3 font-bold text-white shadow-md shadow-blue-200 hover:bg-blue-700">Mulai Absensi →</a></div></section>
@endif
</main>
<footer class="border-t border-slate-200 bg-slate-50 py-8 text-center text-sm text-slate-400">PT AST Indonesia · Sumitomo Forestry Group · Spin Doorprize</footer>
<script>function countdown(date){return{target:date,units:[],start(){this.tick();setInterval(()=>this.tick(),1000)},tick(){let diff=Math.max(0,new Date(this.target)-new Date());let s=Math.floor(diff/1000);this.units=[{label:'Hari',value:String(Math.floor(s/86400)).padStart(2,'0')},{label:'Jam',value:String(Math.floor(s%86400/3600)).padStart(2,'0')},{label:'Menit',value:String(Math.floor(s%3600/60)).padStart(2,'0')},{label:'Detik',value:String(s%60).padStart(2,'0')}];if(diff===0)this.units[0].value='00'}}}</script>
</body>
</html>
