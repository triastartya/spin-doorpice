@extends('layouts.app')
@section('title', 'Acara Aktif')
@section('content')
@if(!$event)
<div class="mx-auto mt-16 max-w-xl rounded-2xl border border-amber-200 bg-amber-50 p-8 text-center">
    <p class="text-4xl">🎪</p>
    <h1 class="mt-4 text-2xl font-bold text-amber-800">Belum ada acara aktif</h1>
    <p class="mt-2 text-sm text-amber-700">Hubungi admin untuk mengaktifkan acara, atau kembali lagi nanti.</p>
    @if(auth()->user()->role === 'admin')
    <a href="{{ url('admin/events') }}" class="mt-6 inline-block rounded-xl bg-blue-600 px-5 py-3 font-semibold text-white hover:bg-blue-700">Kelola Acara</a>
    @endif
</div>
@else
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <p class="text-xs font-bold uppercase tracking-widest text-slate-400">Acara aktif</p>
        <h1 class="mt-1 text-3xl font-extrabold tracking-tight text-slate-800">{{ $event->name }}</h1>
        <p class="mt-1 text-sm text-slate-500">Status: <span class="font-semibold text-blue-700">{{ strtoupper($event->status) }}</span> · Absensi {{ $event->absensi_buka?->format('d M H:i') ?? '-' }} – {{ $event->absensi_tutup?->format('d M H:i') ?? '-' }}</p>
    </div>
    <a href="{{ url('operator') }}" class="rounded-xl bg-blue-600 px-5 py-3 font-bold text-white shadow-md shadow-blue-200 hover:bg-blue-700">Buka Panel Spin</a>
</div>

<div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><p class="text-xs font-bold uppercase tracking-widest text-slate-400">Hadir</p><p class="mt-2 text-4xl font-extrabold text-blue-700">{{ $event->coupons_count }}</p></div>
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><p class="text-xs font-bold uppercase tracking-widest text-slate-400">Pemenang</p><p class="mt-2 text-4xl font-extrabold text-slate-800">{{ $event->winners_count }}</p></div>
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><p class="text-xs font-bold uppercase tracking-widest text-slate-400">Sisa Hadiah</p><p class="mt-2 text-4xl font-extrabold text-slate-800">{{ $event->prizesRemainingStock() }}</p></div>
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><p class="text-xs font-bold uppercase tracking-widest text-slate-400">Jenis Hadiah</p><p class="mt-2 text-4xl font-extrabold text-slate-800">{{ $prizes->count() }}</p></div>
</div>

<div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <a href="{{ url('admin/events/'.$event->id.'/edit') }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-blue-300 hover:shadow-md"><p class="text-2xl">⚙️</p><p class="mt-2 font-bold">Edit Acara</p><p class="text-xs text-slate-500">Jadwal, GPS, radius, token display</p></a>
    <a href="{{ url('admin/events/'.$event->id.'/prizes') }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-blue-300 hover:shadow-md"><p class="text-2xl">🎁</p><p class="mt-2 font-bold">Hadiah</p><p class="text-xs text-slate-500">Kelola stok & gambar hadiah</p></a>
    <a href="{{ url('admin/events/'.$event->id.'/attendance') }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-blue-300 hover:shadow-md"><p class="text-2xl">📍</p><p class="mt-2 font-bold">Absensi</p><p class="text-xs text-slate-500">Data kehadiran & export</p></a>
    <a href="{{ url('admin/events/'.$event->id.'/winners') }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-blue-300 hover:shadow-md"><p class="text-2xl">🏆</p><p class="mt-2 font-bold">Pemenang</p><p class="text-xs text-slate-500">Riwayat & pengambilan</p></a>
</div>

@if($recentWinners->count())
<h2 class="mt-10 text-lg font-bold text-slate-800">Pemenang Terbaru</h2>
<div class="mt-3 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left text-[11px] font-bold uppercase tracking-widest text-slate-400"><tr><th class="px-4 py-3">#</th><th>Kupon</th><th>Nama</th><th>Hadiah</th><th>Status</th></tr></thead>
        <tbody class="divide-y divide-slate-100">
            @foreach($recentWinners as $winner)
            <tr class="hover:bg-slate-50"><td class="px-4 py-3 font-mono text-xs text-slate-400">{{ $loop->iteration }}</td><td class="px-4 py-3 font-mono">{{ $winner->coupon->code }}</td><td>{{ $winner->coupon->employee->name }}</td><td class="flex items-center gap-2">@if($winner->prize->image_url)<img src="{{ asset($winner->prize->image_url) }}" class="h-8 w-8 rounded-lg object-cover">@endif{{ $winner->prize->name }}</td><td><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $winner->status === 'diserahkan' ? 'bg-emerald-50 text-emerald-700' : ($winner->status === 'dibatalkan' ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700') }}">{{ $winner->status }}</span></td></tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
@endif
@endsection
