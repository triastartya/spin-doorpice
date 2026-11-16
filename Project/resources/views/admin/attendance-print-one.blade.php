<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Kupon {{ $coupon->code }} · {{ $event->name }}</title>
<style>
@page{size:A4;margin:0}*{box-sizing:border-box}body{margin:0;font-family:Arial,sans-serif;color:#111;background:#fff;display:flex;align-items:center;justify-content:center;min-height:100vh}.ticket{width:150mm;height:90mm;border:2px dashed #444;border-radius:6mm;display:flex;overflow:hidden;page-break-inside:avoid}.stub{width:30mm;background:#078d4b;color:white;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:2mm;padding:3mm}.stub img{width:22mm;background:white;border-radius:2mm;padding:1.5mm}.stub small{font-size:6.5pt;text-align:center;line-height:1.3}.stub b{font-size:11pt}.body{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:6mm;gap:3mm}.body small{font-size:8pt;letter-spacing:2px;color:#666;font-weight:bold}.body h1{margin:0;font-family:'Courier New',monospace;font-size:40pt;letter-spacing:4px;color:#078d4b}.body p{margin:0;font-size:10pt;color:#333}.body p b{font-size:13pt;color:#111}.no-print{position:fixed;top:5mm;right:5mm;font-size:10pt;padding:2mm 5mm}@media print{.no-print{display:none}}
</style>
</head>
<body>
<button class="no-print" onclick="window.print()">Cetak</button>
<div class="ticket">
<div class="stub"><img src="{{ asset('images/astindo-logo.png') }}" alt="AST"><small>{{ $event->name }}</small><b>#{{ $coupon->sequence }}</b></div>
<div class="body">
<small>KODE KUPON UNDIAN</small>
<h1>{{ $coupon->code }}</h1>
<p><b>{{ $coupon->employee->name }}</b><br>{{ $coupon->employee->nik }}@if($coupon->employee->department) · {{ $coupon->employee->department }}@endif</p>
<small>Simpan kupon ini untuk proses pengundian</small>
</div>
</div>
<script>window.addEventListener('load',()=>setTimeout(()=>window.print(),400))</script>
</body>
</html>
