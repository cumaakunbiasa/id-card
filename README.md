> Pembaruan ID Card Mitra Kerja: baca **PANDUAN-MITRA.md** terlebih dahulu. Jika database belum menjalankan migration fitur foto, kelompok, zona, dan jadwal dari versi sebelumnya, jalankan `php artisan migrate`. Perubahan form lokasi bebas, tanggal tanpa jam, dan lima warna zona pada paket ini tidak menambah migration baru.

# Sistem Pengajuan ID Card — Localhost

## Pembaruan: kamera, verifikasi email, dan zona waktu WIB

Paket ini memuat perbaikan berikut:

1. **Kamera:** kebijakan browser mengizinkan akses kamera dari halaman aplikasi sendiri (`camera=(self)`). JavaScript memeriksa secure context, menampilkan pesan lebih spesifik saat izin ditolak/perangkat tidak ditemukan/kamera sedang dipakai, menunggu pratinjau siap, dan menghentikan media stream saat kamera ditutup, formulir dikirim, atau komponen dilepas.
2. **Verifikasi email:** model `User` mengimplementasikan `MustVerifyEmail`. Pendaftaran pemohon dan pembuatan akun petugas melalui `php artisan staff:create` mengirim notifikasi verifikasi. Halaman pengajuan, persetujuan, foto, ekspor, dan ID Card mewajibkan email terverifikasi. Halaman profil dan halaman verifikasi tetap dapat diakses agar pengguna dapat memperbarui email atau meminta tautan baru. Perubahan alamat email otomatis mengirim tautan verifikasi baru.
3. **Zona waktu:** default aplikasi menjadi `Asia/Jakarta`, dan koneksi MySQL/MariaDB menggunakan zona waktu sesi `+07:00`. Tidak ada migration database untuk pembaruan ini.

### Pengaturan setelah menyalin paket ke instalasi lama

Pertahankan `.env`, `APP_KEY`, dan konfigurasi database lama. Buka `.env`, lalu pastikan nilai berikut ada:

```dotenv
APP_URL=http://127.0.0.1:8000
APP_TIMEZONE=Asia/Jakarta
DB_TIMEZONE=+07:00
MAIL_MAILER=log
```

Jangan menimpa `.env` lama dengan `.env.example`. Setelah mengubah `.env`, jalankan:

```powershell
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Cara menguji kamera

- Jalankan aplikasi melalui `http://127.0.0.1:8000` atau alamat HTTPS. Browser umumnya hanya memberikan akses kamera pada secure context; alamat localhost/127.0.0.1 diperbolehkan untuk pengembangan lokal.
- Berikan izin kamera saat browser memintanya. Jika sebelumnya pernah menolak, buka pengaturan izin situs di browser dan ubah Camera menjadi Allow.
- Tutup aplikasi lain yang sedang menggunakan kamera.
- Jika aplikasi dibuka dari perangkat lain melalui alamat IP lokal biasa seperti `http://192.168.x.x`, kamera dapat diblokir browser. Gunakan HTTPS untuk akses melalui jaringan.
- Jika kamera tetap tidak tersedia, tombol upload foto tetap dapat digunakan.

### Cara menguji verifikasi email

Konfigurasi bawaan memakai `MAIL_MAILER=log`, sehingga email **tidak dikirim ke inbox**. Untuk pengujian lokal:

1. Daftarkan akun pemohon atau buat akun petugas dengan `php artisan staff:create`.
2. Buka `storage/logs/laravel.log`.
3. Cari notifikasi **Verify Email Address**, lalu salin URL verifikasi yang tercatat.
4. Buka URL tersebut pada browser yang sama dan pastikan masih login dengan akun yang bersangkutan. Gunakan host yang sama dengan `APP_URL`.
5. Jika email tidak muncul di log, periksa `MAIL_MAILER` dan `storage/logs/laravel.log`.

Untuk pengiriman sungguhan, ubah `MAIL_MAILER` menjadi `smtp` dan isi `MAIL_HOST`, `MAIL_PORT`, `MAIL_SCHEME`, `MAIL_USERNAME`, `MAIL_PASSWORD`, serta `MAIL_FROM_ADDRESS` sesuai kredensial penyedia email. Jangan memasukkan kredensial email ke source code atau membagikan file `.env`.

Akun lama dengan `email_verified_at` bernilai `NULL` harus melakukan verifikasi email sebelum menggunakan fitur pengajuan/persetujuan/ID Card. Pengguna dapat membuka `/verify-email` dan meminta tautan baru. Jika email akun lama tidak aktif, perbarui ke alamat email yang valid melalui profil atau minta pengelola sistem membantu.

### Catatan zona waktu

- Jadwal kerja baru menggunakan WIB (`Asia/Jakarta`).
- `DB_TIMEZONE=+07:00` mengatur zona waktu sesi koneksi MySQL/MariaDB untuk kolom timestamp.
- Pembaruan ini tidak mengubah atau menghapus data lama. Jika instalasi sebelumnya menyimpan timestamp dengan zona waktu database yang berbeda, periksa beberapa riwayat lama sebelum dipakai sebagai acuan resmi.

---

Paket untuk menjalankan aplikasi pada Windows melalui http://127.0.0.1:8000. Server hanya mendengarkan loopback komputer sendiri. Tidak memerlukan domain atau hosting.

Fitur Humas, K3, Keamanan, pemohon, operator, riwayat, profil, dan pemeriksaan instalasi tetap tersedia. Source menyertakan partial profil dan route admin.permohonan.history yang dibutuhkan.

