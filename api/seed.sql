-- Seed data untuk Web Seleksi Asleb
-- Hapus data lama (jika ada) untuk menghindari konflik
DELETE FROM detail_jawaban;
DELETE FROM sesi_ujian;
DELETE FROM pemberkasan;
DELETE FROM hasil_akhir;
DELETE FROM mahasiswa;
DELETE FROM admin_prodi;
DELETE FROM admin_lab;
DELETE FROM akun;

-- Insert Akun
INSERT INTO akun (id_user, username, password, role) VALUES 
(1, 'adminprodi', '$2y$10$XNjua.9oHys7oRHtfx2k2eKmsrz9F.MxutwYmSR8SYeSI/noo5Nf2', 'Admin Prodi'),
(2, 'adminlab', '$2y$10$XNjua.9oHys7oRHtfx2k2eKmsrz9F.MxutwYmSR8SYeSI/noo5Nf2', 'Admin Lab'),
(3, '123456789', '$2y$10$XNjua.9oHys7oRHtfx2k2eKmsrz9F.MxutwYmSR8SYeSI/noo5Nf2', 'Mahasiswa');

-- Insert Profil Admin Prodi
INSERT INTO admin_prodi (id_admin_prodi, id_user, nama_admin, nip) VALUES 
(1, 1, 'Prodi Administrator', '19880101');

-- Insert Profil Admin Lab
INSERT INTO admin_lab (id_admin_lab, id_user, nama_admin, nip, jabatan) VALUES 
(1, 2, 'Lab Administrator', '19880102', 'Kepala Lab');

-- Insert Profil Mahasiswa (NIM: 123456789)
INSERT INTO mahasiswa (nim, id_user, nama_mahasiswa, semester, ipk) VALUES 
('123456789', 3, 'Mahasiswa Test', 6, 3.85);
