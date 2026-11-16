# Absensi: Ikatan Perangkat–NIK + Info Perangkat di Halaman Absensi

## Tujuan
Di halaman publik `/absensi` saja: satu perangkat (browser) hanya boleh absen dengan satu NIK per acara. Buka halaman → tampilkan NIK yang terikat di perangkat itu. Halaman absensi manual operator tidak diubah.

Keputusan bersama user: identitas perangkat = cookie persisten 1 tahun; simpan User-Agent + IP di kupon; tampilkan token + UA + IP lewat popup di list absensi admin. Bypass lewat incognito/hapus-cookie diterima sebagai keterbatasan; perlindungan utama tetap "satu NIK satu kupon".

## Perubahan

### 1. Migrasi `2026_11_16_000001_add_device_tracking_to_coupons.php`
- `coupons`: tambah `device_token` string(64) nullable, `device_agent` text nullable, `device_ip` string(45) nullable, setelah `attendance_method`.
- Unique index `['event_id', 'device_token']` (NULL boleh duplikat untuk kupon manual/lama).
- Jalankan `php artisan migrate --force`.

### 2. Model `App\Models\Coupon`
- `$guarded = []` sudah ada; tidak perlu perubahan fillable.

### 3. `App\Http\Controllers\ParticipantController`
- Helper `deviceToken(Request $request): string` — ambil cookie `absensi_device`, kalau kosong generate `Str::random(40)` lalu `Cookie::queue('absensi_device', $token, 525600)` (1 tahun, httpOnly default). Panggil di `index()` dan `store()` agar cookie selalu terpasang.
- `index()`: cari kupon acara aktif berdasarkan `device_token` → kirim `deviceCoupon` (with `employee`) ke view untuk banner.
- `checkNik()`: setelah employee ditemukan — jika kupon acara aktif dengan `device_token` perangkat ini milik employee lain → `ValidationException` pesan: `"Perangkat ini sudah digunakan untuk absensi NIK {nik} ({nama})."`
- `store()`: guard yang sama sebelum transaksi; saat create kupon isi `device_token`, `device_agent` = `$request->userAgent()`, `device_ip` = `$request->ip()`; pada cabang `alreadyAttended` jika kupon belum punya `device_token` → ikat sekalian (bind manual-attended yang buka halaman dengan NIK-nya sendiri).

### 4. View `resources/views/participant/absensi.blade.php`
- Jika `$deviceCoupon` ada: banner di atas card — "Perangkat ini sudah absen dengan NIK {nik} — {nama} · kupon {code}" + tombol/link ke `peserta` (dashboard). Form tetap tampil di bawahnya.

### 5. Halaman admin absensi (list)
- `AttendanceController::index` — pastikan query kupon memuat kolom baru (select default `*`, cukup).
- `resources/views/admin/attendance.blade.php` — tambah tombol kecil "Device" di kolom Aksi tiap baris → popup Alpine (pola modal `prizes.blade.php`: overlay fixed + card) menampilkan **Token**, **User-Agent**, **IP**; kosong → "—" (kupon manual/lama tidak berikat).

### 6. Tests `tests/Feature/AbsensiTest` (tambah 3 kasus)
- Absen dengan cookie perangkat A lalu NIK berbeda via perangkat sama → 422 dengan pesan perangkat.
- NIK sama lewat perangkat yang sama → sukses / data kupon kembali.
- Absensi manual operator tetap jalan tanpa cookie (data device NULL) dan popup data muncul di `/admin/events/{id}/attendance` (`assertSee` token).

## Validasi
- `php artisan migrate --force` lalu `php artisan test` (full suite, 30 kasus lama + 3 baru).
- Manual: buka `/absensi` di dua browser (cookie beda) → absen NIK A di browser 1; browser 2 coba NIK B → ditolak; browser 1 buka ulang halaman → banner muncul; login admin → list absensi → tombol Device → popup token/UA/IP.

## Batasan (disepakati)
- Hapus cookie / incognito = perangkat baru; tidak bisa dideteksi. UA/IP hanya untuk audit manual.
- Admin/operator tidak terpengaruh CSRF; endpoint absensi sudah dikecualikan dari CSRF sebelumnya.
