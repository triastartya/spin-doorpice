@extends('layouts.app')
@section('title', 'Penyerahan Hadiah')
@section('content')
<div class="mx-auto max-w-2xl" x-data="handover({{ $winner && $winner->status === 'menang' ? 'true' : 'false' }})">
    <h1 class="page-title">Penyerahan Hadiah</h1>
    <p class="page-sub">Input atau scan kode kupon pemenang.</p>
    <form method="get" class="mt-6 flex flex-col gap-3 sm:flex-row"><input name="code" value="{{ $code }}" required placeholder="ABC123" autofocus class="input flex-1 font-mono text-lg uppercase"><button class="btn-primary sm:px-8">Cari</button></form>

    @if($code && !$winner)<div class="alert-error mt-8">Kupon <strong class="font-mono">{{ $code }}</strong> tidak ditemukan atau bukan pemenang.</div>@endif

    @if($winner)
    <div class="card mt-8 p-7">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="font-mono text-3xl font-black text-emerald-600">{{ $winner->coupon->code }}</p>
                <p class="mt-3 text-xl font-bold text-slate-800">{{ $winner->coupon->employee->name }}</p>
                <p class="text-slate-500">{{ $winner->coupon->employee->nik }} · {{ $winner->coupon->employee->department }}</p>
            </div>
            <div class="text-right">
                @if($winner->prize->image_path)<img src="{{ asset($winner->prize->image_path) }}" class="ml-auto h-24 w-24 rounded-xl object-cover" alt="{{ $winner->prize->name }}">@endif
                <p class="mt-2 text-sm text-slate-500">Hadiah</p>
                <p class="text-lg font-bold text-slate-800">{{ $winner->prize->name }}</p>
                <span class="badge mt-3 {{ $winner->status === 'menang' ? 'bg-amber-50 text-amber-700' : ($winner->status === 'diserahkan' ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700') }}">{{ ucfirst($winner->status) }}</span>
            </div>
        </div>

        @if($winner->photo_path)
        <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-4"><p class="mb-3 text-xs font-bold uppercase tracking-widest text-slate-400">Foto bukti pengambilan</p><img src="{{ asset($winner->photo_path) }}" alt="Bukti pengambilan" class="max-h-64 rounded-xl"></div>
        @endif

        <form method="post" action="{{ url('operator/kupon/handover') }}" enctype="multipart/form-data" class="mt-7 space-y-4">@csrf
            <input type="hidden" name="code" value="{{ $winner->coupon->code }}">
            <input type="hidden" name="photo_data" :value="photoData">
            <template x-if="camOpen || photoData">
                <div>
                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-black"><video x-ref="video" x-show="camOpen" autoplay playsinline class="aspect-video w-full object-cover"></video><canvas x-ref="canvas" class="hidden"></canvas><img x-show="!camOpen && photoData" :src="photoData" alt="Pratinjau foto" class="aspect-video w-full object-contain"></div>
                    <div class="mt-3 flex flex-wrap gap-3">
                        <button type="button" x-show="camOpen" @click="capture()" class="btn-primary">📷 Ambil Foto</button>
                        <button type="button" x-show="camOpen" @click="stop()" class="btn-ghost">Tutup Kamera</button>
                        <button type="button" x-show="!camOpen && photoData" @click="start()" class="btn-ghost">Ulangi Foto</button>
                        <button type="button" x-show="!camOpen && photoData" @click="photoData=''" class="btn-danger">Hapus Foto</button>
                    </div>
                </div>
            </template>
            <button type="button" x-show="!camOpen && !photoData" @click="start()" {{ $winner->status !== 'menang' ? 'disabled' : '' }} class="btn-ghost w-full">📷 Ambil Foto Bukti <span class="font-normal text-slate-400">(opsional)</span></button>
            <button {{ $winner->status !== 'menang' ? 'disabled' : '' }} class="btn-success w-full py-3.5">Serahkan Hadiah</button>
        </form>

        @if($winner->status !== 'dibatalkan')
        <form method="post" action="{{ url('operator/kupon/'.$winner->id.'/cancel') }}" class="mt-4 flex flex-col gap-3 sm:flex-row">@csrf
            <input name="reason" required placeholder="Alasan pembatalan (wajib)" class="input flex-1">
            <button class="btn-danger">Batalkan</button>
        </form>
        @endif

        @if($winner->taken_at)<p class="mt-4 text-sm text-slate-500">Diserahkan {{ $winner->taken_at->format('d M Y H:i') }}</p>@endif
        @if($winner->cancel_reason)<p class="mt-4 text-sm text-red-600">Dibatalkan: {{ $winner->cancel_reason }}</p>@endif
    </div>
    @endif
</div>
<script>
function handover(){return{camOpen:false,photoData:'',stream:null,async start(){this.camOpen=true;this.photoData='';try{this.stream=await navigator.mediaDevices.getUserMedia({video:{facingMode:'environment',width:{ideal:1280},height:{ideal:720}},audio:false});await this.$nextTick();this.$refs.video.srcObject=this.stream}catch(e){this.camOpen=false;alert('Kamera tidak dapat diakses. Pastikan izin kamera diberikan dan halaman dibuka via HTTPS/localhost.')}},stop(){this.stream?.getTracks().forEach(t=>t.stop());this.stream=null;this.camOpen=false},capture(){const v=this.$refs.video,c=this.$refs.canvas;c.width=v.videoWidth;c.height=v.videoHeight;c.getContext('2d').drawImage(v,0,0);this.photoData=c.toDataURL('image/jpeg',0.85);this.stop()},destroy(){this.stop()}}}
</script>
@endsection
