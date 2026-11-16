# Plan: Field Loket + Import hadiah.xlsx + Gambar Internet

## Context
- `hadiah.xlsx`: 28 jenis hadiah (7 utama, 21 massal), kolom NO/JENIS/SPESIFIKASI/QTY/Loket/jenis.
- Tabel `prizes`: name, category (massal|utama), stock, image_path, spin_duration (default 10), last_digit_duration (default 5). Belum ada kolom loket.
- `PrizeController` sudah punya CRUD + upload gambar manual (uploads/prizes).
- Import Excel sudah ada pola untuk karyawan (`EmployeeImportService` + halaman admin) — pakai maatwebsite/excel (sudah terinstal).
- Durasi yang diminta: per digit = 5 (`spin_duration`), digit terakhir = 7 (`last_digit_duration`).

## Implementasi

### 1. Kolom loket
- Migration: `string('loket')->nullable()->after('stock')` pada prizes.
- `PrizeController::validated()`: tambah `'loket' => ['nullable', 'max:255']`.
- View `admin/prizes.blade.php`: input loket di form, kolom loket di tabel (badge kecil).

### 2. Import hadiah.xlsx
- `PrizeImportService`: parse baris (skip header); mapping:
  - JENIS + SPESIFIKASI → `name` = "JENIS — SPESIFIKASI" (mis. "Kulkas (2 pintu) — Sharp"), trim spasi.
  - QTY → `stock`; jenis → `category` (Hadiah Utama→utama, lainnya→massal).
  - Loket → `loket` (kosong → null).
  - `spin_duration = 5`, `last_digit_duration = 7` (konstanta permintaan user).
- Duplikat per event: skip jika kombinasi name sama sudah ada (laporan baris).
- Route `GET/POST admin/events/{event}/prizes/import` (middleware sama seperti prizes) + tombol "Import Excel" di halaman hadiah + view upload sederhana dengan laporan hasil (pola employees-import).
- Target event: event aktif (`ActiveEvent::get()`), proteksi `ActiveEvent::authorize`.

### 3. Gambar dari internet
- Setelah import, jalankan pengambilan gambar sekali jalan (bukan fitur aplikasi permanen): unduh gambar per hadiah dari pencarian produk (Google/Bing image) memakai skrip lokal Python/curl ke `public/uploads/prizes/`, lalu set `image_path`.
- Sumber: hasil pencarian gambar produk; simpan hanya file dengan tipe jpg/png/webp dan ukuran wajar (<2 MB). Jika gagal/timeout → biarkan `image_path` null dan laporkan (admin tetap bisa upload manual).
- Catatan lisensi: gambar internet untuk keperluan internal event — sebutkan di hasil.

## Validasi
- Migration jalan; halaman prizes menampilkan kolom loket; CRUD manual tetap lolos validasi.
- Import 28 baris: 7 utama + 21 massal, stok total 700, spin_duration=5, last_digit_duration=7 semua.
- Tiap prize punya file gambar valid atau null; extension sesuai; tampil di halaman hadiah.
- Tes: `php artisan test` suite absensi tetap hijau; route list menampilkan import prizes.

## Out of scope
- Tidak mengubah logika spin/undian.
- Tidak membuat fitur pencarian gambar permanen di UI (hanya proses sekali saat import ini).
