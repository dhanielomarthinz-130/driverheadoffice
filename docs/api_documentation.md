# Dokumentasi API - TMS WH Operation

Dokumen ini menjelaskan semua fungsi/aksi (actions) yang tersedia di dalam file `api.php`. Setiap request ke API menggunakan parameter query `action` (contoh: `api.php?action=nama_aksi`).

---

## Daftar Isi
1. [Autentikasi & Profil Pengguna](#1-autentikasi--profil-pengguna)
2. [Manajemen Pengguna & Hak Akses (User & Role Management)](#2-manajemen-pengguna--hak-akses-user--role-management)
3. [Penugasan Driver & Pengiriman (WH Operation)](#3-penugasan-driver--pengiriman-wh-operation)
4. [Permintaan Penjemputan (Pickup Requests)](#4-permintaan-penjemputan-pickup-requests)
5. [Manajemen Kendaraan (Vehicles)](#5-manajemen-kendaraan-vehicles)
6. [Manajemen Lokasi & Pelacakan GPS (Locations & GPS Tracking)](#6-manajemen-lokasi--pelacakan-gps-locations--gps-tracking)
7. [Tugas & Vendor Ekspedisi (Expedisi/Pihak Ketiga)](#7-tugas--vendor-ekspedisi-expedisipihak-ketiga)
8. [Pelaporan & Log Sistem](#8-pelaporan--log-sistem)

---

## 1. Autentikasi & Profil Pengguna

### `login`
* **Metode**: `POST`
* **Fungsi**: Memproses login pengguna ke dalam sistem.
* **Parameter POST**:
  * `username`: Username pengguna
  * `password`: Password pengguna
* **Return**: JSON status sukses/gagal, role, dan ketersediaan menu (web/mobile).

### `get_my_profile`
* **Metode**: `GET`
* **Fungsi**: Mengambil data profil pengguna yang sedang login (id, username, name, role, phone_number) beserta performa pickup-nya.
* **Return Tambahan**:
  * `today_count`: Jumlah `pickup_requests` yang dibuat user hari ini.
  * `performance`: Persentase pickup request user berstatus `completed` dari total request-nya (default 100 jika belum pernah request).

### `update_my_profile`
* **Metode**: `POST`
* **Fungsi**: Memperbarui profil (nama & username) pengguna yang sedang login.
* **Parameter POST**:
  * `name`: Nama lengkap baru
  * `username`: Username baru (akan dicek keunikannya)

### `change_my_password`
* **Metode**: `POST`
* **Fungsi**: Mengubah password mandiri untuk pengguna yang sedang login.
* **Parameter POST**:
  * `old_password`: Password lama (untuk verifikasi)
  * `new_password`: Password baru (minimal 6 karakter)

---

## 2. Manajemen Pengguna & Hak Akses (User & Role Management)

### `get_all_users`
* **Metode**: `GET`
* **Fungsi**: Mengambil semua daftar pengguna. Role superadmin disembunyikan jika pengakses bukan superadmin.
* **Parameter GET (Opsional)**: `role` (filter berdasarkan role), `search` (pencarian teks).

### `register`
* **Metode**: `POST`
* **Fungsi**: Mendaftarkan pengguna baru (Admin, Driver, dll.).

### `update_user`
* **Metode**: `POST`
* **Fungsi**: Mengubah informasi pengguna lain (Nama, Role, Nomor Telp, Masa Aktif).

### `toggle_user_status`
* **Metode**: `POST`
* **Fungsi**: Mengaktifkan atau menonaktifkan pengguna (`is_active` = 1 atau 0).

### `delete_user`
* **Metode**: `POST`
* **Fungsi**: Menghapus data pengguna secara permanen.

### `change_password`
* **Metode**: `POST`
* **Fungsi**: Mengubah password pengguna lain secara paksa (oleh admin).

### `bulk_delete_users`
* **Metode**: `POST`
* **Fungsi**: Menghapus banyak pengguna sekaligus secara massal.

### `bulk_extend_users`
* **Metode**: `POST`
* **Fungsi**: Memperpanjang masa aktif akun beberapa pengguna sekaligus (dalam jumlah hari).

### `get_roles`
* **Metode**: `GET`
* **Fungsi**: Mengambil daftar role/jabatan yang terdaftar.

### `add_role` / `edit_role` / `delete_role`
* **Metode**: `POST`
* **Fungsi**: Manajemen tipe role (tambah, edit, dan hapus) serta konfigurasi warna & ikon representatif.

### `get_permissions`
* **Metode**: `GET`
* **Fungsi**: Mengambil daftar hak akses menu tertentu untuk role tertentu.

### `update_permission`
* **Metode**: `POST`
* **Fungsi**: Memperbarui izin akses menu (`can_access`) dan izin menulis (`can_write`) untuk suatu role.

---

## 3. Penugasan Driver & Pengiriman (WH Operation)

### `assign_task`
* **Metode**: `POST`
* **Fungsi**: Memberikan tugas pengiriman (Delivery/Pickup) baru kepada driver internal.
* **Fitur Khusus**:
  * Secara otomatis menormalkan input **No Surat Jalan** & **Catatan** menjadi **HURUF BESAR (UPPERCASE)**.
  * Jika `pickup_id` diisi (assign dari `pickup_requests`), file SJ/Goods yang belum di-upload akan diambil dari record pickup, dan status `pickup_requests` otomatis menjadi `approved`.
  * Memerlukan akses tulis menu `assign_tasks` (atau role `admin`).
* **Parameter POST**:
  * `driver_id`, `origin_name`, `dest_name`, `dest_lat`, `dest_lng`, `task_type` (default `antar`), `surat_jalan`, `total_koli`, `notes`, `target_date`, `surat_jalan_file[]` (Foto SJ), `goods_file[]` (Foto Barang).
  * **Opsional**: `origin_id`, `dest_id` (ID master lokasi), `pickup_id` (ID dari `pickup_requests` jika di-assign dari permintaan penjemputan).

### `get_deliveries`
* **Metode**: `GET`
* **Fungsi**: Mengambil daftar penugasan aktif/riwayat pengiriman, lengkap dengan info driver, kendaraan aktif, koordinat GPS terakhir driver, dan file (SJ/POD/Goods) dalam bentuk array.
* **Parameter GET (semua opsional)**:
  * `driver_id`: Filter berdasarkan driver tertentu.
  * `type`: Filter `task_type` (`antar` / `kirim`).
  * `status`: Filter status (`pending`, `in_transit`, `completed`, `canceled`).
  * `date_from`, `date_to`: Filter rentang tanggal berdasarkan `target_date` (fallback ke `created_at`).
  * `target_date`: Filter exact match tanggal target.
  * `search`: Pencarian teks pada SJ, origin, destination, atau nama driver.

### `get_delivery`
* **Metode**: `GET`
* **Fungsi**: Mengambil detail satu penugasan berdasarkan ID.

### `edit_delivery`
* **Metode**: `POST`
* **Fungsi**: Mengubah informasi penugasan secara detail (Rute, Driver, Jadwal, SJ, Koli, Catatan). Menormalkan input **SJ** & **Catatan** menjadi **UPPERCASE**.

### `quick_edit_delivery`
* **Metode**: `POST`
* **Fungsi**: Mengubah satu kelompok kolom penugasan secara cepat berdasarkan parameter `field`. Input **No Surat Jalan** otomatis di-convert ke **UPPERCASE**. Hanya tugas berstatus `pending` yang bisa di-edit (kecuali untuk `reassign`).
* **Parameter POST**:
  * `id`: ID delivery.
  * `field`: Tipe edit. Nilai yang didukung:
    * `driver`: Ganti driver + jadwal. Param: `driver_id`, `target_date`.
    * `route`: Ganti rute origin/destination. Param: `origin_id`, `dest_id`, `origin_name`, `dest_name`, `dest_lat`, `dest_lng`.
    * `sj`: Ganti SJ & koli. Param: `surat_jalan`, `total_koli`.
    * `reassign`: **Buat tugas baru** (clone dari tugas asal, biasanya untuk re-assign tugas yang sudah `canceled`). Param: `driver_id`, `target_date`, `status` (default `pending`). Tugas asal tidak diubah.

### `delete_delivery`
* **Metode**: `POST`
* **Fungsi**: Menghapus data penugasan driver.

### `update_status`
* **Metode**: `POST`
* **Fungsi**: Digunakan oleh Driver di Mobile App untuk mengubah status tugas (`pending` -> `in_transit` -> `completed` / `canceled`) serta mengunggah foto bukti POD.
* **Parameter POST**:
  * `delivery_id`: ID penugasan.
  * `status`: `in_transit` | `completed` | `canceled`.
  * **Khusus `in_transit`**: `vehicle_id` aktif driver otomatis di-attach dari `driver_active_vehicles` dan `start_time` di-set ke `NOW()`.
  * **Khusus `completed` / `canceled`**: `receiver_name`, `late_reason`, `driver_notes`, `surat_jalan_file[]` (foto POD bisa multiple).
* **Auto-sync**: Jika tugas berasal dari `pickup_requests` (punya `pickup_id`), status `pickup_requests` ikut diupdate: `completed` → `completed`, `canceled` → balik ke `pending`.

### `mark_shared`
* **Metode**: `POST`
* **Fungsi**: Menandai bahwa detail tugas/status pengiriman telah dibagikan (shared).

### `get_driver_summary`
* **Metode**: `GET`
* **Fungsi**: Mengambil ringkasan aktivitas harian driver (tugas aktif, koordinat, jenis penugasan).

### `get_calendar_summary`
* **Metode**: `GET`
* **Fungsi**: Menghitung jumlah tugas per status pada rentang tanggal kalender untuk driver tertentu.

### `get_monthly_summary`
* **Metode**: `GET`
* **Fungsi**: Mengambil rekap performa tugas bulanan driver internal (jumlah selesai, cancel).

### `get_driver_kpi`
* **Metode**: `GET`
* **Fungsi**: Mengambil metrik KPI performa penyelesaian tugas driver internal.

---

## 4. Permintaan Penjemputan (Pickup Requests)

### `request_pickup`
* **Metode**: `POST`
* **Fungsi**: Mengajukan permintaan penjemputan barang baru oleh user (misal store/toko).
* **Parameter POST**:
  * `surat_jalan`, `origin_name`, `destination_name`, `total_koli`, `notes`, `scheduled_date`, `surat_jalan_files[]`, `goods_files[]`.

### `get_pickup_requests`
* **Metode**: `GET`
* **Fungsi**: Mengambil semua daftar permintaan penjemputan (menunggu proses, sedang jalan, selesai).

### `delete_pickup_request`
* **Metode**: `POST`
* **Fungsi**: Menghapus ajuan permintaan penjemputan yang belum diproses.

### `get_pending_counts`
* **Metode**: `GET`
* **Fungsi**: Menghitung jumlah request pickup dan tugas ekspedisi yang berstatus `pending` (butuh tindakan/proses segera).

---

## 5. Manajemen Kendaraan (Vehicles)

### `get_vehicles`
* **Metode**: `GET`
* **Fungsi**: Mengambil daftar mobil beserta driver yang sedang aktif memakainya dan tujuan aktifnya.

### `get_vehicles_raw`
* **Metode**: `GET`
* **Fungsi**: Mengambil list kendaraan murni tanpa relasi status driver.

### `get_vehicle` / `add_vehicle` / `edit_vehicle` / `delete_vehicle`
* **Metode**: `GET` / `POST`
* **Fungsi**: CRUD data mobil/kendaraan (Nama & Nomor Plat).

### `assign_vehicle`
* **Metode**: `POST`
* **Fungsi**: Menetapkan mobil aktif untuk driver tertentu.

### `release_vehicle`
* **Metode**: `POST`
* **Fungsi**: Melepas ikatan mobil aktif dari driver (tombol "Lepas Mobil").

### `get_active_vehicle`
* **Metode**: `GET`
* **Fungsi**: Mengecek mobil apa yang sedang aktif digunakan driver tertentu.

---

## 6. Manajemen Lokasi & Pelacakan GPS (Locations & GPS Tracking)

### `get_locations`
* **Metode**: `GET`
* **Fungsi**: Mengambil semua daftar lokasi yang terdaftar (gudang, store, vendor).

### `get_location` / `add_location` / `edit_location` / `delete_location`
* **Metode**: `GET` / `POST`
* **Fungsi**: CRUD data master lokasi (Nama, tipe, kota, alamat, koordinat lat & lng).

### `update_location`
* **Metode**: `POST`
* **Fungsi**: Mengirimkan koordinat GPS terbaru dari driver di HP/mobile ke server (update live tracking).

### `get_drivers`
* **Metode**: `GET`
* **Fungsi**: Mengambil list semua driver aktif (role `driver`, `is_active = 1`) lengkap dengan koordinat GPS terakhir, jumlah tugas harian (`pending`/`in_transit` pada hari ini), total tugas aktif, daftar tujuan aktif (format `tujuan|status` dipisah `||`), serta kendaraan yang sedang dipakai. Endpoint utama untuk halaman Monitor Live, Assign Tasks, dan Timeline.

### `get_driver_location`
* **Metode**: `GET`
* **Fungsi**: Mengambil koordinat GPS terakhir satu driver tertentu untuk dirender di peta live monitoring.

### `get_location_history`
* **Metode**: `GET`
* **Fungsi**: Mengambil rekam jejak koordinat perjalanan driver pada hari ini untuk digambar rutenya di peta.

### `get_gps_alerts`
* **Metode**: `GET`
* **Fungsi**: Mendeteksi driver yang sedang berstatus `in_transit` namun GPS-nya tidak mengirimkan update lokasi lebih dari 30 menit.

### `resolve_maps_link`
* **Metode**: `POST`
* **Fungsi**: Mengurai link share Google Maps/koordinat menjadi data latitude dan longitude desimal.

---

## 7. Tugas & Vendor Ekspedisi (Expedisi/Pihak Ketiga)

### `get_expedisi_tasks`
* **Metode**: `GET`
* **Fungsi**: Mengambil daftar penugasan ekspedisi (vendor luar) dengan filter rentang tanggal, status, vendor, dan pencarian teks.

### `add_expedisi_task`
* **Metode**: `POST`
* **Fungsi**: Membuat tugas pengiriman baru untuk vendor ekspedisi luar.
* **Fitur Khusus**: Secara otomatis menormalkan input **No Surat Jalan**, **Nama Sopir**, **Plat Nomor**, dan **Catatan** menjadi **HURUF BESAR (UPPERCASE)**.

### `start_expedisi_task`
* **Metode**: `POST`
* **Fungsi**: Mengubah status tugas ekspedisi menjadi `in_transit` (memulai perjalanan).

### `complete_expedisi_task`
* **Metode**: `POST`
* **Fungsi**: Menyelesaikan tugas ekspedisi, memasukkan nama penerima, catatan penyelesaian, dan mengunggah foto bukti selesai (POD).

### `delete_expedisi_task`
* **Metode**: `POST`
* **Fungsi**: Menghapus tugas ekspedisi.

### `get_expedisi_vendors` / `add_expedisi_vendor` / `get_expedisi_vendor` / `edit_expedisi_vendor` / `delete_expedisi_vendor`
* **Metode**: `GET` / `POST`
* **Fungsi**: CRUD Vendor ekspedisi luar (Nama vendor, contact person, nomor telepon, alamat).

### `get_expedisi_summary`
* **Metode**: `GET`
* **Fungsi**: Mengambil ringkasan aktivitas pengiriman ekspedisi hari ini / yang belum selesai.

### `get_expedisi_monthly_summary`
* **Metode**: `GET`
* **Fungsi**: Rekap total tugas ekspedisi bulanan per vendor.

### `get_expedisi_unique_drivers`
* **Metode**: `GET`
* **Fungsi**: Mengambil list vendor-vendor ekspedisi yang unik.

### `get_expedisi_kpi`
* **Metode**: `GET`
* **Fungsi**: Mengambil performa KPI penyelesaian pengiriman dari masing-masing vendor ekspedisi luar.

---

## 8. Pelaporan & Log Sistem

### `get_tracking_report`
* **Metode**: `GET`
* **Fungsi**: Mengambil data terperinci untuk rekap laporan pelacakan (tracking report), mencakup rute, durasi perjalanan, koli, penerima, dan tautan foto POD untuk diekspor ke Excel.

### `get_system_logs`
* **Metode**: `GET`
* **Fungsi**: Mengambil log aktivitas sistem/audit trail (riwayat aksi CRUD oleh admin/user) dengan pencarian dan filter tanggal.
