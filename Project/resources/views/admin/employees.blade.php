@extends('layouts.app')
@section('title', 'Karyawan')
@section('content')
<div x-data="{ addOpen: false, editOpen: false, edit: { id: '', nik: '', name: '', department: '', email: '', active: true } }">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div><h1 class="page-title">Karyawan</h1><p class="page-sub">Kelola data karyawan yang berlaku untuk seluruh acara.</p></div>
        <div class="flex flex-wrap gap-3"><a href="{{ url('admin/employees-import') }}" class="btn-ghost"><svg class="icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v12m0 0 4-4m-4 4-4-4M5 20h14"/></svg>Import Excel</a><a href="{{ url('admin/employees-template') }}" class="btn-ghost">Unduh Template</a><button type="button" @click="addOpen=true" class="btn-primary"><svg class="icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>Tambah Karyawan</button></div>
    </div>

    <div class="card mt-6 p-5">
        <form method="get" class="flex flex-col gap-3 sm:flex-row">
            <div class="relative flex-1"><svg class="absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m2.35-5.65a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z"/></svg><input name="q" value="{{ request('q') }}" placeholder="Cari NIK, nama, departemen, atau email..." class="input pl-12"></div>
            <button class="btn-primary sm:px-7">Cari</button>
            @if(request('q'))<a href="{{ url('admin/employees') }}" class="btn-ghost">Reset</a>@endif
        </form>
        <div class="mt-4 flex flex-wrap gap-6 border-t border-slate-100 pt-4 text-sm text-slate-500"><span><strong class="text-slate-800">{{ $employees->total() }}</strong> karyawan terdaftar</span><span><strong class="text-emerald-600">{{ $employees->getCollection()->where('is_active', true)->count() }}</strong> aktif di halaman ini</span><span><strong class="text-slate-500">{{ $employees->getCollection()->where('is_active', false)->count() }}</strong> nonaktif di halaman ini</span></div>
    </div>

    <div class="table-wrap mt-6">
        <table class="min-w-[860px]">
            <thead><tr><th class="w-14">#</th><th>Karyawan</th><th>NIK</th><th>Departemen</th><th>Email</th><th>Status</th><th class="text-right">Aksi</th></tr></thead>
            <tbody>
                @forelse($employees as $employee)
                <tr>
                    <td class="font-mono text-xs text-slate-400">{{ ($employees->firstItem() ?? 0) + $loop->iteration - 1 }}</td>
                    <td><div class="flex items-center gap-3"><div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-sm font-bold text-blue-700">{{ collect(explode(' ', $employee->name))->take(2)->map(fn($n) => mb_substr($n,0,1))->implode('') }}</div><div><p class="font-semibold text-slate-800">{{ $employee->name }}</p><p class="text-xs text-slate-400">ID #{{ $employee->id }}</p></div></div></td>
                    <td class="font-mono font-medium text-slate-700">{{ $employee->nik }}</td>
                    <td>{{ $employee->department ?: '—' }}</td>
                    <td class="text-slate-500">{{ $employee->email ?: '—' }}</td>
                    <td><span class="badge {{ $employee->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}"><span class="mr-1.5 h-1.5 w-1.5 rounded-full {{ $employee->is_active ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>{{ $employee->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                    <td><div class="flex justify-end gap-2"><button type="button" @click="edit={id:'{{ $employee->id }}',nik:@js($employee->nik),name:@js($employee->name),department:@js($employee->department),email:@js($employee->email),active:{{ $employee->is_active ? 'true' : 'false' }}};editOpen=true" class="btn-ghost px-3 py-2">Edit</button><form method="post" action="{{ url('admin/employees/'.$employee->id) }}" onsubmit="return confirm('Hapus data {{ addslashes($employee->name) }}?')">@csrf @method('delete')<button class="btn-danger px-3 py-2">Hapus</button></form></div></td>
                </tr>
                @empty
                <tr><td colspan="6"><div class="empty-state"><div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-50 text-xl text-blue-600">♙</div><p class="font-semibold text-slate-700">{{ request('q') ? 'Karyawan tidak ditemukan' : 'Belum ada data karyawan' }}</p><p class="text-sm">{{ request('q') ? 'Coba gunakan kata kunci lain.' : 'Mulai dengan import Excel atau tambah manual.' }}</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $employees->withQueryString()->links() }}</div>

    <div x-show="addOpen" x-cloak @keydown.escape.window="addOpen=false" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div x-show="addOpen" x-transition.opacity @click="addOpen=false" class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
        <div x-show="addOpen" x-transition class="relative w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5"><div><h2 class="text-xl font-bold text-slate-800">Tambah Karyawan</h2><p class="mt-1 text-sm text-slate-500">Masukkan data karyawan baru.</p></div><button @click="addOpen=false" class="flex h-9 w-9 items-center justify-center rounded-xl text-slate-400 hover:bg-slate-100">✕</button></div>
            <form method="post" action="{{ url('admin/employees') }}" class="space-y-4 p-6">@csrf
                <div class="grid gap-4 sm:grid-cols-2"><label class="label">NIK<input name="nik" required placeholder="Nomor induk" class="input mt-1"></label><label class="label">Departemen<input name="department" placeholder="Contoh: IT" class="input mt-1"></label></div>
                <label class="label">Nama lengkap<input name="name" required placeholder="Nama karyawan" class="input mt-1"></label>
                <label class="label">Email <span class="font-normal text-slate-400">(opsional)</span><input name="email" type="email" placeholder="nama@perusahaan.com" class="input mt-1"></label>
                <label class="flex items-center gap-3 rounded-xl bg-slate-50 p-4 text-sm font-medium text-slate-700"><input type="checkbox" name="is_active" value="1" checked class="h-4 w-4 accent-blue-600">Karyawan aktif dan dapat mengikuti acara</label>
                <div class="flex justify-end gap-3 border-t border-slate-100 pt-4"><button type="button" @click="addOpen=false" class="btn-ghost">Batal</button><button class="btn-primary">Tambah Karyawan</button></div>
            </form>
        </div>
    </div>

    <div x-show="editOpen" x-cloak @keydown.escape.window="editOpen=false" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div x-show="editOpen" x-transition.opacity @click="editOpen=false" class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
        <div x-show="editOpen" x-transition class="relative w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5"><div><h2 class="text-xl font-bold text-slate-800">Edit Karyawan</h2><p class="mt-1 text-sm text-slate-500">Perbarui data karyawan.</p></div><button @click="editOpen=false" class="flex h-9 w-9 items-center justify-center rounded-xl text-slate-400 hover:bg-slate-100">✕</button></div>
            <form method="post" :action="'/admin/employees/'+edit.id" class="space-y-4 p-6">@csrf @method('put')<input type="hidden" name="is_active" value="0">
                <div class="grid gap-4 sm:grid-cols-2"><label class="label">NIK<input name="nik" x-model="edit.nik" required class="input mt-1"></label><label class="label">Departemen<input name="department" x-model="edit.department" class="input mt-1"></label></div>
                <label class="label">Nama lengkap<input name="name" x-model="edit.name" required class="input mt-1"></label>
                <label class="label">Email<input name="email" type="email" x-model="edit.email" class="input mt-1"></label>
                <label class="flex items-center gap-3 rounded-xl bg-slate-50 p-4 text-sm font-medium text-slate-700"><input type="checkbox" name="is_active" value="1" x-model="edit.active" class="h-4 w-4 accent-blue-600">Karyawan aktif</label>
                <div class="flex justify-end gap-3 border-t border-slate-100 pt-4"><button type="button" @click="editOpen=false" class="btn-ghost">Batal</button><button class="btn-primary">Simpan Perubahan</button></div>
            </form>
        </div>
    </div>
</div>
@endsection
