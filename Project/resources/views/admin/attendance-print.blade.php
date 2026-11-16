<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Cetak Kupon · {{ $event->name }}</title>
<style>
@page{size:A4;margin:10mm}*{box-sizing:border-box}body{margin:0;font-family:Arial,sans-serif;color:#111}.tickets{display:flex;flex-wrap:wrap;gap:5mm}.ticket{width:47mm;height:34mm;border:1px dashed #555;border-radius:3mm;padding:3mm;display:flex;flex-direction:column;page-break-inside:avoid;break-inside:avoid}.head{display:flex;align-items:center;gap:2mm;border-bottom:1px solid #bbb;padding-bottom:1.5mm}.head img{height:7mm}.head span{font-size:6pt;font-weight:bold;color:#333;text-transform:uppercase}.code{flex:1;display:flex;align-items:center;justify-content:center;font-family:'Courier New',monospace;font-size:24pt;font-weight:bold;letter-spacing:2px}.foot{border-top:1px solid #bbb;padding-top:1.5mm;font-size:6.5pt;color:#333;line-height:1.3}.foot b{display:block;font-size:7.5pt;color:#111}.no-print{margin-bottom:6mm;display:flex;justify-content:space-between;align-items:center;font-size:10pt}.no-print button{font-size:10pt;padding:2mm 5mm}@media print{.no-print{display:none}}
</style>
</head>
<body>
<div class="no-print"><span>{{ $coupons->count() }} kupon · {{ $event->name }} — {{ now()->format('d M Y H:i') }}</span><button onclick="window.print()">Cetak</button></div>
<div class="tickets">
@foreach($coupons as $coupon)
<div class="ticket">
<div class="head"><img src="{{ asset('images/astindo-logo.png') }}" alt="AST"><span>{{ $event->name }}</span></div>
<div class="code">{{ $coupon->code }}</div>
<div class="foot"><b>{{ $coupon->employee->name }}</b>{{ $coupon->employee->nik }}@if($coupon->employee->department) · {{ $coupon->employee->department }}@endif</div>
</div>
@endforeach
</div>
<script>if(location.search.includes('auto=1'))window.print()</script>
</body>
</html>
