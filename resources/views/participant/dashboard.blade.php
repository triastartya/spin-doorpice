<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Peserta · PT AST Indonesia</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-800 antialiased" @if($coupon && $coupon->winner && $coupon->winner->status !== 'dibatalkan') x-data="fireworks()" x-init="start()" @endif>
<header class="absolute left-0 right-0 top-0 z-20"><div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-6 lg:px-8">
    <a href="{{ url('/') }}" class="flex items-center gap-3 text-white"><div class="flex h-14 w-16 items-center justify-center rounded-xl bg-white p-1.5 shadow-lg"><img src="{{ asset('images/astindo-logo.png') }}" alt="PT AST Indonesia" class="h-full w-full object-contain"/></div><div><p class="text-base font-extrabold leading-tight">PT AST Indonesia</p><p class="text-xs text-white/70">Sumitomo Forestry Group</p></div></a>
</div></header>

<main class="relative isolate flex min-h-screen items-center overflow-hidden bg-[#089850] text-white">
<canvas x-show="started" class="pointer-events-none fixed inset-0 z-50 h-full w-full"></canvas>
    <div class="absolute inset-0 bg-[url("{{ asset('images/landing-bg.svg') }}")] bg-cover bg-center"></div><div class="absolute inset-0 bg-slate-950/20"></div>
    <div class="relative mx-auto w-full max-w-lg px-6 py-28">
        <div class="text-center">
            <p class="text-sm font-bold uppercase tracking-[.25em] text-emerald-100">{{ $event?->name ?? 'Peserta' }}</p>
            <h1 class="mt-3 text-4xl font-extrabold leading-tight">Halo, {{ $employee->name }}</h1>
            <p class="mt-3 text-sm text-white/70">{{ $employee->nik }}@if($employee->department) · {{ $employee->department }}@endif</p>
        </div>

        @if(!$coupon)
        <div class="mt-8 rounded-3xl border border-white/40 bg-white/95 p-8 text-center text-slate-800 shadow-2xl backdrop-blur">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-50 text-3xl">⚠</div>
            <h2 class="mt-3 text-xl font-bold text-amber-700">Belum absen</h2>
            <p class="mt-2 text-sm text-slate-500">Silakan lakukan absensi GPS terlebih dahulu untuk mendapatkan kupon.</p>
            <a href="{{ url('absensi') }}" class="mt-6 block w-full rounded-xl bg-[#089850] px-4 py-3.5 font-bold text-white shadow-lg shadow-emerald-200 hover:bg-[#007a42]">Absen Sekarang</a>
        </div>
        @else
        <div class="mt-8 rounded-3xl border border-white/40 bg-white/95 p-8 text-slate-800 shadow-2xl backdrop-blur">
            <p class="text-center text-xs font-bold uppercase tracking-widest text-slate-400">Kode kupon Anda</p>
            <div class="mt-3 rounded-2xl bg-emerald-50 py-6 text-center font-mono text-5xl font-black tracking-[.2em] text-[#089850]">{{ $coupon->code }}</div>
            <div class="mt-6 border-t border-slate-100 pt-6 text-center">
                @if(!$coupon->winner)
                <p class="text-sm text-slate-500">Belum memenangkan hadiah. Semoga beruntung!</p>
                @elseif($coupon->winner->status === 'dibatalkan')
                <p class="font-semibold text-red-600">Kemenangan dibatalkan.</p>
                @else
                <p class="text-sm text-slate-500">Selamat! Anda memenangkan</p>
                @if($coupon->winner->prize->image_path)<img src="{{ asset($coupon->winner->prize->image_path) }}" alt="{{ $coupon->winner->prize->name }}" class="mx-auto mt-4 h-32 w-32 rounded-2xl border border-slate-100 object-cover shadow-sm">@endif
                <h2 class="mt-3 text-2xl font-extrabold text-slate-800">{{ $coupon->winner->prize->name }}</h2>
                <span class="badge mt-3 {{ $coupon->winner->status === 'diserahkan' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">{{ $coupon->winner->status === 'diserahkan' ? 'Sudah diambil' : 'Menunggu pengambilan' }}</span>
                @if($coupon->winner->status === 'diserahkan' && $coupon->winner->photo_path)
                <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-4"><p class="mb-3 text-xs font-bold uppercase tracking-widest text-slate-400">Foto pengambilan hadiah</p><img src="{{ asset($coupon->winner->photo_path) }}" alt="Foto pengambilan hadiah" class="mx-auto max-h-48 rounded-xl"></div>
                @endif
                @endif
            </div>
        </div>
        @endif

        <div class="mt-6 grid grid-cols-2 gap-3"><a href="{{ url('peserta') }}" class="rounded-xl border border-white/40 bg-white/15 px-4 py-3 text-center text-sm font-bold text-white shadow-lg backdrop-blur transition hover:bg-white/25">↻ Refresh Kupon</a><form method="post" action="/peserta/logout">@csrf<button type="submit" class="w-full rounded-xl border border-white/20 px-4 py-3 text-sm font-medium text-white/70 transition hover:bg-white/10 hover:text-white">Keluar</button></form></div>
        <p class="mt-6 text-center text-xs text-white/60">© 2026 PT AST Indonesia · Spin Doorprize</p>
    </div>
</main>
<script>function fireworks(){return{started:false,_fw:null,start(){this.started=true;this.$nextTick(()=>{const canvas=this.$el.querySelector('canvas');canvas.width=innerWidth;canvas.height=innerHeight;const ctx=canvas.getContext('2d');const colors=['#ffffff','#fbbf24','#34d399','#fde68a','#86efac'];let rockets=[],particles=[],raf=null,launch=null;const boom=(x,y)=>{const c=colors[Math.floor(Math.random()*colors.length)];for(let i=0;i<60;i++){const a=Math.random()*2*Math.PI,s=Math.random()*4.5+1.5;particles.push({x,y,vx:Math.cos(a)*s,vy:Math.sin(a)*s,life:1,color:Math.random()<.75?c:colors[Math.floor(Math.random()*colors.length)]})}};const tick=()=>{ctx.clearRect(0,0,canvas.width,canvas.height);rockets=rockets.filter(r=>{r.y+=r.vy;r.vy+=.12;r.x+=r.vx;ctx.fillStyle='rgba(255,255,255,.85)';ctx.fillRect(r.x-2,r.y-8,4,10);if(r.vy>=-1){boom(r.x,r.y);return false}return true});particles=particles.filter(p=>{p.x+=p.vx;p.y+=p.vy;p.vy+=.06;p.vx*=.985;p.life-=.014;if(p.life<=0)return false;ctx.globalAlpha=Math.max(0,p.life);ctx.fillStyle=p.color;ctx.beginPath();ctx.arc(p.x,p.y,2.2,0,2*Math.PI);ctx.fill();ctx.globalAlpha=1;return true});raf=requestAnimationFrame(tick)};const spawn=()=>{rockets.push({x:canvas.width*(0.15+Math.random()*0.7),y:canvas.height+10,vx:(Math.random()-.5)*2,vy:-(8+Math.random()*4)})};spawn();spawn();launch=setInterval(spawn,1000);raf=requestAnimationFrame(tick);this._fw={raf,launch}})}}}</script>
</body>
</html>
