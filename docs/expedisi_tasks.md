# Dokumentasi Modul: Tugas Ekspedisi Pihak Ketiga (`expedisi_tasks.php`)

Modul ini digunakan oleh Admin untuk menjadwalkan, mencatat, dan memantau tugas pengiriman yang diserahkan kepada pihak ekspedisi eksternal (vendor luar/pihak ketiga).

---

## 1. Diagram Alur Kerja

```mermaid
flowchart TD
    A([Mulai]) --> B[Admin membuka menu 'Expedisi' - expedisi_tasks.php]
    B --> C[Klik tombol 'Buat Tugas Expedisi']
    
    C --> D[Pilih Tanggal, Tipe Tugas, & Cari Nama Vendor]
    D --> E[Input data: Plat Nomor, No Surat Jalan, Koli, Asal, Tujuan, Nama Sopir, Catatan & Foto Bukti]
    
    E --> F[Kirim Data via API action=add_expedisi_task]
    
    F --> G[API menormalkan data No SJ, Plat Kendaraan, Nama Sopir, & Catatan ke UPPERCASE]
    G --> H[Sistem menyimpan tugas di tabel 'expedisi_tasks' status: 'pending']
    
    H --> I[Petugas klik 'Mulai Perjalanan' - status: 'in_transit']
    
    I --> J[Barang Tiba di Tujuan - Klik 'Selesai']
    J --> K[Input Penerima, Catatan Kendala, & Foto POD]
    K --> L[API action=complete_expedisi_task menyimpan status 'completed' / 'canceled']
    L --> M([Selesai])
```

---

## 2. Fitur Utama Halaman
* **Buat Tugas Ekspedisi (Add Modal)**: Menginput data pengiriman pihak ketiga dengan pencarian otomatis master vendor luar.
* **Mulai Tugas**: Mengubah status pengiriman dari `pending` ke `in_transit` secara manual oleh petugas admin/operasional.
* **Selesaikan Pengiriman (Finish Modal)**: Digunakan untuk memproses tanda selesai saat ekspedisi tiba di tujuan. Admin akan memasukkan nama penerima, catatan pengiriman dari sopir, dan mengunggah berkas foto POD (bukti penerimaan).
* **Batal / Laporan Kendala**: Jika dalam perjalanan ekspedisi eksternal mengalami kendala fatal, status dapat diselesaikan sebagai `canceled` (batal) dengan menginput detail kendala sebagai laporan audit.
* **Standardisasi UPPERCASE**: Untuk menjaga keseragaman data pihak luar, input berupa **Nomor Plat Kendaraan**, **No Surat Jalan**, **Nama Sopir / Driver**, dan **Catatan** secara otomatis dipaksa menjadi **HURUF BESAR (UPPERCASE)** saat disimpan di database.

---

## 3. Integrasi API
Halaman ini berkomunikasi dengan `api.php` melalui aksi-aksi berikut:
* `api.php?action=get_expedisi_vendors`: Memuat list vendor ekspedisi untuk pencarian otomatis dan dropdown filter.
* `api.php?action=get_locations`: Memuat list nama lokasi master (Asal & Tujuan).
* `api.php?action=get_expedisi_tasks`: Mengambil semua daftar penugasan ekspedisi aktif dan riwayat dengan berbagai filter.
* `api.php?action=add_expedisi_task` (`POST`): Menyimpan data tugas ekspedisi pihak ketiga baru.
* `api.php?action=start_expedisi_task` (`POST`): Mengubah status menjadi jalan (`in_transit`).
* `api.php?action=complete_expedisi_task` (`POST`): Menyimpan POD dan mengakhiri pengiriman dengan status `completed` atau `canceled`.
* `api.php?action=delete_expedisi_task` (`POST`): Menghapus tugas ekspedisi dari database.
