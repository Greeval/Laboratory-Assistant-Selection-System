<?php
// HELPER: KIRIM NOTIFIKASI GANDA (INTERNAL WEB & EMAIL SMTP)
require_once __DIR__ . '/koneksiweb.php';

// Load PHPMailer files
require_once __DIR__ . '/vendor/PHPMailer/src/Exception.php';
require_once __DIR__ . '/vendor/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/vendor/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Fungsi untuk mengirim notifikasi ke tabel dan via email
 *
 * @param string $nim NIM mahasiswa tujuan
 * @param string $judul Judul Notifikasi
 * @param string $pesan Isi Pesan
 * @param string $email_admin Email admin lab (untuk reply-to)
 * @param string $nama_admin Nama admin lab
 * @return array ['success' => bool, 'msg' => string]
 */
function kirim_notifikasi_ganda($nim, $judul, $pesan, $email_admin, $nama_admin) {
    global $koneksi;

    $hasil = ['success' => true, 'msg' => ''];

    // 1. Simpan ke Database (Notifikasi Web)
    $judul_esc = mysqli_real_escape_string($koneksi, $judul);
    $pesan_esc = mysqli_real_escape_string($koneksi, $pesan);
    $nim_esc = mysqli_real_escape_string($koneksi, $nim);

    $q_insert = "INSERT INTO notifikasi (nim, judul, pesan, waktu, is_read) 
                 VALUES ('$nim_esc', '$judul_esc', '$pesan_esc', NOW(), 0)";
    if (!mysqli_query($koneksi, $q_insert)) {
        $hasil['success'] = false;
        $hasil['msg'] .= "Gagal simpan ke DB: " . mysqli_error($koneksi) . ". ";
    }

    // 2. Ambil Email Mahasiswa
    $q_mhs = mysqli_query($koneksi, "SELECT email, nama_mahasiswa FROM mahasiswa WHERE nim = '$nim_esc' LIMIT 1");
    $d_mhs = mysqli_fetch_assoc($q_mhs);
    
    if ($d_mhs && !empty($d_mhs['email'])) {
        $email_mhs = $d_mhs['email'];
        $nama_mhs = $d_mhs['nama_mahasiswa'];

        // 3. Kirim Email via PHPMailer
        $mail = new PHPMailer(true);

        try {
            // Konfigurasi smtp server (contoh gmail)
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';         
            $mail->SMTPAuth   = true;
            // Gunakan Environment Variables untuk keamanan
            $mail->Username   = getenv('SMTP_USER') ?: 'contoh.email.sistem@gmail.com'; 
            $mail->Password   = getenv('SMTP_PASS') ?: 'abcdefghijklmnop'; 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // Pengirim (Sistem, tapi pakai nama Admin)
            $mail->setFrom('contoh.email.sistem@gmail.com', "Admin Lab - $nama_admin (Web Seleksi Aslab)");
            
            // Reply-To (Jika mahasiswa membalas email, akan masuk ke email pribadi Admin Lab)
            if (!empty($email_admin)) {
                $mail->addReplyTo($email_admin, "Admin Lab - $nama_admin");
            }

            // Penerima
            $mail->addAddress($email_mhs, $nama_mhs);

            // Konten Email
            $mail->isHTML(true);
            $mail->Subject = $judul;
            
            // Template Email Sederhana (Dengan proteksi XSS)
            $judul_html = htmlspecialchars($judul);
            $nama_mhs_html = htmlspecialchars($nama_mhs);
            $pesan_html = htmlspecialchars($pesan);
            $nama_admin_html = htmlspecialchars($nama_admin);
            
            $body_html = "
                <div style='font-family: Arial, sans-serif; padding: 20px; color: #333;'>
                    <h2>$judul_html</h2>
                    <p>Halo <strong>$nama_mhs_html</strong>,</p>
                    <p style='white-space: pre-line; line-height: 1.6;'>$pesan_html</p>
                    <hr>
                    <p style='font-size: 12px; color: #777;'>Email ini dikirim secara otomatis oleh Sistem Seleksi Asisten Laboratorium.<br>
                    Jika Anda memiliki pertanyaan, Anda dapat membalas email ini (akan diteruskan ke $nama_admin_html).</p>
                </div>
            ";
            
            $mail->Body    = $body_html;
            $mail->AltBody = strip_tags(str_replace("<br>", "\n", $body_html));

            $mail->send();
            $hasil['msg'] .= "Notif dan Email berhasil dikirim.";

        } catch (Exception $e) {
            // Kita tangkap errornya agar aplikasi tidak crash jika konfigurasi email belum diisi
            $hasil['success'] = false;
            $hasil['msg'] .= "Email gagal dikirim. Pastikan SMTP dikonfigurasi. Mailer Error: {$mail->ErrorInfo}";
        }
    } else {
        $hasil['msg'] .= "Mahasiswa tidak memiliki alamat email. (Notifikasi Web tetap tersimpan).";
    }

    return $hasil;
}
?>
