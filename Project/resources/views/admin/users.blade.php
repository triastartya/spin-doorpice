@extends('layouts.app')
@section('title', 'User')
@section('content')
<div x-data="{ addOpen: false, editOpen: false, edit: { id: '', name: '', email: '', role: 'operator' } }">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div><h1 class="page-title">User</h1><p class="page-sub">Kelola akun login admin dan operator.</p></div>
        <button type="button" @click="addOpen=true" class="btn-primary"><svg class="icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>Tambah User</button>
    </div>

    <div class="table-wrap mt-6">
        <table class="min-w-[700px]">
            <thead><tr><th class="w-14">#</th><th>User</th><th>Role</th><th class="text-right">Aksi</th></tr></thead>
            <tbody>
                @forelse($users as $user)
                <tr>
                    <td class="font-mono text-xs text-slate-400">{{ ($users->firstItem() ?? 0) + $loop->iteration - 1 }}</td>
                    <td><div class="flex items-center gap-3"><div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-sm font-bold text-blue-700">{{ strtoupper(mb_substr($user->name, 0, 1)) }}</div><div><p class="font-semibold text-slate-800">{{ $user->name }}</p><p class="text-xs text-slate-400">{{ $user->email }}</p></div></div></td>
                    <td><span class="badge {{ ['admin' => 'bg-blue-50 text-blue-700', 'operator' => 'bg-emerald-50 text-emerald-700', 'operator_hadiah' => 'bg-amber-50 text-amber-700'][$user->role] ?? 'bg-slate-100 text-slate-500' }}">{{ ['admin' => 'Admin', 'operator' => 'Operator', 'operator_hadiah' => 'Operator Hadiah'][$user->role] ?? $user->role }}</span></td>
                    <td><div class="flex justify-end gap-2">@if(auth()->user()->role === 'admin' || in_array($user->role, ['operator', 'operator_hadiah']))<button type="button" @click="edit={id:'{{ $user->id }}',name:@js($user->name),email:@js($user->email),role:@js($user->role)};editOpen=true" class="btn-ghost px-3 py-2">Edit</button>@if($user->id !== auth()->id())<form method="post" action="{{ url('admin/users/'.$user->id) }}" onsubmit="return confirm('Hapus akun ini?')">@csrf @method('delete')<button class="btn-danger px-3 py-2">Hapus</button></form>@endif @endif</div></td>
                </tr>
                @empty
                <tr><td colspan="4"><div class="empty-state"><p class="font-semibold text-slate-700">Belum ada user</p></div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $users->withQueryString()->links() }}</div>

    <div x-show="addOpen" x-cloak @keydown.escape.window="addOpen=false" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div x-show="addOpen" x-transition.opacity @click="addOpen=false" class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
        <div x-show="addOpen" x-transition class="relative w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5"><div><h2 class="text-xl font-bold text-slate-800">Tambah User</h2><p class="mt-1 text-sm text-slate-500">Buat akun login baru.</p></div><button @click="addOpen=false" class="flex h-9 w-9 items-center justify-center rounded-xl text-slate-400 hover:bg-slate-100">✕</button></div>
            <form method="post" action="{{ url('admin/users') }}" class="space-y-4 p-6">@csrf
                <label class="label">Nama lengkap<input name="name" required class="input mt-1"></label>
                <label class="label">Email<input name="email" type="email" required class="input mt-1"></label>
                <label class="label">Password <span class="font-normal text-slate-400">(min. 6 karakter)</span><input name="password" type="password" required minlength="6" class="input mt-1"></label>
                <label class="label">Role<select name="role" class="input mt-1"><option value="operator">Operator</option><option value="operator_hadiah">Operator Hadiah</option>@if(auth()->user()->role === 'admin')<option value="admin">Admin</option>@endif</select></label>
                <div class="flex justify-end gap-3 border-t border-slate-100 pt-4"><button type="button" @click="addOpen=false" class="btn-ghost">Batal</button><button class="btn-primary">Tambah User</button></div>
            </form>
        </div>
    </div>

    <div x-show="editOpen" x-cloak @keydown.escape.window="editOpen=false" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div x-show="editOpen" x-transition.opacity @click="editOpen=false" class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
        <div x-show="editOpen" x-transition class="relative w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-100 px-6 py-5"><div><h2 class="text-xl font-bold text-slate-800">Edit User</h2><p class="mt-1 text-sm text-slate-500">Perbarui data akun.</p></div><button @click="editOpen=false" class="flex h-9 w-9 items-center justify-center rounded-xl text-slate-400 hover:bg-slate-100">✕</button></div>
            <form method="post" :action="'/admin/users/'+edit.id" class="space-y-4 p-6">@csrf @method('put')
                <label class="label">Nama lengkap<input name="name" x-model="edit.name" required class="input mt-1"></label>
                <label class="label">Email<input name="email" type="email" x-model="edit.email" required class="input mt-1"></label>
                <label class="label">Password baru <span class="font-normal text-slate-400">(kosongkan jika tidak diubah)</span><input name="password" type="password" class="input mt-1"></label>
                <label class="label">Role<select name="role" x-model="edit.role" class="input mt-1"><option value="operator">Operator</option><option value="operator_hadiah">Operator Hadiah</option>@if(auth()->user()->role === 'admin')<option value="admin">Admin</option>@endif</select></label>
                <div class="flex justify-end gap-3 border-t border-slate-100 pt-4"><button type="button" @click="editOpen=false" class="btn-ghost">Batal</button><button class="btn-primary">Simpan Perubahan</button></div>
            </form>
        </div>
    </div>
</div>
@endsection
