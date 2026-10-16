@extends('layouts.app')
@section('title', 'Hadiah')
@section('content')
<div x-data="{ addOpen: false, search: '', category: 'semua' }">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <a href="{{ auth()->user()->role === 'admin' ? '/admin/events' : '/event' }}" class="text-sm font-semibold text-blue-600 hover:text-blue-700">← Kembali</a>
            <h1 class="page-title mt-3">Hadiah</h1>
            <p class="page-sub">Kelola hadiah untuk {{ $event->name }}</p>
        </div>
        <button type="button" @click="addOpen = true" class="btn-primary">
            <svg class="icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Hadiah
        </button>
    </div>

    <div class="card mt-6 p-5">
        <div class="grid gap-4 md:grid-cols-[1fr_auto]">
            <div>
                <label class="label">Cari hadiah</label>
                <div class="relative">
                    <svg class="absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m2.35-5.65a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z"/></svg>
                    <input x-model="search" type="search" placeholder="Cari nama hadiah..." class="input pl-12">
                </div>
            </div>
            <div>
                <label class="label">Kategori</label>
                <div class="flex rounded-xl bg-slate-100 p-1">
                    <button type="button" @click="category='semua'" :class="category==='semua' ? 'bg-white text-blue-700 shadow-sm' : 'text-slate-500'" class="rounded-lg px-4 py-2 text-sm font-semibold transition">Semua</button>
                    <button type="button" @click="category='utama'" :class="category==='utama' ? 'bg-white text-blue-700 shadow-sm' : 'text-slate-500'" class="rounded-lg px-4 py-2 text-sm font-semibold transition">Utama</button>
                    <button type="button" @click="category='massal'" :class="category==='massal' ? 'bg-white text-blue-700 shadow-sm' : 'text-slate-500'" class="rounded-lg px-4 py-2 text-sm font-semibold transition">Massal</button>
                </div>
            </div>
        </div>
        <div class="mt-4 flex flex-wrap gap-4 border-t border-slate-100 pt-4 text-sm text-slate-500">
            <span><strong class="text-slate-800">{{ $prizes->count() }}</strong> jenis hadiah</span>
            <span><strong class="text-slate-800">{{ $prizes->sum('stock') }}</strong> total stok</span>
            <span><strong class="text-emerald-600">{{ $prizes->sum('taken') }}</strong> terdistribusi</span>
        </div>
    </div>

    <div class="mt-6 grid gap-5 xl:grid-cols-2">
        @forelse($prizes as $prize)
        <article x-show="('{{ strtolower(addslashes($prize->name)) }}'.includes(search.toLowerCase())) && (category === 'semua' || category === '{{ $prize->category }}')" x-transition class="card overflow-hidden">
            <div class="flex gap-4 p-5">
                @if($prize->image_path)
                <img src="{{ asset($prize->image_path) }}" alt="{{ $prize->name }}" class="h-24 w-24 shrink-0 rounded-2xl border border-slate-100 object-cover">
                @else
                <div class="flex h-24 w-24 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-3xl font-bold text-blue-600">{{ mb_substr($prize->name, 0, 1) }}</div>
                @endif
                <div class="min-w-0 flex-1">
                    <div class="flex items-start justify-between gap-3"><h2 class="truncate text-lg font-bold text-slate-800">{{ $prize->name }}</h2><span class="badge {{ $prize->category === 'utama' ? 'bg-amber-50 text-amber-700' : 'bg-blue-50 text-blue-700' }}">{{ ucfirst($prize->category) }}</span></div>
                    <div class="mt-4 grid grid-cols-3 gap-2 text-center"><div class="rounded-xl bg-slate-50 p-2"><strong class="block text-lg text-slate-800">{{ $prize->stock }}</strong><span class="text-[11px] text-slate-400">Stok</span></div><div class="rounded-xl bg-slate-50 p-2"><strong class="block text-lg text-emerald-600">{{ $prize->taken }}</strong><span class="text-[11px] text-slate-400">Terdistribusi</span></div><div class="rounded-xl bg-slate-50 p-2"><strong class="block text-lg text-blue-600">{{ $prize->stock - $prize->taken }}</strong><span class="text-[11px] text-slate-400">Tersisa</span></div></div>
                </div>
            </div>
            <details class="border-t border-slate-100 bg-slate-50/50">
                <summary class="cursor-pointer px-5 py-3 text-sm font-semibold text-blue-600">Edit hadiah</summary>
                <div class="px-5 pb-5">
                    <form method="post" enctype="multipart/form-data" action="{{ url('admin/prizes/'.$prize->id) }}" class="grid gap-3 sm:grid-cols-2">@csrf @method('put')
                        <label class="label">Nama<input name="name" value="{{ $prize->name }}" required class="input mt-1"></label>
                        <label class="label">Kategori<select name="category" class="input mt-1"><option value="utama" @selected($prize->category==='utama')>Utama</option><option value="massal" @selected($prize->category==='massal')>Massal</option></select></label>
                        <label class="label">Stok<input name="stock" type="number" min="1" value="{{ $prize->stock }}" required class="input mt-1"></label><label class="label">Durasi per digit (detik) <span class="font-normal text-slate-400">digit 1–3</span><input name="spin_duration" type="number" min="5" max="60" value="{{ $prize->spin_duration ?? 10 }}" class="input mt-1"></label><label class="label">Durasi digit terakhir (detik)<input name="last_digit_duration" type="number" min="1" max="60" value="{{ $prize->last_digit_duration ?? 20 }}" class="input mt-1"></label><label class="label">Ganti gambar<input name="image" type="file" accept="image/jpeg,image/png,image/webp" class="input mt-1 p-2"></label>
                        <button class="btn-primary sm:col-span-2">Simpan Perubahan</button>
                    </form>
                    <form method="post" action="{{ url('admin/prizes/'.$prize->id) }}" class="mt-3" onsubmit="return confirm('Hapus hadiah ini?')">@csrf @method('delete')<button class="btn-danger w-full">Hapus Hadiah</button></form>
                </div>
            </details>
        </article>
        @empty
        <div class="empty-state card xl:col-span-2"><div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-50 text-2xl">🎁</div><strong class="text-slate-700">Belum ada hadiah</strong><span>Tambahkan hadiah pertama untuk acara ini.</span></div>
        @endforelse
    </div>

    <div x-show="addOpen" x-cloak @keydown.escape.window="addOpen=false" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div x-show="addOpen" x-transition.opacity @click="addOpen=false" class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
        <div x-show="addOpen" x-transition class="relative w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5"><div><h2 class="text-xl font-bold text-slate-800">Tambah Hadiah</h2><p class="mt-1 text-sm text-slate-500">Isi detail hadiah baru.</p></div><button type="button" @click="addOpen=false" class="flex h-9 w-9 items-center justify-center rounded-xl text-slate-400 hover:bg-slate-100">✕</button></div>
            <form method="post" enctype="multipart/form-data" action="{{ url('admin/events/'.$event->id.'/prizes') }}" class="space-y-4 p-6">@csrf
                <label class="label">Nama hadiah<input name="name" required placeholder="Contoh: Sepeda Motor" class="input mt-1"></label>
                <div class="grid gap-4 sm:grid-cols-2"><label class="label">Kategori<select name="category" class="input mt-1"><option value="utama">Hadiah Utama</option><option value="massal">Hadiah Massal</option></select></label><label class="label">Jumlah stok<input name="stock" type="number" min="1" required placeholder="1" class="input mt-1"></label></div>
                <label class="label">Durasi per digit (detik) <span class="font-normal text-slate-400">digit 1–3</span><input name="spin_duration" type="number" min="5" max="60" value="10" class="input mt-1"></label><label class="label">Durasi digit terakhir (detik)<input name="last_digit_duration" type="number" min="1" max="60" value="20" class="input mt-1"></label><label class="label">Gambar hadiah <span class="font-normal text-slate-400">(opsional)</span><input name="image" type="file" accept="image/jpeg,image/png,image/webp" class="input mt-1 p-2"></label>
                <div class="flex justify-end gap-3 border-t border-slate-100 pt-4"><button type="button" @click="addOpen=false" class="btn-ghost">Batal</button><button class="btn-primary">Tambah Hadiah</button></div>
            </form>
        </div>
    </div>
</div>
@endsection
