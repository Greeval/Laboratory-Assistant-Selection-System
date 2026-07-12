# 🛡️ Rencana Implementasi Perbaikan Keamanan Sistem

Dokumen ini merangkum langkah-langkah sistematis yang akan saya lakukan untuk menambal semua celah keamanan yang ditemukan pada proses audit, terutama kerentanan Kritis dan Tinggi.

## User Review Required

> [!WARNING]
> Kerentanan **CRITICAL (Password Plaintext)** sangat berbahaya karena menyimpan dan menampilkan password di tabel aplikasi.
> Saya akan **menghapus permanen** kolom `password_plain` dari database dan mengubah semua kode PHP yang memanggil variabel ini agar sistem hanya bergantung pada *password hash* terenkripsi yang aman.
> **Mohon setujui rencana ini agar saya bisa langsung mengeksekusinya.**

## Open Questions
- Apakah SMTP (pengiriman email notifikasi) sudah aktif dan menggunakan akun Gmail tersebut? Saya akan memindahkannya dari *hardcode* ke environment agar password Gmail Anda aman.

---

## Proposed Changes

---
### 1. Perbaikan Database & Password (CRITICAL)

Mengamankan struktur data dan akun database. Menghilangkan kerentanan penyebaran informasi dan error sistem.

#### [MODIFY] [dashboard_admin_prodi_km.php](file:///c:/Users/hakim/Greeval/PROJECT/Web%20Seleksi%20Asleb/dashboard_admin_prodi_km.php)
- Hapus semua *query* yang menggunakan `password_plain`.
- Hapus tampilan `password_plain` di tabel daftar Mahasiswa dan Admin.
- Ubah aksi `hapus` dari parameter GET (`?aksi=hapus&id=X`) menjadi POST form dengan CSRF.

#### [MODIFY] [dashboard_admin_lab.php](file:///c:/Users/hakim/Greeval/PROJECT/Web%20Seleksi%20Asleb/dashboard_admin_lab.php)
- Hapus *query update* `password_plain`.
- Hapus variabel `$pw_baru` pada *query* password.

#### [MODIFY] [login.php](file:///c:/Users/hakim/Greeval/PROJECT/Web%20Seleksi%20Asleb/login.php)
- Pastikan hanya menggunakan `password_hash()` dan `password_verify()`.
- Upgrade format query `UPDATE` ke Prepared Statement (Bukan string interpolasi).

#### [MODIFY] [koneksiweb.php](file:///c:/Users/hakim/Greeval/PROJECT/Web%20Seleksi%20Asleb/koneksiweb.php)
- Sembunyikan pesan error internal DB dari pengguna (hindari XSS dan *Information Disclosure*).
- Ubah koneksi root kosong jika dimungkinkan atau tambahkan instruksi pembuatan akun DB.
- Perbaiki logika *Auto-Logout Session* agar mengecek key role yang benar (`$_SESSION[$role]['id_user']`).

---
### 2. Implementasi Keamanan Form: CSRF Token & File Upload (HIGH)

Menutup celah pembajakan form (CSRF) dan memvalidasi file unggahan.

#### [NEW] [csrf_helper.php](file:///c:/Users/hakim/Greeval/PROJECT/Web%20Seleksi%20Asleb/csrf_helper.php)
- Membuat sistem generator token CSRF dan fungsi verifikasinya.

#### [MODIFY] Semua File Dashboard & Kelola 
- Menyisipkan `<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">` di setiap `<form method="POST">`.
- Menyisipkan validasi CSRF di semua proses POST PHP terkait.

#### [MODIFY] [dashboard_mahasiswa.php](file:///c:/Users/hakim/Greeval/PROJECT/Web%20Seleksi%20Asleb/dashboard_mahasiswa.php)
- Mengganti validasi ektensi file unggahan dengan fungsi validasi konten menggunakan `mime_content_type()` atau `finfo_file()`.
- Mencegah *Remote Code Execution* dari unggahan PHP berkedok jpg.

---
### 3. Perbaikan Keamanan Role Guard & XSS (HIGH - MEDIUM)

Memastikan validasi Role yang tepat dan proteksi kode berbahaya pada render HTML dan Notifikasi Email.

#### [MODIFY] [ajax_update_inline.php](file:///c:/Users/hakim/Greeval/PROJECT/Web%20Seleksi%20Asleb/ajax_update_inline.php)
- Mengganti `$_SESSION['Admin Prodi']` menjadi pengecekan yang fleksibel atau khusus `$_SESSION['Admin Lab']` agar fitur berfungsi semestinya.
- Menambahkan validasi Token CSRF.
- Refactor dengan *Prepared Statement*.

#### [MODIFY] [kirim_notifikasi.php](file:///c:/Users/hakim/Greeval/PROJECT/Web%20Seleksi%20Asleb/kirim_notifikasi.php)
- Bungkus semua variabel `$judul`, `$pesan`, `$nama_mhs` dengan fungsi `htmlspecialchars()` pada render format HTML Email.
- Sembunyikan *hardcoded* SMTP App Password ke variabel *environment* yang aman.

#### [MODIFY] [ujian_mahasiswa.php](file:///c:/Users/hakim/Greeval/PROJECT/Web%20Seleksi%20Asleb/ujian_mahasiswa.php) & Dashboard
- Lindungi *Alert Message JS* dengan *escaping* variabel.

---

## Verification Plan

### Manual Verification
- Melakukan login dengan masing-masing Role (Mahasiswa, Admin Lab, Admin Prodi).
- Menyimulasikan perubahan password dan profil, serta mengecek bahwa database tetap tersimpan dengan format hash yang aman.
- Mencoba mengakses endpoint mutasi tanpa menyertakan CSRF token dan memastikan tertolak.
- Mengunggah file .php berkedok .jpg dan memastikan gagal.
- Memastikan tidak ada lagi pesan Error MySQL mentah yang muncul pada layar bila ada data ganda atau gangguan.
