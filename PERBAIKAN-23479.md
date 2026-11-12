# Pengajuan, upload, dan notifikasi

Perubahan ini mempertahankan jadwal berupa tanggal saja. Aturan masa berlaku/cetak kartu (nomor 5) serta revisi/pembatalan (nomor 6) tidak berubah.

- Total semua foto dalam satu pengajuan maksimal 100 MiB (104857600 byte), termasuk mandor. Batas ini bukan 100 MB per pekerja. Format hanya JPG/JPEG/PNG; isi gambar dan ekstensi diperiksa. Dimensi maksimum tetap 6000 × 6000.
- Formulir jumlah pekerja tidak memiliki batas 10 lagi. Minimal satu pekerja, ditambah satu mandor. Jumlah pekerja mengikuti kapasitas browser/server dan total foto, bukan tak terbatas secara fisik.
- Token UUID pada formulir digunakan kembali saat validasi gagal. Lock pengguna dan unique index memastikan retry token yang sama tidak membuat kelompok maupun email job kedua. Token baru berarti pengajuan baru. Isi yang diubah dengan token yang sudah berhasil tidak memperbarui pengajuan lama.
- Notifikasi aplikasi disimpan dalam transaksi pengajuan. Satu email job per akun Humas/K3/Keamanan disimpan di database yang sama sebelum commit. Jika penyimpanan job gagal, seluruh pengajuan dibatalkan dan foto dibersihkan. SMTP dijalankan worker setelah commit, bukan di request pengguna.
- Email mencoba ulang sampai lima kali. Pengiriman email memakai mekanisme at-least-once: gangguan setelah SMTP menerima email dapat menyebabkan email terkirim ulang. Pencegahan pengajuan ganda tidak menjamin exactly-once SMTP.
- Ada halaman Notifikasi dan penanda sudah dibaca. Pemohon menerima notifikasi dalam aplikasi ketika petugas memberi keputusan.

## Langkah penerapan

1. Cadangkan aplikasi/database. Terapkan source baru dan jalankan `php artisan migrate --force` di server tujuan setelah peninjauan.
2. Sesuaikan PHP dengan `deploy/uploads.ini.example`, lalu restart PHP-FPM atau Apache/Laragon. Nginx memakai `client_max_body_size 110m`, sebagai ruang untuk multipart; aplikasi tetap membatasi foto 100 MiB. Selaraskan juga batas reverse proxy bila ada.
3. Contoh PHP membolehkan 10000 berkas dan 30000 input per request. Ini batas operasional contoh, bukan janji jumlah tak terbatas. Jika jumlah pekerja melampaui kapasitas itu, naikkan dengan mempertimbangkan memori, waktu proses, dan total foto. PHP dapat menolak/memotong upload sebelum Laravel jika batas server lebih kecil.
4. Atur SMTP di `.env`: `MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`. Gunakan `MAIL_SCHEME` sesuai penyedia dan `APP_URL` HTTPS yang benar. Jangan kirim kredensial melalui chat.
5. Jalankan `php artisan config:clear`. Jalankan worker sebagai service yang dipantau: `php artisan queue:work permit_mail --queue=permit-mail --tries=5 --timeout=60`. Koneksi khusus ini memakai database utama dan tabel jobs. Pantau `php artisan queue:failed`, lalu retry job yang relevan setelah penyebab kegagalan diperbaiki. Pastikan tabel jobs dan failed_jobs sudah dimigrasikan.
6. Jalankan `php artisan test`. Fokus: `php artisan test --filter='MitraRegistrationTest|PermitNotificationTest|PermitSubmissionControlsTest|UploadSecurityTest|PermitImprovementsTest'`.
7. Uji browser: 25 pekerja, upload JPG/PNG, penolakan WebP, tanggal sama, ulangi pengiriman token yang sama, dan inbox tiga petugas. Uji SMTP menggunakan akun uji dengan izin penerima; pastikan email masuk dan tautan membutuhkan login. Uji dua request token sama secara bersamaan pada database produksi ekuivalen.

## Status validasi saat penyerahan

Sintaks JavaScript dan integritas paket diperiksa. PHP/Composer tidak tersedia di lingkungan pengerjaan, sehingga test suite PHP, migrasi, uji browser, concurrency database, dan pengiriman SMTP nyata belum dijalankan. Tidak ada database/server produksi yang diubah atau email nyata yang dikirim.

Bundle JavaScript bawaan diperbarui terbatas pada konstanta 100 MiB dan pesan terkait, dengan nama aset baru untuk cache. Source sesuai perubahan tersebut. Build Vite penuh belum dijalankan; jalankan `npm ci` dan `npm run build` dalam lingkungan build yang sesuai sebelum rilis produksi.
