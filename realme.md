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

## 7. Deployment

- Vercel cocok untuk frontend dan API, tetapi SQLite tidak cocok karena filesystem Vercel bersifat sementara.
- Socket.IO tidak cocok di Vercel. Pilihan: layanan real-time terpisah (Pusher, Ably, Supabase Realtime), atau backend WebSocket di Render, Railway, atau Fly.io.
- Keputusan deployment belum final.

## 8. Multi Cabang

### 8.1 Asumsi

- Semua cabang dimiliki satu organisasi.

### 8.2 Hierarki Data

```
Organisasi
 └─ Cabang (branch)
     ├─ Layanan (service)  → misal: Pendaftaran, Pembayaran
     ├─ Loket (counter)     → tempat petugas melayani
     └─ Antrian (queue)     → nomor yang diambil pelanggan
```

Setiap data antrian selalu terikat ke `branch_id`.

### 8.3 Tabel

- `branches`: id, nama, kode, alamat, timezone, aktif.
- `services`: id, branch_id, nama, prefix nomor (misal `A`, `B`), aktif.
- `counters`: id, branch_id, nama, aktif.
- `users`: id, nama, role, branch_id (kosong untuk super admin).
- `counter_assignments`: user_id, counter_id, tanggal.
- `queues`: id, branch_id, service_id, nomor, status, counter_id, waktu ambil, waktu panggil, waktu selesai, tanggal lokal.
- Unik: (`branch_id`, `service_id`, `tanggal_lokal`, `nomor`).

### 8.4 Nomor Antrian

- Nomor direset per cabang dan per layanan setiap hari, mengikuti tanggal di timezone cabang.
- Format contoh: `A-001`.

### 8.5 Peran dan Akses

- **Super admin**: mengatur semua cabang, membuat cabang baru, melihat laporan gabungan.
- **Admin cabang**: mengatur layanan dan loket di cabangnya saja.
- **Petugas loket**: memanggil antrian hanya di loket yang ditugaskan pada hari itu.
- Setiap API memeriksa `branch_id` dari user, bukan hanya dari request.

### 8.6 Real-time per Cabang

- Setiap cabang punya channel sendiri (misal `branch:{id}`).
- Layar display cabang hanya menerima event cabangnya.
- Dashboard super admin berlangganan ke semua channel cabang.

### 8.7 Tampilan

- Halaman pelanggan (kiosk) per cabang, lewat URL atau QR code berbeda.
- Halaman loket/admin dengan pilihan loket setelah login.
- Layar display per cabang.
- Dashboard pusat: antrian per cabang, rata-rata waktu layanan, perbandingan antar cabang.

### 8.8 Laporan

- Per cabang, per layanan, dan gabungan, dengan filter tanggal.
- Metrik: jumlah antrian, dilewati, rata-rata waktu tunggu, rata-rata waktu layanan.

### 8.9 Dampak ke Deployment

- Database hosted (Postgres) menjadi wajib karena data dari banyak cabang harus terpusat.
- Real-time per cabang memperkuat rekomendasi layanan real-time terpisah jika tetap ingin di Vercel.

### 8.10 Tahapan

1. Satu cabang berjalan penuh, dengan model data sudah memakai `branch_id` sejak awal.
2. Manajemen cabang, layanan, dan loket dari admin.
3. Peran dan pembatasan akses per cabang.
4. Dashboard pusat dan laporan gabungan.
5. Uji dengan dua cabang dan beberapa loket di masing-masing cabang.

### 8.11 Pertanyaan Terbuka

- Apakah pelanggan boleh mengambil nomor di cabang lain, atau hanya di cabang yang dipilih?
- Apakah satu petugas boleh bertugas di lebih dari satu cabang?
