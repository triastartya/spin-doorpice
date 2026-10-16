<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Spin Doorprize')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-800 antialiased">
<div class="min-h-screen" x-data="{ open: false }">
    <div x-show="open" @click="open=false" class="fixed inset-0 z-30 bg-slate-900/30 lg:hidden"></div>
    <aside class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col border-r border-slate-200 bg-white shadow-xl transition-transform lg:translate-x-0" :class="open ? 'translate-x-0' : ''">
        <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-5"><div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600 text-white shadow-md shadow-blue-200"><svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="8"/><path stroke-linecap="round" d="M12 4v4m0 8v4m8-8h-4M8 12H4"/></svg></div><div><p class="text-sm font-extrabold tracking-tight text-blue-700">Spin Doorprize</p><p class="text-[10px] text-slate-400">Sistem undian karyawan</p></div></div>
        @php($activeEvent = \App\Support\ActiveEvent::get())
        <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-5 text-[15px] font-semibold">
            <div><p class="mb-2 px-3 text-[10px] font-bold uppercase tracking-widest text-slate-400">Utama</p>
                <a href="{{ url(auth()->user()?->role === 'operator' ? 'event' : 'dashboard') }}" class="nav-link {{ request()->is('dashboard') || request()->is('event') ? 'nav-active' : '' }}">Dashboard</a>
                <a href="{{ url('operator') }}" class="nav-link {{ request()->is('operator') ? 'nav-active' : '' }}">Spin Doorprize</a>
                <a href="{{ url('operator/kupon') }}" class="nav-link {{ request()->is('operator/kupon*') ? 'nav-active' : '' }}">Penyerahan</a>
            </div>
            @if($activeEvent)<div><p class="mb-2 px-3 text-[10px] font-bold uppercase tracking-widest text-slate-400">Acara Aktif · {{ Str::limit($activeEvent->name, 18) }}</p>
                <a href="{{ url('admin/events/'.$activeEvent->id.'/edit') }}" class="nav-link {{ request()->is('admin/events/*/edit') ? 'nav-active' : '' }}">Edit Acara</a><a href="{{ url('admin/events/'.$activeEvent->id.'/prizes') }}" class="nav-link {{ request()->is('admin/events/*/prizes') ? 'nav-active' : '' }}">Hadiah</a><a href="{{ url('admin/events/'.$activeEvent->id.'/attendance') }}" class="nav-link {{ request()->is('admin/events/*/attendance*') ? 'nav-active' : '' }}">Absensi</a><a href="{{ url('admin/events/'.$activeEvent->id.'/winners') }}" class="nav-link {{ request()->is('admin/events/*/winners*') ? 'nav-active' : '' }}">Pemenang</a>
            </div>@endif
            <div><p class="mb-2 px-3 text-[10px] font-bold uppercase tracking-widest text-slate-400">Data</p><a href="{{ url('admin/employees') }}" class="nav-link {{ request()->is('admin/employees*') ? 'nav-active' : '' }}">Karyawan</a>@if(auth()->user()?->role === 'admin')<a href="{{ url('admin/events') }}" class="nav-link {{ request()->is('admin/events') || request()->is('admin/events/create') ? 'nav-active' : '' }}">Semua Acara</a>@endif</div>
        </nav><div class="border-t border-slate-100 px-4 py-3 text-center text-[10px] text-slate-400">Spin Doorprize · internal</div>
    </aside>
    <div class="min-w-0 w-full lg:pl-64"><header class="sticky top-0 z-30 flex items-center justify-between border-b border-slate-200 bg-white/95 px-5 py-3 backdrop-blur lg:px-8"><button class="rounded-lg border border-slate-200 p-2 text-slate-600 lg:hidden" @click="open = !open"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16"/></svg></button><div class="hidden text-sm text-slate-400 sm:block">Panel <span class="mx-2">/</span> <span class="font-semibold text-slate-700">@yield('title', 'Dashboard')</span></div><div class="ml-auto flex items-center gap-3 text-sm"><span class="hidden font-medium sm:block">{{ auth()->user()?->name }}</span><div class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-100 font-bold text-blue-700">{{ strtoupper(substr(auth()->user()?->name ?? 'U',0,1)) }}</div><form method="post" action="{{ url('logout') }}">@csrf<button class="rounded-lg bg-slate-100 px-3 py-2 font-medium hover:bg-slate-200">Keluar</button></form></div></header>
        <main class="mx-auto max-w-7xl px-5 py-8 lg:px-8">@if(session('success'))<div class="alert-success mb-6">{{ session('success') }}</div>@endif @if($errors->any())<div class="alert-error mb-6">{{ $errors->first() }}</div>@endif @yield('content')</main>
    </div>
</div></body></html>
