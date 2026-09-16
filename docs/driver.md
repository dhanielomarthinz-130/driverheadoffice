# Dokumentasi: `driver.php` (Aplikasi Mobile Driver)

## Deskripsi Singkat
File `driver.php` merupakan antarmuka utama (mobile web app) yang digunakan oleh Sopir (Driver) di lapangan. Aplikasi ini dirancang agar *mobile-friendly* (UI/UX dioptimalkan untuk layar sentuh ponsel) dan memiliki fitur-fitur penting seperti pelacakan lokasi (GPS), manajemen pemilihan kendaraan, pengaturan status tugas, serta pengambilan bukti foto (*Proof of Delivery/Pickup*) secara *real-time*.

## Fitur Utama
1. **Manajemen Kendaraan (Vehicle Assignment):** Sopir harus memilih kendaraan yang digunakan sebelum memulai tugas. Terdapat modal khusus untuk memilih kendaraan yang tersedia. Sopir juga dapat melepas (*release*) kendaraan saat selesai bertugas.
2. **Pelacakan Lokasi (GPS Tracking):** Aplikasi secara otomatis meminta akses GPS dari peramban (browser) dan mengirimkan koordinat *latitude* & *longitude* sopir ke server saat berpindah tab/secara berkala. Terdapat peringatan visual jika GPS terputus.
3. **Manajemen Tugas (Task Management):** Menampilkan daftar tugas (Antar maupun Pickup) yang dibagi ke dalam 3 tab navigasi bawah: **Tugas Baru**, **Perjalanan**, dan **Riwayat**. Tugas ditampilkan dalam bentuk kartu *accordion* (bisa dilebarkan untuk melihat detail koli & catatan).
4. **Bukti Penyelesaian (Proof of Delivery/Pickup):** Untuk menyelesaikan tugas, sopir wajib mengunggah foto barang / bukti serah terima (via kamera langsung atau galeri). Aplikasi secara otomatis memproses gambar menggunakan Canvas JS untuk menambahkan **Watermark** (SJ, Lokasi Asal-Tujuan, Nama Sopir, Waktu) sebelum file dikirim ke server.
5. **Navigasi Rute:** Terdapat tombol *Action* untuk "Mulai Perjalanan" dan tombol untuk membuka rute langsung di aplikasi Google Maps (*Deep Link*).
6. **Kalender Horizontal:** Sopir dapat melihat ringkasan tugas berdasarkan tanggal. Terdapat indikator titik (merah/hijau) di bawah tanggal yang menandakan adanya tugas pending atau selesai di hari tersebut.
7. **Peringatan & Alarm:** Memainkan suara alarm notifikasi (bunyi *doorbell*) saat sistem mendeteksi ada tugas baru yang masuk.
8. **Alasan Keterlambatan:** Jika sopir menyelesaikan tugas yang sudah lewat dari tanggal penugasan (lewat hari), sistem mewajibkan pengisian alasan keterlambatan melalui modal khusus sebelum bisa *Submit* bukti penyelesaian.

## Alur Kerja (Workflow) Driver

```mermaid
flowchart TD
    A[Sopir Buka Aplikasi] --> B{Punya Kendaraan Aktif?}
    B -- Belum --> C[Tampil Modal Pilih Kendaraan]
    C --> D[Pilih Kendaraan API: assign_vehicle]
    B -- Sudah --> E[Dashboard Utama]
    D --> E
    
    E --> F[Cek/Kirim Koordinat GPS API: update_location]
    E --> G[Load Daftar Tugas API: get_deliveries]
    
    G --> H[Tab: Tugas Baru]
    G --> I[Tab: Perjalanan]
    G --> J[Tab: Riwayat Selesai]
    
    H --> K[Klik 'Mulai Perjalanan']
    K --> L[API: update_status to in_transit]
    L --> I
    
    I --> M[Klik 'Navigasi']
    M --> N[Buka Google Maps ke Tujuan]
    
    I --> O[Klik 'Selesai']
    O --> P{Tugas Lewat Hari?}
    P -- Ya --> Q[Input Alasan Keterlambatan]
    P -- Tidak --> R[Modal Proof of Delivery]
    Q --> R
    
    R --> S[Ambil Foto Bukti]
    S --> T[Sistem Sisipkan Watermark (Canvas JS)]
    T --> U[Isi Nama Penerima & Catatan]
    U --> V[API: update_status to completed]
    V --> J
```

## API Endpoint yang Terhubung
Aplikasi ini beroperasi hampir layaknya *Single Page Application* (SPA) dengan bantuan metode AJAX (Fetch API). Endpoint utama di `api.php` meliputi:
- `?action=get_active_vehicle` & `?action=get_vehicles` & `?action=assign_vehicle` & `?action=release_vehicle`: Endpoint terkait manajemen unit kendaraan.
- `?action=update_location`: Digunakan untuk melempar titik koordinat sopir.
- `?action=get_deliveries`: Mengambil array JSON tugas yang di-assign untuk sopir pada tanggal terpilih.
- `?action=update_status`: Menangani perubahan status (Pending -> Transit -> Selesai). Termasuk menangani form *multipart* saat sopir mengunggah foto penyelesaian tugas.
- `?action=get_calendar_summary`: Mengambil ringkasan keberadaan tugas harian untuk dirender ke komponen Kalender Horizontal.