## Jika memakai proyek dan database lama (disarankan untuk instalasi Anda)

1. Hentikan server lama dengan Ctrl+C dan cadangkan source serta database.
2. Salin semua isi ZIP langsung ke folder yang memiliki artisan dan composer.json; pilih replace untuk source. Jangan membuat folder proyek bersarang.
3. Pertahankan .env lama, APP_KEY, dan pengaturan database MySQL Anda. ZIP ini tidak memuat .env aktif atau database.
4. Di .env lama, sesuaikan hanya pengaturan lokal berikut:

```dotenv
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
APP_TIMEZONE=Asia/Jakarta
DB_TIMEZONE=+07:00
SESSION_SECURE_COOKIE=false
SESSION_DOMAIN=null
```

Jangan mengganti APP_KEY atau DB_DATABASE instalasi lama. Jika sebelumnya menggunakan SMTP dan tetap ingin mengirim email sungguhan, pengaturannya boleh dipertahankan. Untuk pengujian tanpa pengiriman email gunakan MAIL_MAILER=log.

5. Buka terminal di folder proyek, jalankan:

```powershell
composer install
npm ci
npm run build
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan installation:check
php artisan serve --host=127.0.0.1 --port=8000
```

Pembaruan mitra menambah migration; jalankan php artisan migrate setelah mencadangkan database. Jangan gunakan migrate:fresh.

Setelah dependensi dan build tersedia, Anda dapat menjalankan start-local.bat. Script memeriksa prasyarat, membersihkan cache config/route/view, memeriksa instalasi, dan menyalakan server. Script tidak menimpa .env atau menjalankan migration.

## Instalasi baru dari nol

Prasyarat: PHP >=8.3 sesuai composer.lock, Composer 2, ekstensi PHP yang dibutuhkan Laravel dan PDO MySQL, MySQL yang sudah berjalan, serta Node.js yang memenuhi engine Vite (^20.19.0 atau >=22.12.0). Pastikan php, composer, node dan npm tersedia di terminal.

Jalankan dari folder yang berisi artisan:

```powershell
composer install
Copy-Item .env.example .env
```

Langkah Copy-Item hanya untuk instalasi baru tanpa .env. Buat database kosong bernama izin_kerja melalui pengelola MySQL Anda, atau gunakan nama lain dan ubah DB_DATABASE. Sesuaikan DB_HOST, DB_PORT, DB_USERNAME dan DB_PASSWORD dengan konfigurasi MySQL lokal. Nilai root/password kosong pada contoh bukan jaminan cocok dengan komputer Anda.

Kemudian:

```powershell
php artisan key:generate
php artisan migrate
npm ci
npm run build
php artisan installation:check
php artisan serve --host=127.0.0.1 --port=8000
```

Buka http://127.0.0.1:8000. Gunakan alamat ini secara konsisten untuk menghindari perbedaan cookie dengan localhost.

## Akun

- Daftar lewat halaman registrasi untuk membuat akun pemohon.
- Untuk akun Humas, K3, Keamanan, atau Operator baru, jalankan php artisan staff:create dan ikuti pertanyaan terminal.
- Tidak ada akun/password bawaan. Akun lama tetap berada dalam database lama.

## Jika error sebelumnya masih muncul

```powershell
php artisan installation:check
php artisan route:list --name=admin.permohonan.history
```

Periksa folder aplikasi yang dicetak oleh installation:check. File resources/views/profile/partials/update-profile-information-form.blade.php harus ada. Route admin.permohonan.history harus terdaftar. Pastikan server dijalankan dari folder yang sama dengan folder tempat source disalin.

Jika port 8000 sudah digunakan, hentikan proses server lama atau jalankan php artisan serve --host=127.0.0.1 --port=8001 dan sesuaikan APP_URL di .env.

## Email dan pengujian

Konfigurasi contoh memakai MAIL_MAILER=log: email verifikasi dan reset password dicatat di storage/logs/laravel.log, tidak dikirim ke inbox. Untuk menguji verifikasi, salin tautan Verify Email Address dari log dan buka di browser yang masih login dengan akun terkait. Untuk mengirim email sungguhan, konfigurasi SMTP pada .env seperti dijelaskan di bagian pembaruan di atas. Log dapat memuat tautan verifikasi/reset; gunakan hanya untuk pengujian lokal dan jangan bagikan isinya.

Jalankan php artisan test pada lingkungan uji setelah dependensi lengkap. phpunit.xml menggunakan SQLite in-memory dan membutuhkan ekstensi PDO SQLite, meskipun aplikasi lokal memakai MySQL. Jalankan php artisan config:clear sebelum tes.

## Status verifikasi paket perbaikan

Source diperiksa dengan lint sintaks PHP dan kompilasi Blade. Test regresi untuk kebijakan kamera, pengiriman verifikasi email, pembatasan akses akun belum terverifikasi, perubahan email, zona waktu, lokasi bebas, jadwal tanggal, dan pemetaan warna zona tersedia di suite Pest. Suite Laravel memerlukan ekstensi PHP DOM, PDO SQLite, dan GD; build Vite memerlukan dependensi Node yang lengkap. Folder `vendor` dan `node_modules` tidak disertakan dalam ZIP, sehingga jalankan `composer install` dan `npm ci` sebelum pengujian penuh. Kamera fisik, dialog cetak, dan pengiriman SMTP juga perlu diuji pada perangkat/akun email tujuan.
