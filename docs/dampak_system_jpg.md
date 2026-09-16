# Analisis Dampak Format JPG pada Sistem TMS

Format JPG secara umum adalah format kompresi gambar yang sudah sangat baik untuk foto. Namun, apakah file JPG tersebut akan memakan memori (penyimpanan/disk space) server yang besar atau tidak, **sangat bergantung pada cara pengambilan dan pengiriman gambar tersebut.**

Berikut adalah analisis detail beserta dampaknya terhadap server dan handphone driver:

---

## 1. Dampak pada Penyimpanan Server (Disk Space) 💾

Kamera HP modern rata-rata menghasilkan resolusi tinggi (12 MP hingga 108 MP). Satu foto JPG mentah dari kamera HP ukurannya berkisar antara **3 MB hingga 10 MB**.

### Ilustrasi Jika File Tidak Dikompresi Sebelum Diunggah

| Parameter | Nilai |
|---|---|
| Jumlah Driver | 10 |
| Foto per Driver / Hari | 5 (bukti kirim, surat jalan, dsb) |
| Rata-rata Ukuran 1 Foto | 4 MB |
| **Konsumsi Harian** | 10 × 5 × 4 MB = **200 MB / hari** |
| **Konsumsi Bulanan** | 200 MB × 30 = **6 GB / bulan** |
| **Konsumsi Tahunan** | 6 GB × 12 = **72 GB / tahun** |

> ⚠️ **WARNING:** Jika kapasitas hosting Anda terbatas (misalnya hanya 10 GB atau 20 GB), penyimpanan server Anda akan **penuh dalam hitungan bulan**.

---

## 2. Dampak pada RAM dan CPU Server 🧠

- **Saat Upload:** Proses pemindahan file dari HP ke server menggunakan `move_uploaded_file()` di PHP tidak memakan banyak RAM server karena PHP hanya menyalin file sementara.
- **Saat Menampilkan/Mengolah:** Jika sistem mencoba menampilkan galeri foto berukuran besar sekaligus, loading halaman akan menjadi sangat lambat, memakan bandwidth server, dan bisa menyebabkan PHP kehabisan RAM (`memory_limit`) jika gambar di-render/di-resize menggunakan GD Library secara bersamaan.

---

## 3. Dampak pada Driver (User Experience & Kuota) 📱

- **Koneksi Lambat:** Mengunggah file 4 MB di daerah dengan sinyal kurang bagus sering kali gagal atau macet di tengah jalan.
- **Boros Kuota:** Driver akan mengeluh karena kuota internet mereka cepat habis hanya untuk mengunggah foto laporan kerja.

---

## Solusi Terbaik (Sudah Diimplementasikan) 💡

Agar foto tidak membebani server dan sangat menghemat kuota driver, sistem menggunakan **Kompresi di Sisi Browser (Client-side Compression) & Konversi ke Format WebP** sebelum file diunggah ke server:

| Langkah | Detail |
|---|---|
| **Ubah Resolusi Gambar** | Perkecil resolusi dari 4000×3000px → **1200px** pada sisi terpanjang. Resolusi ini sangat tajam untuk membaca tulisan surat jalan atau bukti tanda tangan. |
| **Konversi Format ke WebP** | Mengubah format gambar dari JPEG/PNG menjadi **WebP** yang memiliki algoritma kompresi jauh lebih baik (25%-35% lebih kecil dibanding JPEG pada kualitas setara). |
| **Kualitas Kompresi** | Set kualitas gambar ke **82% WebP**. |
| **Hasil Akhir** | Foto yang semula berukuran **4 MB (4000 KB)** akan mengecil secara dramatis menjadi sekitar **40 KB – 100 KB** (menghemat penyimpanan hingga **98%**), dengan teks dan detail gambar yang tetap tajam dan terbaca jelas. |

> ✅ **Status:** Fitur ini sudah sepenuhnya diimplementasikan di semua modul sistem TMS (Pendaftaran Tugas oleh Admin, Pengunggahan Bukti oleh Driver, dan Request Pickup oleh Store). Semua gambar dikonversi ke format `.webp` secara langsung di browser pengguna sebelum diunggah ke server. Format ini didukung penuh oleh browser modern untuk tampilan langsung dan dapat dibagikan (share) dengan lancar.