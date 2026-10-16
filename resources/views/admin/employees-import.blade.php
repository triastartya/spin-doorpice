@extends('layouts.app')
@section('title', 'Import Karyawan')
@section('content')
<div class="mx-auto max-w-2xl">
    <h1 class="page-title">Import Karyawan</h1>
    <p class="page-sub">Upload file .xlsx dengan kolom: NIK, nama, departemen, email, status aktif.</p>
    <form method="post" enctype="multipart/form-data" class="card mt-6 p-6">@csrf<input type="file" name="file" accept=".xlsx" required class="block w-full rounded-xl border border-slate-200 bg-white p-3 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-blue-600 file:px-4 file:py-2 file:font-semibold file:text-white"><button class="btn-primary mt-5">Import File</button></form>
    @if(session('report'))<h2 class="mt-8 text-lg font-bold text-slate-800">Laporan Import</h2><div class="table-wrap mt-3"><table><thead><tr><th>#</th><th>Baris</th><th>NIK</th><th>Status</th><th>Keterangan</th></tr></thead><tbody>@foreach(session('report') as $row)<tr><td class="font-mono text-xs text-slate-400">{{ $loop->iteration }}</td><td class="font-mono">{{ $row['row'] }}</td><td class="font-mono">{{ $row['nik'] }}</td><td><span class="badge {{ ($row['status'] ?? '') === 'sukses' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">{{ $row['status'] }}</span></td><td>{{ $row['reason'] }}</td></tr>@endforeach</tbody></table></div>@endif
</div>
@endsection
