@extends('layouts.app')
@section('title', 'Absensi Manual')
@section('content')
<div class="mx-auto max-w-2xl">
    <div class="flex flex-wrap items-end justify-between gap-4"><div><h1 class="page-title">Absensi Manual</h1><p class="page-sub">Absenkan peserta tanpa HP/GPS · {{ $event->name }}</p></div></div>
    <form method="get" class="mt-6 flex flex-col gap-3 sm:flex-row"><input name="q" value="{{ $q }}" required placeholder="Cari NIK, nama, atau departemen..." autofocus class="input flex-1"><button class="btn-primary sm:px-8">Cari</button></form>

    @if($errors->any())<div class="alert-error mt-6">{{ $errors->first() }}</div>@endif

    @if($q !== '')
    <div class="card mt-6 divide-y divide-slate-100">
        @forelse($employees as $employee)
        <div class="flex items-center justify-between gap-4 p-4">
            <div>
                <p class="font-semibold text-slate-800">{{ $employee->name }}</p>
                <p class="text-sm text-slate-500">{{ $employee->nik }}@if($employee->department) · {{ $employee->department }}@endif</p>
            </div>
            @if($coupon = $employee->coupons->first())
            <div class="text-right"><span class="badge bg-emerald-50 text-emerald-700">Sudah absen</span><p class="mt-1 font-mono text-sm font-bold text-emerald-700">{{ $coupon->code }}</p></div>
            @else
            <form method="post" action="{{ url('operator/manual') }}">@csrf<input type="hidden" name="employee_id" value="{{ $employee->id }}"><button class="btn-primary">Absenkan</button></form>
            @endif
        </div>
        @empty
        <div class="empty-state">Karyawan tidak ditemukan.</div>
        @endforelse
    </div>
    @endif
</div>
@endsection
