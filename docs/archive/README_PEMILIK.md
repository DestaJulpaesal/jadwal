# Cara memakai paket prompt ini (untuk kamu, bukan untuk agent)

1. Buka folder project di Claude Code / Cursor (atau agent coding lain).
2. Untuk SETIAP fase, mulai sesi/obrolan BARU, lalu tempel: isi `00_BACA_DULU.md` + isi file fase itu. Jangan tempel semua file.
3. Urutan: 01 → 02 → 03 → 04 → 05 → 06 → 07 → 08 → 09 → 10 (opsional) → 99.
   - Fase 6 (keamanan inti) boleh dikerjakan lebih awal/paralel setelah Fase 0.
   - Fase 7 (CSP) wajib setelah Fase 5 dan 6, dan boleh memakan lebih dari satu sesi.
4. Setelah tiap fase: cek hasilnya di HP (atau emulasi 360×640), pastikan tes hijau, lalu merge branch-nya.
5. Fase 10 (fitur baru) opsional. Pilih modul yang kamu mau dan sebut jelas di prompt, misalnya: "Kerjakan Modul B (tugas berulang) saja".
6. Keputusan default yang dipakai: sapaan "kamu", SQLite + backup, CSP bertahap (off/report/enforce). Mau beda? Ubah di Bagian 6 `00_BACA_DULU.md` sebelum mulai.
7. Folder `list/` di project kamu adalah salinan lama; putuskan sendiri mau dihapus atau tidak (agent hanya menandainya).
