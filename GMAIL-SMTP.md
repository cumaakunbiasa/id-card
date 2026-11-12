# Email admin melalui Gmail SMTP Laravel

Pengirim: idmitrakerja@gmail.com. Email pengajuan dikirim ke seluruh akun Humas, K3, dan Keamanan menggunakan alamat email akun di database. Token pengajuan mencegah kelompok dan job kedua untuk request ulang yang sama. Setiap penerima memperoleh job terpisah, maksimal lima percobaan. SMTP dapat menghasilkan email duplikat jika koneksi terputus setelah pesan diterima penyedia; tidak ada jaminan exactly-once.

Paket ini mengganti integrasi Firebase dengan channel mail Laravel. Firebase, Firestore dan paket Blaze tidak diperlukan. Data izin, kelompok pekerjaan, upload 100 MB, format JPG/JPEG/PNG dan keputusan per orang tetap tersedia.

## Aktifkan akun pengirim

1. Pada akun idmitrakerja@gmail.com, aktifkan Verifikasi 2 Langkah Google.
2. Buat App Password melalui https://myaccount.google.com/apppasswords . Bila opsi tidak tersedia karena kebijakan akun, gunakan akun yang mendukungnya atau integrasi OAuth; password akun biasa tidak digunakan.
3. Isi App Password langsung pada MAIL_PASSWORD di .env server. Jangan bagikan melalui chat, git, atau log. Password belum diisi dalam ZIP.

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_SCHEME=smtp
MAIL_USERNAME=idmitrakerja@gmail.com
MAIL_PASSWORD=APP_PASSWORD_ANDA
MAIL_FROM_ADDRESS=idmitrakerja@gmail.com
MAIL_FROM_NAME="Sistem Pengajuan ID Card"
MAIL_URL=
```

Ganti placeholder APP_PASSWORD_ANDA. Port 587 memakai STARTTLS otomatis pada transport SMTP Symfony bawaan Laravel. Jangan gunakan MAIL_SCHEME=tls; nilai skema transport adalah smtp. Pastikan ekstensi openssl aktif dan server dapat mengakses smtp.gmail.com:587. Timeout SMTP diatur 30 detik.

MAIL_* ini juga berlaku untuk email verifikasi/reset password Laravel. Jangan menimpa APP_KEY, konfigurasi database, atau pengaturan server Anda ketika menyalin pembaruan. Salin hanya nilai MAIL_* yang diperlukan ke .env yang sedang dipakai. .env bawaan ZIP menyiapkan Gmail tanpa App Password; tetap perlu pengisian lokal.

## Jalankan aplikasi dan worker

Pastikan migrasi sebelumnya, termasuk permit_submissions, jobs, failed_jobs dan notifications, sudah diterapkan. Tidak ada migrasi baru untuk penggantian Gmail ini.

```bash
php artisan config:clear
php artisan view:clear
php artisan queue:restart
php artisan queue:work permit_mail --queue=permit-mail --tries=5 --timeout=60
```

Worker berjalan pada terminal kedua jika lokal. Biarkan aplikasi/worker aktif dan komputer tersambung internet. Di server, jalankan worker sebagai service yang dipantau. Koneksi khusus permit_mail tetap memakai database utama, walaupun QUEUE_CONNECTION=sync pada .env lokal. Pengajuan tidak menunggu koneksi SMTP.

Atur APP_URL ke URL yang benar untuk tautan email. Alamat localhost hanya dapat dibuka dari perangkat penerima sendiri; admin di perangkat lain memerlukan URL server yang bisa diakses mereka.

## Jika sebelumnya sudah memakai paket Firebase

Salin source Gmail lengkap. Hapus folder firebase, config/firebase_email.php, app/Notifications/Channels/FirebaseEmailChannel.php dan tests/Feature/FirebaseEmailChannelTest.php yang tersisa dari versi lama, serta FIREBASE_EMAIL_* dari .env jika masih ada. ZIP baru tidak menyertakan berkas integrasi tersebut.

Job Firebase yang sudah tersimpan di tabel jobs/failed_jobs tetap membawa nama channel lama. Jangan langsung menjalankan ulang job tersebut setelah channel lama dihapus. Periksa dan tangani job lama sebelum pergantian; jangan hapus semua antrean. Jika terdapat job Firebase tertunda, informasikan jumlah/statusnya agar dapat disiapkan migrasi khusus tanpa email ganda. Worker lama harus direstart setelah pembaruan.

## Pengujian dan operasi

```bash
php artisan test --filter='PermitNotificationTest|PermitSubmissionControlsTest|PermitGroupWorkflowTest'
```

Setelah konfigurasi lokal selesai, uji satu pengajuan dengan penerima admin uji yang disetujui. Pastikan email muncul untuk Humas/K3/Keamanan, periksa folder spam, dan buka tautan. Periksa php artisan queue:failed bila email gagal, lalu retry hanya job terkait setelah penyebab diperbaiki.

Batas pengiriman Gmail tetap berlaku; tidak ada janji email tanpa batas atau selalu masuk inbox. Pengiriman ini tidak memakai akses Gmail pada ChatGPT.

Status penyerahan: isi ZIP dan source dibandingkan dengan versi kelompok pekerjaan. Konfigurasi Gmail dan channel antrean diperiksa secara statis. PHP/Composer tidak tersedia, sehingga suite PHP, SMTP nyata, dan UI belum dijalankan. Tidak ada email nyata dikirim.

Rujukan:
- https://support.google.com/mail/answer/185833
- https://developers.google.com/workspace/gmail/imap/imap-smtp
