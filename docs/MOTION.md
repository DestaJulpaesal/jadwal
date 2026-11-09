# Motion dan transisi

Durasi dan easing animasi UI berasal dari token di `resources/css/tokens.css`. Jangan menambahkan durasi CSS baru di luar token; animasi dekoratif berulang adalah pengecualian dan harus dibatasi pada permukaan yang memang memakainya.

| Token | Nilai | Pemakaian |
|---|---:|---|
| `--dur-instant` | 80 ms | Respons tekan/hover yang sangat singkat |
| `--dur-fast` | 140 ms | Transisi halaman dan perubahan navigasi |
| `--dur-interaction` | 180 ms | Tab, pilihan, dan kontrol interaktif |
| `--dur-base` | 200 ms | Perubahan posisi/keadaan komponen |
| `--dur-medium` | 220 ms | Perpindahan UI yang memerlukan sedikit penekanan |
| `--dur-feedback` | 320 ms | Toast, overlay, dan lembar bawah |
| `--dur-slow` | 320 ms | Transisi overlay yang sama; hindari durasi baru |
| `--dur-spin` | 700 ms | Indikator berputar |
| `--dur-reduced` | 1 ms | Gerakan pada `prefers-reduced-motion: reduce` |
| `--ease-standard` | kurva standar | Perubahan posisi dan navigasi |
| `--ease-enter` | kurva masuk | Elemen yang muncul |
| `--ease-exit` | kurva keluar | Elemen yang menghilang |
| `--ease-spring` | kurva pegas | Aksen singkat, bukan gerakan konten utama |

View Transitions API digunakan lintas navigasi dengan crossfade singkat; browser tanpa dukungan tetap berpindah halaman normal. Animasi dekoratif dihentikan ketika tab tersembunyi, dan reduced motion membatasi animasi CSS hingga 1 ms. Prefetch navigasi dibatasi pada tautan GET bertanda, bukan aksi yang mengubah data.

`resources/js/motion.js` menghentikan animasi dekoratif saat halaman tersembunyi. Centang tugas dan check-in kebiasaan menggunakan peningkatan progresif: tanpa JavaScript tetap memakai formulir POST biasa; dengan JavaScript UI diperbarui optimistis dan dikembalikan bila server gagal.

## Bukti dan batas pengukuran

- Build terakhir menghasilkan JavaScript 54,15 KB gzip, CSS bersama 19,15 KB, CSS halaman masuk 3,06 KB, dan CSS beranda 3,79 KB; masing-masing di bawah batas Fase 5.
- Browser login diuji pada 360×640, 390×844, 768×1024, dan 1280×800. Tidak ditemukan overflow horizontal. Pada 360×640, form berakhir sebelum bagian bawah viewport.
- Emulasi reduced motion menghasilkan durasi animasi 0,001 s.
- Tidak ada pengukuran Lighthouse sebelum/sesudah atau profil CPU throttling yang dapat diverifikasi. Jangan menyimpulkan target LCP/CLS/INP tercapai hanya dari ukuran bundle atau uji viewport.
