# Teknis Pengacakan Nomor Kupon dan Penentuan Pemenang

## Ringkasan

Sistem tidak mengacak atau mengganti nomor kupon peserta. Nomor kupon dibuat berurutan dan unik dalam satu event, misalnya `AST0001`, `AST0002`, dan seterusnya. Yang diacak adalah **daftar kupon eligible** saat operator menjalankan spin hadiah.

## 1. Pembuatan nomor kupon

Saat absensi berhasil, sistem mengambil nomor urut terbesar pada event tersebut, menambahkan satu, lalu membuat kode:

```text
next = MAX(coupons.sequence untuk event) + 1
code = AST + nomor sequence 4 digit
```

Contoh:

```text
sequence 1   -> AST0001
sequence 27  -> AST0027
sequence 504 -> AST0504
```

Database menjaga keunikan dengan constraint:

```text
UNIQUE(event_id, employee_id)
UNIQUE(event_id, code)
```

Artinya satu peserta hanya bisa memiliki satu kupon per event dan satu kode tidak dapat dipakai dua kali dalam event yang sama.

## 2. Menentukan peserta eligible

Sebelum pengundian, `SpinService::eligibleCoupons()` mengambil kupon yang memenuhi seluruh syarat berikut:

1. Berasal dari event yang sedang di-spin.
2. Karyawan masih aktif (`employees.is_active = true`).
3. Belum memiliki record pemenang pada tabel `winners`.
4. Kupon manual tetap eligible karena tetap merupakan kupon resmi event; perbedaannya hanya metode absensi (`manual` atau `gps`).

Kupon yang sudah menang tidak dimasukkan kembali, sehingga satu kupon tidak dapat memenangkan hadiah berikutnya secara normal.

## 3. Metode pengacakan

Pemilihan dilakukan di `app/Services/SpinService.php` menggunakan:

```php
$picked = $eligible->shuffle()->take($count);
```

`shuffle()` adalah fungsi Laravel Collection. Pada versi Laravel yang digunakan, fungsi ini meneruskan proses ke `Illuminate\Collections\Arr::shuffle()`, yang memakai PHP `Random\Randomizer` dengan engine `Random\Engine\Secure`.

Dengan demikian metode yang dipakai adalah **secure random shuffle** berbasis generator acak kriptografis PHP, bukan urutan alfabetis, nomor kupon terakhir, atau `rand()` sederhana.

Setelah daftar diacak:

- Hadiah kategori `utama` mengambil 1 kupon.
- Hadiah kategori `massal` mengambil sebanyak stok hadiah yang tersedia atau sebanyak peserta eligible jika jumlahnya lebih sedikit.
- `take($count)` mengambil sejumlah kupon pertama dari hasil shuffle.

Secara statistik, setiap kupon eligible mempunyai peluang yang sama untuk berada di setiap posisi hasil pengacakan, dengan asumsi daftar eligible dan jumlah hadiah tetap.

## 4. Waktu pengacakan dan perlindungan transaksi

Pengacakan terjadi saat operator menekan tombol spin pada tahap trigger, bukan saat kupon dibuat. Proses trigger dibungkus `DB::transaction()` dan mengunci event, hadiah, serta spin dengan `lockForUpdate()`.

Perlindungan ini mencegah dua proses bersamaan memakai stok hadiah atau kupon eligible yang sama secara tidak konsisten. Record pemenang ditulis ke tabel `winners` dan dikaitkan ke `spin_id`.

## 5. Tampilan roda/layar undian

Urutan kupon yang ditampilkan pada roda diambil oleh `wheelSnapshot()`. Daftar ini dipakai untuk visualisasi layar dan bukan sumber keputusan pemenang. Keputusan resmi dibuat server ketika `trigger()` dijalankan, kemudian pemenang disimpan ke database.

Jika jumlah kupon melebihi batas tampilan (`cap`), snapshot dipotong untuk kebutuhan tampilan. Pemenang dari spin aktif tetap dimasukkan ke snapshot supaya hasil visual dapat menampilkan pemenang yang benar.

## 6. Audit hasil

Setiap hasil pengundian dapat ditelusuri melalui:

- `spins`: event, hadiah, operator, mode, jumlah pemenang, status, dan waktu.
- `winners`: kupon pemenang, hadiah, spin, status penyerahan, waktu pengambilan, dan operator penyerahan.
- `coupons`: kode kupon, peserta, event, waktu absensi, metode absensi, dan data GPS jika tersedia.

Sistem tidak mengubah kode kupon pemenang. Kode yang tampil di hasil undian adalah kode kupon asli yang tersimpan di database.

## 7. Batasan dan catatan teknis

- Pengacakan bukan pengundian berbasis bobot; semua kupon eligible memiliki satu peluang yang sama.
- Satu peserta dengan satu kupon memiliki satu peluang dalam setiap spin.
- Kupon yang sudah menang dikeluarkan dari daftar eligible berikutnya.
- Pembatalan kemenangan tidak otomatis mengubah riwayat spin atau membuat kode baru.
- Untuk audit publik, simpan export pemenang dan attendance setelah event selesai beserta waktu pengundian.

## Referensi implementasi

- `app/Services/SpinService.php:14` — membentuk daftar eligible.
- `app/Services/SpinService.php:66-75` — menentukan jumlah pemenang dan mengacak kandidat saat menyiapkan spin.
- `app/Services/SpinService.php:91-134` — mengacak ulang dan menyimpan pemenang saat trigger.
- `app/Services/SpinService.php:25-41` — snapshot kupon untuk layar roda.
- `database/migrations/2026_01_01_000003_create_spin_tables.php:45-57` — struktur dan constraint kupon.
