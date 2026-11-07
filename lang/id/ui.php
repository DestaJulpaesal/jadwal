<?php

return [
    'login' => [
        'failed' => 'Email atau kata sandi belum cocok. Periksa lagi huruf besar dan kecilnya, atau atur ulang kata sandi.',
        'disabled' => 'Akun ini sedang dinonaktifkan. Hubungi admin untuk mengaktifkannya kembali.',
        'session_expired' => 'Sesi kamu sudah berakhir. Masuk lagi untuk melanjutkan.',
        'logged_out' => 'Kamu sudah keluar. Sampai jumpa!',
        'welcome' => 'Selamat datang kembali, :name.',
    ],
    'validation' => [
        'required' => ':attribute belum diisi.',
        'email' => 'Format email belum benar. Contoh: nama@email.com',
        'numeric' => ':attribute harus berupa angka.',
        'positive_amount' => 'Nominal harus lebih dari 0.',
        'date' => 'Tanggal belum benar. Pilih dari kalender.',
        'range' => 'Waktu selesai harus setelah waktu mulai.',
    ],
    'feedback' => [
        'invalid_form' => 'Belum tersimpan. Periksa :count isian berikut.',
        'not_found' => 'Data tidak ditemukan.',
        'offline' => 'Kamu sedang offline. Perubahan akan dikirim setelah tersambung.',
        'retry' => 'Koneksi terputus. Periksa jaringan, lalu coba lagi.',
    ],
    'password_reset' => [
        'sent' => 'Jika email terdaftar, tautan pengaturan ulang sudah dikirim. Cek kotak masuk dan folder spam.',
        'changed' => 'Kata sandi berhasil diubah. Silakan masuk dengan kata sandi baru.',
        'invalid' => 'Tautan pengaturan ulang sudah tidak berlaku. Minta tautan baru untuk melanjutkan.',
    ],
    'account' => [
        'profile_updated' => 'Informasi profil berhasil diperbarui.',
        'verification_sent' => 'Tautan verifikasi baru sudah dikirim ke alamat email kamu.',
    ],
];
