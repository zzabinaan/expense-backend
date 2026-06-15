# Xpense — Backend

REST API untuk aplikasi pencatat pengeluaran **Xpense**, dibangun dengan **Laravel 13** dan autentikasi berbasis token menggunakan **Laravel Sanctum**.

---

## Daftar Isi

- [Tentang Project](#tentang-project)
- [Fitur](#fitur)
- [Teknologi](#teknologi)
- [Prasyarat](#prasyarat)
- [Setup di Local](#setup-di-local)
- [Konfigurasi CORS (Penting!)](#konfigurasi-cors-penting)
- [Endpoint API](#endpoint-api)

---

## Tentang Project

Xpense Backend adalah API yang melayani seluruh kebutuhan data dari Xpense Frontend. Menangani autentikasi pengguna, manajemen kategori, pencatatan pengeluaran, hingga analitik seperti grafik tren dan ringkasan bulanan.

> Repo ini **harus dijalankan bersama** [xpense-frontend](../expense-frontend). Keduanya saling terhubung — backend menyediakan data, frontend menampilkannya.

---

## Fitur

- **Autentikasi** — Register, Login, Logout, dan cek profil user yang sedang login
- **Kategori Pengeluaran** — Buat, ubah, lihat daftar, dan hapus kategori
- **Pencatatan Pengeluaran** — Buat, ubah, lihat daftar, dan hapus data pengeluaran
- **Analitik** — Ringkasan total, breakdown per kategori, tren pengeluaran per periode, dan top kategori terboros
- **Keamanan** — Semua endpoint (kecuali login/register) dilindungi token Sanctum

---

## Teknologi

| Komponen | Versi |
|---|---|
| PHP | ^8.3 |
| Laravel | ^13.8 |
| Laravel Sanctum | ^4.0 |
| Database | PostgreSQL |

---

## Prasyarat

Pastikan sudah terinstall di komputer kamu:

- **PHP** versi 8.3 atau lebih baru
- **Composer** (package manager PHP)
- **PostgreSQL** (database yang digunakan)
- **Node.js & npm** (diperlukan untuk build asset Laravel)

---

## Setup di Local

### 1. Clone repo

```bash
git clone <url-repo-ini>
cd xpense-backend
```

### 2. Install dependencies PHP

```bash
composer install
```

### 3. Buat file `.env`

```bash
cp .env.example .env
```

### 4. Generate app key

```bash
php artisan key:generate
```

### 5. Buat database PostgreSQL

Buat database baru di PostgreSQL kamu (misalnya lewat pgAdmin atau terminal):

```sql
CREATE DATABASE xpense;
```

### 6. Isi konfigurasi database di `.env`

Buka file `.env`, lalu sesuaikan bagian ini:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=xpense        # nama database yang kamu buat
DB_USERNAME=postgres      # username PostgreSQL kamu
DB_PASSWORD=              # password PostgreSQL kamu
```

### 7. Jalankan migrasi database

```bash
php artisan migrate
```

### 8. (Opsional) Isi data demo

Jika kamu ingin langsung melihat bagaimana aplikasi menampilkan data yang sudah ada, tanpa harus input manual dari nol, jalankan seeder berikut:

```bash
php artisan db:seed
```

Seeder akan membuat:
- **Akun demo** siap pakai
- **7 kategori** (Food, Transport, Shopping, Bills, Entertainment, Health, Other)
- **Ratusan data pengeluaran** realistis dari Januari hingga pertengahan Juni 2026

Login dengan kredensial berikut setelah frontend berjalan:

| Field | Value |
|---|---|
| Email | `zzabinaan@gmail.com` |
| Password | `12345678` |

> Seeder aman dijalankan berulang kali — data lama akan direset dan diganti yang baru.

### 9. Jalankan server

```bash
php artisan serve
```

Backend sekarang berjalan di **http://localhost:8000**

---

## Konfigurasi CORS (Penting!)

CORS adalah mekanisme keamanan browser yang mengatur dari domain mana saja API boleh diakses. Jika frontend dan backend kamu berjalan di port/alamat yang berbeda dari default, **kamu wajib mengatur ini**.

### Kasus default (tidak perlu diubah)

Jika frontend berjalan di `http://localhost:5173` (default Vite) dan backend di `http://localhost:8000`, semuanya sudah berjalan otomatis.

### Jika kamu mengganti port atau host frontend

Buka file `.env` dan ubah nilai berikut:

```env
FRONTEND_URL=http://localhost:5173         # ganti sesuai alamat frontend kamu
SANCTUM_STATEFUL_DOMAINS=localhost:5173   # ganti sesuai host:port frontend kamu
```

Jika kamu perlu menambahkan beberapa origin sekaligus, buka `config/cors.php`:

```php
'allowed_origins' => [
    env('FRONTEND_URL', 'http://localhost:5173'),
    'http://localhost:3000',   // tambahkan origin lain di sini jika perlu
],
```

> **Catatan:** Setelah mengubah `.env`, jalankan `php artisan config:clear` agar perubahan diterapkan.

---

## Endpoint API

Semua endpoint berada di bawah prefix `/api`.

### Autentikasi (publik)

| Method | Endpoint | Deskripsi |
|---|---|---|
| POST | `/api/auth/register` | Daftar akun baru |
| POST | `/api/auth/login` | Login, mendapat token |

### Autentikasi (butuh token)

| Method | Endpoint | Deskripsi |
|---|---|---|
| POST | `/api/auth/logout` | Logout, hapus token |
| GET | `/api/auth/me` | Data user yang sedang login |

### Kategori (butuh token)

| Method | Endpoint | Deskripsi |
|---|---|---|
| GET | `/api/categories` | Daftar semua kategori |
| POST | `/api/categories` | Buat kategori baru |
| PUT | `/api/categories/{id}` | Update kategori |
| DELETE | `/api/categories/{id}` | Hapus kategori |

### Pengeluaran (butuh token)

| Method | Endpoint | Deskripsi |
|---|---|---|
| GET | `/api/expenses` | Daftar semua pengeluaran |
| POST | `/api/expenses` | Catat pengeluaran baru |
| PUT | `/api/expenses/{id}` | Update pengeluaran |
| DELETE | `/api/expenses/{id}` | Hapus pengeluaran |

### Analitik (butuh token)

| Method | Endpoint | Deskripsi |
|---|---|---|
| GET | `/api/analytics/summary` | Ringkasan total pengeluaran |
| GET | `/api/analytics/by-category` | Pengeluaran dikelompokkan per kategori |
| GET | `/api/analytics/trend` | Tren pengeluaran dari waktu ke waktu |
| GET | `/api/analytics/top-categories` | Kategori dengan pengeluaran terbanyak |

Untuk endpoint yang membutuhkan token, sertakan header berikut di setiap request:

```
Authorization: Bearer <token_dari_login>
```
