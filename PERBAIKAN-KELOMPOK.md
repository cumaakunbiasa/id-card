# Daftar pekerjaan dan keputusan per orang

- Dashboard pemohon, Humas, K3, dan Keamanan serta daftar pemohon dan antrean admin menampilkan satu baris per kelompok pekerjaan: pekerjaan, PIC, PT/perusahaan, dan jumlah status anggota.
- PIC adalah mandor pada data lama. Nama kolom dan role akun tetap kompatibel; mandor/PIC tetap memakai akun pemohon.
- Lihat detail menampilkan tanggal kerja tanpa jam, lokasi/zona, uraian pekerjaan, PIC, serta pekerja. PIC ditampilkan pertama; anggota dipaginasi 50 orang per halaman.
- Tombol Izinkan/Tolak dipindahkan dari antrean ke masing-masing orang dalam detail. Pemohon hanya dapat membaca. Setiap petugas hanya dapat memutuskan bagiannya sendiri, satu kali, dengan alasan wajib untuk penolakan.
- Penolakan hanya mengubah izin orang yang dipilih. PIC yang ditolak juga tidak menolak pekerja lain. Seseorang diizinkan setelah tiga bagian menyetujui orang tersebut.
- Kelompok tetap berada dalam antrean suatu bagian selama minimal satu anggotanya berstatus ditinjau dan keputusan bagian tersebut masih menunggu. Anggota yang sudah ditolak tidak menghalangi pemeriksaan anggota lainnya.
- Ringkasan tidak memberikan satu label ditolak pada seluruh kelompok; jumlah orang diizinkan/ditinjau/ditolak ditampilkan terpisah.
- Data lama tanpa group_id tetap ditampilkan sebagai pengajuan terpisah. Pemohon tidak dapat membuka data kelompok pemohon lain.
- Riwayat admin tetap per orang. Operator dan aturan cetak/masa berlaku ID card tidak berubah.

## Penerapan dan pemeriksaan

Tidak ada migrasi database baru untuk tampilan kelompok ini. Migrasi permit_submissions dari pembaruan sebelumnya tetap wajib sudah dijalankan.

Setelah menyalin pembaruan, jalankan `php artisan view:clear`. Jalankan pengujian `php artisan test --filter='PermitGroupWorkflowTest|PermitImprovementsTest|PermitHistoryTest|ReleaseReadinessTest|OperatorIdCardTest'`, kemudian seluruh suite `php artisan test`.

Uji manual dengan PIC dan dua pekerja: tolak pekerja pertama, pastikan PIC dan pekerja kedua tetap ditinjau; setujui pekerja kedua oleh ketiga bagian, pastikan hanya pekerja kedua menjadi diizinkan. Periksa antrean ketiga bagian serta akun pemohon lain. Buka detail dari dashboard dan dari antrean.

SQL agregasi yang digunakan kode sudah diperiksa dengan SQLite untuk pengelompokan, status campuran, pemisahan pemohon, data lama tanpa kelompok, dan antrean per bagian. Integritas ZIP dan bahwa aturan keputusan per orang tidak diubah juga diperiksa. Pengujian PHP/Laravel, MySQL, dan browser belum dijalankan karena PHP/Composer tidak tersedia di lingkungan pengerjaan. Tidak ada perubahan pada server/database pengguna.
