# FASE 3 — PESAN, VALIDASI, DAN UMPAN BALIK YANG MUDAH DIPAHAMI

> **Cara pakai file ini:** tempel isi `00_BACA_DULU.md` DAN file ini ke agent, lalu jalankan. Jangan tempel file fase lain. Ini satu sesi kerja.

**Prasyarat:** Fase 1 dan 2 selesai (`x-toast`, `x-alert`, `x-error-summary`, `x-field` ada).
**Tujuan fase:** Setiap aksi punya hasil yang jelas: sukses/gagal, penyebab, dan langkah berikutnya — dalam bahasa awam.
**Dilarang disentuh di fase ini:** logika bisnis; struktur form (Fase 2); CSP (Fase 7).
**Keluaran wajib:** `lang/id/ui.php`, `lang/id/validation.php` lengkap, `messages()`/`attributes()` di semua FormRequest, helper flash terpadu, halaman error ramah, tes pesan.

## Protokol sesi (berlaku untuk setiap fase)
1. Baca `00_BACA_DULU.md`, lalu `docs/PROGRESS.md` (catatan serah-terima fase sebelumnya). Kalau `docs/PROGRESS.md` belum ada, buat.
2. Buat branch `polish/fase-3-<nama-singkat>`. Commit kecil dan sering, pesan jelas.
3. Kerjakan HANYA isi file ini. Temuan di luar lingkup fase → catat di `docs/PROGRESS.md` bagian "Ditunda", jangan dikerjakan.
4. Sebelum selesai: `php artisan test`, `npm run build`, cek manual di 360, 390, 768, 1280 px. Perbarui tes yang rusak karena perubahan teks (jangan dihapus).
5. Tulis di `docs/PROGRESS.md`: apa yang selesai, keputusan & asumsi, yang ditunda, risiko regresi, dan "Langkah pertama fase berikutnya".
6. **Jika konteks menipis:** hentikan pekerjaan baru, pastikan build/tes hijau, commit, tulis serah-terima di `docs/PROGRESS.md` (apa sudah, apa belum, file mana yang setengah jalan), lalu berhenti. Jangan meninggalkan perubahan setengah jalan tanpa catatan.

---
## Isi pekerjaan Fase 3

### 3.1 Prinsip pesan
Setiap pesan menjawab tiga hal singkat: **apa yang terjadi → kenapa → apa yang harus dilakukan user.** Nada: tenang, sopan, tidak menyalahkan ("Email belum diisi", bukan "Anda salah"). Tanpa jargon ("validasi", "request", "403", "exception"). Maksimal 2 kalimat. Gunakan "kamu" atau "Anda" secara konsisten di seluruh app (pilih satu; saran: "kamu" untuk nuansa ramah — jika memilih "Anda" untuk formalitas, terapkan di semua tempat termasuk email dan `lang/`). Gunakan istilah baku: **"kata sandi"** (bukan password) — termasuk label, tombol, dan pesan; **"Masuk"/"Keluar"** (bukan Login/Logout); **"Simpan"**, **"Tambah"**, **"Batal"**, **"Hapus"**, **"Ubah"**.

### 3.2 Sistem umpan balik terpadu
- Helper PHP tunggal (mis. `flash()` / trait pada controller) untuk `success|error|warning|info` + `message` + opsional `action` (label + URL/undo). Semua controller memakai helper ini; hapus `session('success')` yang disebar manual.
- `x-toast`: muncul dari bawah (HP, di atas bottom-nav) / kanan atas (desktop), ikon sesuai jenis, jeda otomatis saat disentuh/di-hover/difokus, tombol tutup ikon, aksi opsional ("Urungkan" 8 dtk), antrean maksimal 3. `role=status` (sukses/info) dan `role=alert` (error).
- `x-alert` untuk pesan persisten di dalam halaman (banner), `x-error-summary` untuk kegagalan form, pesan inline per-field untuk detail.
- Halaman error (`403/404/419/429/500/503`) ramah, dengan ikon, penjelasan, dan tombol aksi (Kembali, Ke beranda, Coba lagi). 419 → "Sesi habis. Masuk lagi untuk melanjutkan — isianmu tidak hilang jika memungkinkan." (redirect ke login dengan pesan, bukan halaman mati). 429 → hitung mundur.
- Status jaringan: deteksi `offline` → banner "Kamu sedang offline. Perubahan akan disimpan setelah tersambung." (minimal: nonaktifkan submit dan jelaskan). Request fetch gagal → toast error dengan "Coba lagi".

### 3.3 Katalog pesan (terapkan di `lang/id/*.php`, FormRequest `messages()`/`attributes()`, controller)
Lengkapi `attributes` untuk SEMUA field (amount → "nominal", wallet_id → "dompet", to_wallet_id → "dompet tujuan", money_category_id → "kategori", occurred_on → "tanggal", counterparty → "nama pihak", loan_date → "tanggal pinjam", due_on → "jatuh tempo", installments_total → "jumlah cicilan", remind_days_before → "pengingat", weekly_target → "target mingguan", goal_value → "target nilai", goal_unit → "satuan", preferred_time → "jam pilihan", starts_on → "tanggal mulai", ends_on → "tanggal berakhir", dsb.). Buat `messages()` Indonesia yang spesifik di SETIAP FormRequest, termasuk `LoginRequest`.

