# Sistem Antrian Web — Alur

## 1. Fitur Inti

- Pelanggan mengambil nomor antrian lewat halaman web atau tablet di lokasi.
- Petugas loket memanggil nomor berikutnya, lalu menandai selesai, dilewati, atau dipindah loket.
- Layar display menampilkan nomor yang sedang dipanggil dan diperbarui otomatis tanpa refresh.
- Riwayat dan laporan sederhana: jumlah antrian per hari, rata-rata waktu layanan.

## 2. Alur Data

- Tabel `queues`: nomor, layanan, status (`waiting`, `called`, `served`, `skipped`), waktu ambil, loket.
- Tabel `counters`: daftar loket dan petugas yang ditugaskan.
- Setiap perubahan status dikirim ke semua klien yang terhubung secara real-time.

## 3. Alur Pengguna

1. Pelanggan memilih layanan dan mengambil nomor antrian.
2. Sistem menyimpan nomor dengan status `waiting`.
3. Petugas loket menekan "Panggil berikutnya", status berubah menjadi `called`, dan nomor tampil di layar display.
4. Petugas menandai `served` jika selesai, atau `skipped` jika pelanggan tidak hadir.
5. Layar display dan semua halaman loket menerima update lewat WebSocket.

## 4. Stack yang Direncanakan

- Backend: Node.js + Express (alternatif: Laravel).
- Database: SQLite untuk tahap awal, PostgreSQL atau MySQL jika loket sudah banyak.
- Real-time: Socket.IO (atau Server-Sent Events jika hanya server ke klien).
- Frontend: HTML + JavaScript; halaman pelanggan, halaman loket/admin, dan halaman display dibuat terpisah.

## 5. Langkah Pengerjaan

1. Siapkan proyek dan database, buat tabel `queues` dan `counters`.
2. Buat API: ambil nomor, panggil berikutnya, ubah status.
3. Tambahkan WebSocket untuk update real-time.
4. Buat halaman pelanggan, halaman loket/admin, dan halaman display.
5. Tambahkan login petugas dan uji di beberapa perangkat.

## 6. Status

- [ ] Struktur proyek dan database
- [ ] API antrian
- [ ] Real-time update
- [ ] Halaman pelanggan
- [ ] Halaman loket/admin
- [ ] Halaman display
- [ ] Login petugas
- [ ] Uji multi-perangkat
