# ID Card Mitra Kerja — localhost

## Fitur

- Satu formulir mengajukan pekerja sesuai kebutuhan ditambah satu mandor. Mandor menggunakan akun pemohon, bukan role baru.
- Nama dan foto per orang; perusahaan, lokasi kerja, jenis pekerjaan para pekerja, uraian, serta tanggal mulai dan selesai kerja berlaku untuk satu kelompok. Untuk pekerjaan/lokasi/jadwal berbeda, buat pengajuan kelompok lain.
- Setiap orang menghasilkan satu pengajuan dan satu kartu; Humas/K3/Keamanan memutuskan setiap pengajuan seperti alur sebelumnya. Tidak ada persetujuan massal otomatis.
- Foto dari kamera laptop/ponsel melalui izin browser atau upload JPG/JPEG/PNG, total maksimal 100 MB per pengajuan. Kamera memerlukan browser yang mendukung getUserMedia, localhost atau HTTPS. Jika izin ditolak gunakan upload. Pengujian kamera perangkat nyata belum dilakukan.
- Foto disimpan pada disk local privat dan dibaca melalui route berautentikasi. Pemohon hanya melihat foto pengajuannya sendiri. Operator hanya melihat foto pengajuan diizinkan. Admin pemeriksa dapat melihat seluruhnya.
- Form pemohon menerima lokasi kerja dalam bentuk teks bebas. Sistem mendeteksi zona berdasarkan kata kunci lokasi; jadwal mulai dan selesai hanya berupa tanggal tanpa jam.
- Operator memilih pekerja dari daftar pengajuan yang sudah disetujui, lalu melihat preview kartu berisi logo dan judul SISTEM PENGAJUAN ID CARD berdampingan, foto, nama, perusahaan/PT, jabatan, tanggal mulai/selesai, ID unik IK-xxxxxx, serta lokasi kerja.
- Operator memilih salah satu dari empat zona pada preview kartu: ZONA HIJAU, ZONA BIRU, ZONA KUNING, atau ZONA MERAH. Warna header mengikuti zona yang dipilih. Ukuran cetak B1 ditetapkan 55 × 85 mm. Uji preview printer, khususnya nama dan lokasi panjang.
- Menu Pendaftar & Riwayat memiliki Export semua data pekerja ke Excel. Ekspor seluruh pengajuan termasuk mandor dan data lama, semua status; filter layar tidak membatasi ekspor. Ekspor tidak memuat password, sesi, atau file foto. Data dihasilkan sebagai XLSX dengan inline strings sehingga teks pengguna tidak dijalankan sebagai formula.

## Memasang di Laragon dengan database lama

Cadangkan database dan source. Hentikan php artisan serve. Salin isi ZIP ke folder proyek yang berisi artisan, pertahankan .env dan database lama.

Jalankan dari C:\laragon8.7\www\izin-kerja:

```powershell
composer install
php artisan config:clear
php artisan migrate
npm ci
npm run build
php artisan route:clear
php artisan view:clear
php artisan installation:check
php artisan serve --host=127.0.0.1 --port=8000
```

Pembaruan form lokasi, jadwal tanggal, dan pemetaan lima zona ini tidak menambah kolom database. Kolom zona dan warna kartu yang sudah ada tetap digunakan. Jangan memakai migrate:fresh. Jangan rollback migration metadata kelompok/foto/jadwal atau warna kartu setelah ada pengajuan baru tanpa strategi backup dan pemulihan data.

## PHP dan batas upload

Di php.ini yang digunakan server Laravel/Laragon, aktifkan ekstensi zip untuk ekspor XLSX. GD diperlukan untuk pembuatan foto fake pada tes. Sesuaikan setidaknya:

```ini
upload_max_filesize = 100M
post_max_size = 110M
max_file_uploads = 10000
max_input_vars = 30000
```

Jumlah pekerja tidak dibatasi 10 oleh aplikasi, tetapi tetap mengikuti kapasitas server dan total foto 100 MB. Restart proses PHP setelah mengubah php.ini. Gunakan php --ini untuk menemukan konfigurasi CLI yang dipakai artisan serve. Jika post_max_size terlampaui, server dapat menolak request sebelum validasi form.

## Logo dan warna lokasi kerja

File config/mitra.php berisi empat pilihan zona: ZONA HIJAU (#16a34a), ZONA BIRU (#2563eb), ZONA KUNING (#ca8a04), dan ZONA MERAH (#dc2626). Kata kunci Parikesit dipetakan ke ZONA MERAH untuk kompatibilitas deteksi lokasi. Operator tetap memilih zona kartu secara manual pada preview. Logo PLN Indonesia Power yang diberikan telah dipasang pada public/logo-pln-ip.png dan dirujuk melalui konfigurasi mitra.logo.

Lokasi dan kunci zona disimpan pada pengajuan. Untuk zona yang dikenali, kartu menggunakan warna dari konfigurasi saat ini; pengubahan warna konfigurasi akan memengaruhi kartu pada zona tersebut. Lokasi kerja tetap diketik bebas oleh pemohon; pemetaan zona hanya dipakai untuk menentukan warna header. Setelah mengubah konfigurasi jalankan php artisan config:clear.

## Waktu

Form dan kartu hanya menyimpan serta menampilkan tanggal mulai dan selesai, tanpa jam. Kolom timestamp lama tetap dipakai untuk kompatibilitas data; pengajuan baru menyimpan waktu 00:00:00 untuk tanggal yang dipilih. Data lama yang memiliki jam tidak dihapus, tetapi tampilan ID Card hanya menunjukkan tanggal.

## Pengujian

```powershell
php artisan config:clear
php artisan test
```

Tes tambahan berada di tests/Feature/MitraRegistrationTest.php: 10 pekerja + mandor, jumlah tidak sesuai, privasi foto, pembatasan ekspor, dan nilai formula sebagai teks.

Di lingkungan penyusunan, Composer tidak tersedia. Folder vendor dan node_modules ada, tetapi ekstensi PHP DOM, PDO SQLite, dan GD belum aktif sehingga suite Pest tidak dapat dijalankan. Build Vite juga terhambat binding native Rolldown yang tidak tersedia. Sintaks PHP dan hasil kompilasi Blade diperiksa secara langsung; deteksi lokasi Parikesit diuji melalui service Laravel. Kamera, dialog cetak, dan hasil printer tetap perlu dicoba di komputer tujuan. Jalankan composer install, aktifkan ekstensi PHP yang diperlukan, lalu jalankan php artisan test dan npm ci && npm run build pada komputer pengembangan.
