# TMS Driver Head Office

Sistem Manajemen Transportasi & Driver Head Office (Web & Mobile).

---

## 🚀 Fitur Otomatisasi (CI/CD Deployment)

Repositori ini dilengkapi dengan **GitHub Actions** untuk deployment otomatis ke hosting **InfinityFree**:

1. **Auto Deploy Kode**:
   - Setiap `git push` ke branch `main` atau `master` akan otomatis mengunggah file yang diperbarui ke InfinityFree via FTP (`ftpupload.net`).
   - Folder `uploads/` (foto speedometer & bukti serah terima kunci) dikecualikan dari deploy agar data foto di live server tidak terhapus atau tertimpa.

2. **Auto Migrasi Database**:
   - Skrip `migrate.php` tersedia untuk mengeksekusi file migrasi SQL baru di database InfinityFree.
   - Letakkan file query SQL baru (misal `001_nama_fitur.sql`) di folder `migrations/`.
   - File migrasi yang sudah pernah dieksekusi akan dicatat di tabel `schema_migrations`.

---

## ⚙️ Konfigurasi GitHub Repository Secrets

Agar GitHub Action dapat melakukan deploy ke InfinityFree, tambahkan Secrets di repository GitHub Anda:
**GitHub Repository ➡️ Settings ➡️ Secrets and variables ➡️ Actions ➡️ New repository secret**

| Name | Value | Keterangan |
|------|-------|------------|
| `FTP_SERVER` | `ftpupload.net` | Hostname FTP InfinityFree |
| `FTP_USERNAME` | `if0_38464190` | Akun FTP Anda |
| `FTP_PASSWORD` | `Dhaniel0` | Password Akun FTP |
| `WEBSITE_URL` | `http://domain-anda.infinityfreeapp.com` | URL website live Anda di InfinityFree (Opsional, untuk auto trigger migrasi DB) |
| `MIGRATE_TOKEN` | `tms_secret_migration_key_8899` | Token keamanan untuk skrip migrasi (Opsional) |

---

## 🗄️ Cara Menambah Perubahan Database Baru

1. Buat file `.sql` baru di folder `migrations/` dengan format:
   `migrations/001_tambah_kolom_baru.sql`
2. Commit dan push ke GitHub:
   ```bash
   git add migrations/001_tambah_kolom_baru.sql
   git commit -m "Add new migration"
   git push origin main
   ```
3. Jika `WEBSITE_URL` diset, migrasi akan otomatis dieksekusi. Atau Anda bisa memanggil URL berikut di browser:
   `https://domain-anda/migrate.php?token=tms_secret_migration_key_8899`
