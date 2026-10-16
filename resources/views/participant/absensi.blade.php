<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Absensi Peserta · PT AST Indonesia</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-800 antialiased">
<header class="absolute left-0 right-0 top-0 z-20"><div class="mx-auto flex max-w-7xl items-center justify-start px-6 py-6 lg:px-8">
    <a href="{{ url('/') }}" class="flex items-center gap-3 text-white"><div class="flex h-14 w-16 items-center justify-center rounded-xl bg-white p-1.5 shadow-lg"><img src="{{ asset('images/astindo-logo.png') }}" alt="PT AST Indonesia" class="h-full w-full object-contain"/></div><div><p class="text-base font-extrabold leading-tight">PT AST Indonesia</p><p class="text-xs text-white/70">Sumitomo Forestry Group</p></div></a>
</div></header>

<main class="relative isolate flex min-h-screen items-center overflow-hidden bg-[#089850] text-white">
    <div class="absolute inset-0 bg-[url("{{ asset('images/landing-bg.svg') }}")] bg-cover bg-center"></div><div class="absolute inset-0 bg-slate-950/20"></div>
    <div class="relative mx-auto w-full max-w-lg px-6 py-32" x-data="attendance()">
        <div class="text-center">
            <p class="text-sm font-bold uppercase tracking-[.25em] text-emerald-100">Absensi & Kupon</p>
            <h1 class="mt-3 text-4xl font-extrabold leading-tight">{{ $event?->name ?? 'Spin Doorprize' }}</h1>
            <p class="mt-3 text-sm text-white/70">@if($event)Masukkan NIK Anda untuk mendapatkan kupon undian.@elseBelum ada acara aktif.@endif</p>
        </div>
        <div class="mt-8 rounded-3xl border border-white/40 bg-white/95 p-7 text-slate-800 shadow-2xl backdrop-blur">
            <template x-if="step === 1"><div><label class="mb-1.5 block text-xs font-semibold text-slate-600">Nomor Induk Karyawan</label><input x-model="nik" @keydown.enter="identify" inputmode="numeric" placeholder="Masukkan NIK" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-4 text-lg shadow-sm outline-none transition focus:border-[#089850] focus:ring-2 focus:ring-emerald-100"><button @click="identify" class="mt-4 w-full rounded-xl bg-[#089850] px-4 py-4 font-bold text-white shadow-lg shadow-emerald-200 transition hover:bg-[#007a42] active:scale-[.98]">Lanjutkan</button></div></template>
            <template x-if="step === 2"><div><p class="text-sm text-slate-500">Pastikan data Anda benar</p><h2 class="mt-2 text-2xl font-bold text-slate-800" x-text="name"></h2><p class="text-slate-500" x-text="department"></p><template x-if="hasCoupon"><div><p class="mt-4 rounded-xl bg-emerald-50 p-3 text-sm font-medium text-emerald-700">Anda sudah memiliki kupon <strong x-text="code"></strong>.</p><a href="{{ url('peserta') }}" class="mt-4 block w-full rounded-xl bg-[#089850] px-4 py-4 text-center font-bold text-white shadow-lg shadow-emerald-200 transition hover:bg-[#007a42]">Masuk ke Dashboard Peserta</a></div></template><template x-if="!hasCoupon"><button @click="checkin" class="mt-6 w-full rounded-xl bg-emerald-600 px-4 py-4 font-bold text-white shadow-lg shadow-emerald-200 transition hover:bg-emerald-700 active:scale-[.98]">Izinkan Lokasi & Absen</button></template><button @click="step=1" class="mt-3 w-full py-2 text-sm text-slate-400">Kembali</button></div></template>
            <template x-if="step === 3"><div class="text-center"><div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-50 text-3xl">✓</div><p class="mt-3 font-semibold text-emerald-600">Absensi berhasil</p><p class="mt-5 text-xs font-bold uppercase tracking-widest text-slate-400">Kode kupon Anda</p><div class="mt-2 rounded-2xl bg-emerald-50 py-6 font-mono text-5xl font-black tracking-[.2em] text-[#089850]" x-text="code"></div><a href="{{ url('peserta') }}" class="mt-6 block w-full rounded-xl bg-[#089850] px-4 py-3 text-center font-bold text-white shadow-lg shadow-emerald-200 hover:bg-[#007a42]">Buka Dashboard Peserta</a></div></template>
            <p x-show="error" x-text="error" class="mt-5 rounded-xl bg-red-50 p-3 text-sm text-red-700"></p>
        </div>
        <p class="mt-6 text-center text-xs text-white/60">© 2026 PT AST Indonesia · Spin Doorprize</p>
    </div>
</main>
<script>function attendance(){return{step:1,nik:'',name:'',department:'',code:'',hasCoupon:false,error:'',identify(){this.error='';fetch("{{ url('absensi/check-nik') }}",{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'},body:JSON.stringify({nik:this.nik})}).then(r=>r.json()).then(d=>{if(d.name){this.name=d.name;this.department=d.department||'';this.hasCoupon=!!d.has_coupon;this.code=d.code||'';this.step=2}else this.error=d.message||Object.values(d.errors||{}).flat()[0]||'NIK tidak ditemukan'}).catch(()=>this.error='NIK tidak ditemukan')},checkin(){this.error='';if(!navigator.geolocation){this.error='GPS tidak tersedia';return}navigator.geolocation.getCurrentPosition(p=>fetch("{{ url('absensi') }}",{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'},body:JSON.stringify({nik:this.nik,latitude:p.coords.latitude,longitude:p.coords.longitude,accuracy:p.coords.accuracy})}).then(r=>r.json()).then(d=>{if(d.code){this.code=d.code;this.step=3}else this.error=d.message||Object.values(d.errors||{}).flat()[0]||'Absensi gagal'}).catch(()=>this.error='Absensi gagal'),()=>this.error='Izin lokasi diperlukan')}}}</script>
</body>
</html>
