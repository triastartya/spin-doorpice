# Rencana Implementasi — Animasi Spin Massal Grid Dinamis

## Tujuan
Mengubah tampilan hadiah massal pada display fullscreen dari reveal kartu satu-per-satu menjadi grid kode kupon penuh yang sudah disiapkan saat status `siap`, kemudian semua kotak menampilkan angka/kode acak selama 10 detik dan berhenti pada kode pemenang server-side. Tampilan dibuat satu layar tanpa scroll untuk jumlah pemenang sesuai stok/eligible.

## Keputusan Disetujui
- Kode ditampilkan penuh dengan format `AST####`.
- Jumlah kotak = jumlah pemenang aktual (`min(stok tersisa, eligible)`), bukan seluruh peserta eligible.
- Header display massal cukup menampilkan judul: `HADIAH PEMENANG` dan nama hadiah (contoh `TOPI`).
- Grid harus responsif/dinamis agar hingga 150 kotak terlihat dalam satu viewport tanpa scroll; ukuran font/kartu menyesuaikan jumlah kotak.
- Animasi massal berjalan 10 detik, kemudian berhenti pada daftar pemenang yang sudah dikunci server.
- Setelah berhenti, grid tetap tampil kode pemenang; layar tidak melakukan reveal satu per satu.
- Hadiah utama tetap memakai digit reels dan konfigurasi durasi yang sudah ada.

## Kondisi Kode Saat Ini
- `SpinService::trigger()` sudah memilih dan menyimpan semua winner saat trigger, dengan `winner_count` aktual.
- `OperatorController::displayState()` mengirim `spin.winner_count`, `spin.winners`, dan status `berjalan`.
- `display/index.blade.php` masih menampilkan massal melalui `shown` dan `reveal()` satu kode per detik, termasuk auto-scroll.
- `displayState()` memakai durasi massal `max(3, winner_count)` sehingga perlu diubah menjadi 10 detik (atau field konfigurasi jika diputuskan kemudian).

## Perubahan Backend
1. Ubah transition guard `OperatorController::displayState()`:
   - Untuk `mode=massal`, status `berjalan` → `selesai` setelah 10 detik sejak `updated_at`.
   - Pertahankan durasi utama: `(spin_duration * 3) + last_digit_duration + buffer`.
2. Pastikan payload display mengirim `winner_count` dan `winners[].code`; frontend membentuk grid berdasarkan jumlah `spin.winners`.
3. Tidak mengubah algoritma pemilihan pemenang, stok, transaksi, locking, atau format kupon.

## Perubahan Frontend Display
1. Ganti template `state=berjalan && mode=massal`:
   - Hilangkan daftar `shown` dan reveal bertahap.
   - Header ringkas `HADIAH PEMENANG` + `spin.prize_name`.
   - Render grid sejumlah `spin.winner_count` atau `spin.winners.length`.
   - Setiap cell menampilkan kode penuh `AST####`.
2. Animasi grid massal:
   - Saat status berubah ke `berjalan`, buat array `massalCells` dengan panjang winner count.
   - Isi setiap cell dengan kode acak dari pool kode eligible (`spin.codes`), atau kode acak format AST fallback jika pool kosong.
   - Jalankan interval pengacakan seluruh cell selama 10 detik.
   - Pada status `selesai`, hentikan interval dan set cells tepat ke `spin.winners[].code` dalam urutan server.
3. Responsive one-screen layout:
   - Container display `min-h-screen overflow-hidden` untuk mode massal.
   - Hitung class berdasarkan jumlah cell: contoh ≤30 grid 5/6 kolom, ≤80 grid 8/10 kolom, ≤150 grid 10/15 kolom; gunakan CSS grid dengan `grid-template-columns: repeat(auto-fit, minmax(...))` atau style dinamis agar tidak overflow.
   - Gunakan `clamp()` untuk ukuran kartu/font: semakin banyak cell, semakin kecil padding/font.
   - Hilangkan `scrollIntoView`, `window.scrollTo`, dan auto-scroll khusus massal.
4. Status `siap` massal:
   - Tampilkan header dan grid preview/placeholder sesuai jumlah stok yang diperkirakan belum diketahui sebelum trigger; jika winner belum ada, gunakan jumlah `spin.winner_count` (saat ini 0 sebelum trigger) dan tampilkan teks "Siap diundi" tanpa grid atau grid placeholder.
5. Status `selesai`:
   - Header tetap tampil.
   - Grid final menampilkan semua kode pemenang tanpa nama/NIK/departemen per cell.
   - Ucapan selamat + gambar hadiah tetap ditampilkan sesuai desain saat ini.

## State/Timing Frontend
- Tambahkan properti Alpine: `massalCells`, `massalTimer`, `massalStartedAt`.
- Saat `berjalan` massal baru: `startMassalShuffle()`; jangan memanggil `reveal()`.
- Saat `selesai`: `stopMassalShuffle()`, isi `massalCells = spin.winners.map(w => w.code)`.
- Saat `siap` spin baru: reset timer/cells/busy agar tombol SPIN/Enter dapat digunakan tanpa refresh.
- Saat state bukan massal berjalan/selesai: hentikan interval dan bersihkan cells.
- Hanya satu interval aktif; clear interval pada state change dan sebelum start baru.

## Testing & Validasi
1. Unit/feature test backend untuk `displayState`: massal tua 10+ detik menjadi `selesai`; utama tetap memakai durasi konfigurasi.
2. Test payload memuat `winner_count` dan winner codes.
3. Manual test dengan stok 30, 100, dan 150:
   - Semua cell terlihat dalam satu viewport desktop fullscreen.
   - Kode acak berubah selama 10 detik.
   - Tepat berhenti pada seluruh kode winner server.
   - Tidak ada scroll otomatis maupun scrollbar halaman.
4. Manual test refresh display pada status `siap`, `berjalan`, dan `selesai`; state tetap konsisten.
5. Jalankan `php artisan view:cache`, `php artisan test`, dan `npm run build`.

## Risiko / Batasan
- Kode 150 cell penuh tetap kecil pada layar kecil; layout tetap satu viewport sesuai permintaan, sehingga keterbacaan optimal membutuhkan layar besar/proyektor.
- Pool kode acak di frontend hanya visual; hasil final tidak pernah dipilih browser dan tetap berasal dari winners server-side.
- Durasi massal 10 detik saat ini hardcoded sesuai keputusan; field durasi massal dapat ditambahkan terpisah bila kelak dibutuhkan.
