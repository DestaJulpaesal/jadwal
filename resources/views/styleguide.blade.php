@extends('layouts.base')

@section('title', 'Panduan tampilan')
@section('body-class', 'min-h-screen bg-canvas text-ink')
@section('body')
    <main id="main-content" tabindex="-1" class="mx-auto max-w-6xl px-4 py-8 sm:px-6">
        <x-page-header eyebrow="Hanya tersedia di lingkungan lokal" title="Panduan tampilan" description="Contoh komponen dan pola visual untuk halaman baru.">
            <x-button href="{{ route('home') }}" variant="secondary"><x-icon name="kembali" class="h-5 w-5" />Kembali ke beranda</x-button>
        </x-page-header>
        <div class="grid gap-5 lg:grid-cols-2">
            <section class="surface-card space-y-4 p-5 lg:col-span-2">
                <h2 class="text-lg font-semibold">Ikon Jadwal Harian</h2>
                <p class="text-sm text-muted">Ikon outline yang sederhana dan konsisten; warnanya mengikuti konteks teks.</p>
                <div class="grid grid-cols-3 gap-3 sm:grid-cols-5 lg:grid-cols-7">
                    @foreach (['beranda', 'tugas', 'kalender', 'profil', 'menu', 'kembali', 'sebelumnya', 'berikutnya', 'tambah', 'ubah', 'hapus', 'belum-selesai', 'selesai', 'cari', 'filter', 'menu-aksi', 'tutup', 'lihat-password', 'sembunyikan-password', 'keluar', 'terlambat', 'bentrok', 'galat', 'info', 'kelola-akun', 'nonaktifkan', 'zona-waktu', 'kebiasaan', 'keuangan', 'target', 'tagihan', 'utang'] as $icon)
                        <div class="flex min-h-16 items-center gap-2 rounded-lg border border-line px-3 py-2 text-sm">
                            <x-icon :name="$icon" class="h-6 w-6" />
                            <span>{{ str_replace('-', ' ', $icon) }}</span>
                        </div>
                    @endforeach
                </div>
            </section>
            <section class="surface-card space-y-4 p-5">
                <h2 class="text-lg font-semibold">Warna dan tombol</h2>
                <div class="flex flex-wrap gap-2">
                    <x-button type="button" variant="primary" icon="tambah">Tambah tugas</x-button>
                    <x-button type="button" variant="secondary" icon="kembali">Kembali</x-button>
                    <x-button type="button" variant="danger" icon="hapus">Hapus tugas</x-button>
                    <x-button type="button" variant="ghost" icon="info">Lihat detail</x-button>
                    <x-button type="button" variant="primary" disabled title="Lengkapi isian wajib" icon="selesai">Belum bisa disimpan</x-button>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @foreach (['sm' => 'Kecil', 'md' => 'Sedang', 'lg' => 'Besar'] as $size => $sizeLabel)
                        @foreach (['primary' => 'tambah', 'secondary' => 'ubah', 'ghost' => 'info', 'danger' => 'hapus'] as $variant => $icon)
                            <x-button type="button" :size="$size" :variant="$variant" :icon="$icon">{{ ucfirst($variant) }} {{ $sizeLabel }}</x-button>
                        @endforeach
                    @endforeach
                    <x-button type="button" variant="primary" icon="selesai" aria-busy="true" loading-text="Menyimpan tugas…">Simpan tugas</x-button>
                    <x-button type="button" variant="primary" class="is-success" icon="selesai">Tersimpan</x-button>
                    <x-button type="button" variant="ghost" icon="tutup" icon-only aria-label="Tutup contoh" title="Tutup contoh" />
                </div>
                <div class="flex flex-wrap gap-2">
                    <x-status-badge status="Selesai" />
                    <x-status-badge status="Terlambat" />
                    <x-status-badge status="Aktif" />
                    <x-status-badge status="Menunggu" />
                </div>
            </section>

            <section class="surface-card space-y-4 p-5">
                <h2 class="text-lg font-semibold">Kolom isian</h2>
                <x-money-input id="guide-amount" name="guide_amount" label="Nominal" value="1500000" />
                <x-progress-bar value="65" label="Contoh progres" description="Sudah berjalan dengan baik." />
                <x-field id="guide-title" label="Judul tugas" help="Petunjuk singkat ditampilkan di bawah label.">
                    <input id="guide-title" class="form-control" value="Menyiapkan rapat">
                </x-field>
                <x-select id="guide-category" label="Kategori" help="Pilih kelompok yang membantu menyaring tugas.">
                    <option>Pribadi</option><option>Pekerjaan</option>
                </x-select>
                <x-textarea id="guide-note" name="guide_note" label="Catatan" help="Tambahkan detail yang perlu diingat." rows="2" maxlength="160" value="Bawa catatan rapat." />
                <x-segmented name="guide-kind" label="Jenis" :options="['task' => 'Tugas', 'habit' => 'Kebiasaan']" value="task" />
                <x-choice-card name="guide-wallet" :options="['cash' => ['label' => 'Tunai', 'description' => 'Untuk uang yang dibawa.', 'icon' => 'keuangan'], 'bank' => ['label' => 'Bank', 'description' => 'Untuk saldo rekening.', 'icon' => 'keuangan']]" value="cash" />
                <x-datetime-quick id="guide-time" name="guide_time" label="Waktu" type="datetime-local" help="Pilih waktu untuk contoh jadwal." />
                <label class="flex min-h-11 items-center gap-3"><input type="checkbox" class="h-5 w-5 rounded border-line text-primary-700"> Ingat pilihan saya</label>
                <label class="field-label">Pilih warna kategori <input type="color" value="#226B50" class="ml-2 h-11 w-16 rounded border border-line p-1"></label>
            </section>

            <section class="surface-card space-y-4 p-5 lg:col-span-2">
                <h2 class="text-lg font-semibold">Bagian formulir dan ringkasan</h2>
                <x-form-section title="Rencana tugas" description="Isikan hal utama yang ingin kamu selesaikan.">
                    <x-field id="guide-section-title" label="Judul tugas" help="Gunakan nama yang mudah dikenali.">
                        <input id="guide-section-title" class="form-control" value="Membaca catatan">
                    </x-field>
                    <x-error-summary :errors="['guide-section-title' => ['Contoh ringkasan: periksa judul tugas.']]" />
                </x-form-section>
                <div class="grid gap-3 sm:grid-cols-3">
                    <x-stat-card label="Tugas selesai" value="4 dari 6" description="Dua tugas masih menunggu." icon="tugas" />
                    <x-badge tone="success">Selesai</x-badge>
                    <x-tabs :tabs="[['label' => 'Ringkasan', 'url' => '#ringkasan'], ['label' => 'Riwayat', 'url' => '#riwayat']]" active="#ringkasan" />
                </div>
                <x-form-actions :cancel-url="route('home')" submit-label="Simpan contoh" />
            </section>

            <section class="surface-card space-y-3 p-5">
                <h2 class="text-lg font-semibold">Pesan dan status</h2>
                <x-alert type="success">Tugas berhasil disimpan.</x-alert>
                <x-alert type="warning">Waktu tugas ini berdekatan dengan jadwal lain.</x-alert>
                <x-alert type="danger">Periksa kembali alamat emailmu.</x-alert>
                <x-alert type="info">Kamu dapat mengubah zona waktu dari profil.</x-alert>
                <x-toast>Perubahan contoh berhasil disimpan.</x-toast>
            </section>

            <section class="surface-card space-y-4 p-5 lg:col-span-2">
                <h2 class="text-lg font-semibold">Ringkasan keuangan dan laporan</h2>
                <div class="grid gap-3 sm:grid-cols-3">
                    <article class="rounded-lg border border-line p-4"><p class="text-sm text-muted">Pemasukan</p><strong class="mt-1 block" data-private>Rp 2.500.000</strong></article>
                    <article class="rounded-lg border border-line p-4"><p class="text-sm text-muted">Pengeluaran</p><strong class="mt-1 block" data-private>Rp 1.250.000</strong></article>
                    <article class="rounded-lg border border-line p-4"><p class="text-sm text-muted">Sisa</p><strong class="mt-1 block" data-private>Rp 1.250.000</strong></article>
                </div>
                <div>
                    <div class="mb-1 flex justify-between gap-3 text-sm"><span>Makan &amp; minum</span><strong data-private>Rp 500.000</strong></div>
                    <div class="h-2 overflow-hidden rounded-full bg-gray-100" role="progressbar" aria-label="Pengeluaran kategori Makan dan minum" aria-valuemin="0" aria-valuemax="100" aria-valuenow="100"><div class="h-full w-full rounded-full bg-primary-700"></div></div>
                </div>
            </section>

            <section class="surface-card p-5">
                <h2 class="mb-4 text-lg font-semibold">Keadaan kosong dan memuat</h2>
                <x-empty-state title="Belum ada agenda" description="Tambahkan tugas untuk mulai menyusun harimu." action-url="{{ route('register') }}" action-label="Daftar gratis" />
                <div class="mt-5 space-y-3" aria-label="Contoh pemuatan">
                    <x-skeleton height="h-5" width="w-1/2" />
                    <x-skeleton />
                    <x-skeleton width="w-2/3" />
                </div>
            </section>

            <section class="surface-card p-5 lg:col-span-2">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div><h2 class="text-lg font-semibold">Konfirmasi tindakan</h2><p class="mt-1 text-sm text-muted">Modal menjelaskan akibat sebelum pengguna melanjutkan.</p></div>
                    <div class="flex gap-2">
                        <x-button type="button" x-data x-on:click="$dispatch('open-modal', 'styleguide-confirm')" variant="danger" icon="hapus">Contoh hapus</x-button>
                        <x-button type="button" x-data x-on:click="$dispatch('open-modal', 'styleguide-sheet')" variant="secondary" icon="info">Contoh bottom-sheet</x-button>
                    </div>
                </div>
                <x-modal name="styleguide-confirm" focusable>
                    <div class="p-6">
                        <h3 class="text-lg font-semibold">Hapus contoh ini?</h3>
                        <p class="mt-2 text-sm text-muted">Tindakan ini tidak dapat dibatalkan.</p>
                        <div class="mt-5 flex justify-end"><x-secondary-button x-on:click="$dispatch('close')">Batal</x-secondary-button></div>
                    </div>
                </x-modal>
                <x-bottom-sheet name="styleguide-sheet" title="Contoh tambah cepat">
                    <p class="mb-4 text-sm text-muted">Panel ini berubah menjadi dialog pada layar yang lebih lebar.</p>
                    <x-field id="guide-sheet-title" label="Judul tugas" help="Nama ini membantumu mengenali tugas.">
                        <input id="guide-sheet-title" class="form-control" value="Menyiapkan rapat">
                    </x-field>
                    <div class="mt-4 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <x-button type="button" variant="secondary" icon="kembali" x-on:click="$dispatch('close')">Batal</x-button>
                        <x-button type="button" variant="primary" icon="selesai" x-on:click="$dispatch('close')">Simpan contoh</x-button>
                    </div>
                </x-bottom-sheet>
                <div class="mt-5 overflow-x-auto rounded-lg border border-line">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50"><tr><th class="px-4 py-3">Nama</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Waktu</th></tr></thead>
                        <tbody><tr class="border-t border-line"><td class="px-4 py-3">Menyiapkan rapat</td><td class="px-4 py-3"><x-status-badge status="Aktif" /></td><td class="px-4 py-3">09.00–10.00</td></tr></tbody>
                    </table>
                </div>
            </section>
        </div>
    </main>
@endsection