**Masuk (login)** — spesifik tapi tetap aman dari enumerasi akun:
| Kondisi | Tampilan |
|---|---|
| Email kosong | Di bawah field: "Email belum diisi." |
| Format email salah | "Format email belum benar. Contoh: nama@email.com" |
| Kata sandi kosong | "Kata sandi belum diisi." |
| Email/kata sandi salah | Banner di atas tombol: "Email atau kata sandi belum cocok. Periksa lagi huruf besar/kecilnya, atau atur ulang kata sandi." + tautan "Lupa kata sandi?" langsung di pesan. Fokus ke field kata sandi, kosongkan hanya kata sandi. JANGAN menyebut mana yang salah. |
| Caps Lock aktif | Petunjuk halus di bawah field kata sandi: "Caps Lock sedang aktif." (deteksi `getModifierState('CapsLock')`) |
| Akun nonaktif | Hanya ditampilkan SETELAH kata sandi benar: "Akun ini sedang dinonaktifkan. Hubungi admin untuk mengaktifkannya kembali." Jika kata sandi salah, tetap pesan generik di atas. |
| Terlalu banyak percobaan | "Terlalu banyak percobaan masuk. Coba lagi dalam 0:45." dengan hitung mundur hidup; tombol Masuk dinonaktifkan sampai habis lalu aktif otomatis dengan pesan "Silakan coba lagi." |
| Sesi habis | "Sesi kamu sudah berakhir. Masuk lagi untuk melanjutkan." |
| Berhasil | Tombol berubah "Berhasil…" (centang) → redirect halus; di halaman tujuan toast "Selamat datang kembali, {nama}." |
| Baru keluar | "Kamu sudah keluar. Sampai jumpa!" |
| Berhasil ubah kata sandi | "Kata sandi berhasil diubah. Silakan masuk dengan kata sandi baru." |

**Daftar (register)**: indikator syarat kata sandi hidup (✓ minimal 8 karakter, ✓ ada huruf besar & kecil, ✓ ada angka, ✓ tidak termasuk kata sandi yang bocor — di produksi), meter kekuatan, "Ulangi kata sandi" dengan pesan "Belum sama dengan kata sandi di atas.", email sudah terdaftar: "Email ini sudah dipakai. Coba masuk, atau atur ulang kata sandi." (tautan).

**Lupa kata sandi**: respons SELALU seragam: "Jika email terdaftar, tautan pengaturan ulang sudah dikirim. Cek kotak masuk dan folder spam." (anti-enumerasi); tombol kirim ulang dengan hitung mundur 60 dtk.

**CRUD umum**
- Sukses: "{Objek} \"{nama}\" tersimpan." / "…berhasil diubah." / "…dihapus." + Urungkan bila mungkin.
- Gagal validasi: ringkasan "Belum tersimpan. Periksa {n} isian berikut:".
- Wajib: "{Field} belum diisi." Panjang: "{Field} terlalu panjang (maks {n} karakter)." Angka: "{Field} harus berupa angka." Nominal: "Nominal harus lebih dari 0." Tanggal: "Tanggal belum benar. Pilih dari kalender." Rentang: "Waktu selesai harus setelah waktu mulai." Kepemilikan: jangan bocorkan — "Data tidak ditemukan." (404, bukan "bukan milik akun Anda").
- Saldo: "Saldo {dompet} tidak cukup (tersisa Rp X)."
- Hapus: dialog menyebut objek, dampak, dan tombol "Batal" + "Hapus {objek}".

Tulis semua pesan di satu tempat (`lang/id/ui.php` untuk teks UI + flash; `lang/id/validation.php` untuk validasi). Buat tes yang memastikan tiap FormRequest menghasilkan pesan Indonesia (tidak ada key mentah, tidak ada kata Inggris).

## 3.4 Tambahan: tes pesan (wajib)
- Tes untuk SETIAP FormRequest/validasi inline controller: kirim input salah → pesan Indonesia yang sesuai katalog (tanpa key mentah seperti `validation.required`, tanpa kata Inggris).
- Tes login: kosong, format email salah, kredensial salah (pesan seragam), akun nonaktif + kata sandi benar (pesan khusus), akun nonaktif + kata sandi salah (pesan seragam), throttle (pesan + detik), berhasil (flash selamat datang).
- Tes bahwa respons lupa-kata-sandi identik untuk email terdaftar dan tidak terdaftar.
- Tes bahwa akses data milik user lain mengembalikan 404 (tidak membocorkan kepemilikan).

## Checklist selesai Fase 3
- [ ] Tidak ada pesan Inggris/key mentah di mana pun (grep + tes).
- [ ] Semua field punya `attributes` Indonesia; semua FormRequest punya `messages()`.
- [ ] Login memenuhi seluruh tabel 3.3 (termasuk hitung mundur, deteksi Caps Lock, anti-enumerasi).
- [ ] Satu helper flash dipakai semua controller; `session('success')` manual tidak tersisa.
- [ ] Toast: jeda saat hover/fokus, tombol tutup ikon, aksi opsional, role/aria benar.
- [ ] Halaman 403/404/419/429/500/503 ramah dengan tombol aksi; 419 mengarah ke login dengan pesan.
- [ ] Konsisten satu sapaan ("kamu") dan istilah baku di seluruh app, email, dan `lang/`.
