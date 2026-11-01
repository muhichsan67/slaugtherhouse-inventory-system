# Slaughterhouse Inventory System

## Deskripsi

Aplikasi web untuk membantu operasional inventaris rumah potong hewan, mulai dari pembelian, penerimaan barang, penjualan, hingga pengiriman barang. Aplikasi mencatat pergerakan stok dan saldo stok per gudang.

Laporan yang tersedia:

- **Profit & Loss**: menghitung total purchase order berstatus `received` sebagai pengeluaran dan total sales order berstatus `delivered` sebagai pendapatan. Laporan ini sederhana, bukan perhitungan laba akuntansi lengkap.
- **Stock Card**: menampilkan riwayat pergerakan dan jumlah stok berjalan untuk barang dan gudang yang dipilih.

Fitur General Ledger, Trial Balance, jurnal, akun, dan stock opname tidak termasuk dalam aplikasi.

## Demo aplikasi

[Buka demo aplikasi](https://faturasya.my.id)

## Akun demo

Semua akun demo menggunakan kata sandi: **`password`**

| Role | Nama | Email |
|---|---|---|
| Admin | Admin RPH | `admin@rph.test` |
| Purchasing | Budi Purchasing | `purchasing@rph.test` |
| Warehouse | Wawan Warehouse | `warehouse@rph.test` |
| Sales | Sari Sales | `sales@rph.test` |
| Finance | Fina Finance | `finance@rph.test` |

Akun-akun tersebut dibuat oleh database seeder. Jalankan `php artisan migrate --seed` untuk membuat database lokal beserta akun demo.

## Instalasi di komputer lokal (Windows)

### Persyaratan

- PHP **8.3** atau lebih baru dengan ekstensi **PDO MySQL** aktif
- MySQL **8** atau lebih baru (server lokal seperti MySQL Community Server, XAMPP, atau Laragon)
- [Composer](https://getcomposer.org/)
- [Node.js dan npm](https://nodejs.org/)
- Git (opsional, jika meng-clone repositori)

### Langkah instalasi

1. Buka PowerShell dan masuk ke folder proyek. Jika belum memiliki salinan proyek, clone repositori:

   ```powershell
   git clone <URL_REPOSITORY>
   cd slaugtherhouse-inventory-system
   ```

   Jika proyek sudah ada di komputer, cukup jalankan `cd` ke folder proyek.

2. Pasang dependency PHP:

   ```powershell
   composer install
   ```

3. Buat database MySQL kosong, misalnya `rph_inventory`. Dengan MySQL CLI:

   ```powershell
   mysql -u root -p -e "CREATE DATABASE rph_inventory CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   ```

   Sesuaikan `root` dan opsi password dengan konfigurasi MySQL lokal Anda. Database juga dapat dibuat melalui phpMyAdmin atau tool sejenis.

4. Buat file konfigurasi lokal:

   ```powershell
   Copy-Item .env.example .env
   ```

   Ubah konfigurasi database di `.env` agar sesuai dengan MySQL lokal:

   ```dotenv
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=rph_inventory
   DB_USERNAME=root
   DB_PASSWORD=
   ```

   Isi `DB_USERNAME` dan `DB_PASSWORD` sesuai akun MySQL Anda.

5. Buat application key, jalankan migrasi, dan isi database dengan data demo:

   ```powershell
   php artisan key:generate
   php artisan migrate --seed
   ```

6. Pasang dependency frontend dan buat aset:

   ```powershell
   npm install
   npm run build
   ```

7. Jalankan server lokal:

   ```powershell
   php artisan serve
   ```

8. Buka [http://127.0.0.1:8000](http://127.0.0.1:8000), lalu masuk menggunakan salah satu akun demo di atas.

> **Catatan:** `information\rph_inventory.sql` adalah referensi skema dan data MySQL. Skrip tersebut menghapus lalu membuat ulang tabelnya; untuk instalasi lokal biasa, gunakan migrasi dan seeder Laravel seperti langkah di atas.
