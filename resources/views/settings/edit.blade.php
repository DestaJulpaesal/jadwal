@extends('layouts.user')

@section('title', 'Pengaturan')

@section('content')
    @php
        $timezones = [
            'Asia/Jakarta' => 'WIB — Jakarta',
            'Asia/Makassar' => 'WITA — Makassar',
            'Asia/Jayapura' => 'WIT — Jayapura',
            'Asia/Singapore' => 'Singapura',
            'Asia/Tokyo' => 'Tokyo',
            'Australia/Sydney' => 'Sydney',
            'Europe/London' => 'London',
            'America/New_York' => 'New York',
            'UTC' => 'UTC',
        ];
        $selectedTimezone = old('timezone', $user->timezone);
        $selectedDays = array_map('intval', old('digest_days', $user->digest_days ?? range(1, 7)));
        $selectedSections = old('dashboard_sections')
            ? array_keys(array_filter(old('dashboard_sections')))
            : ($user->dashboard_sections ?? array_keys($dashboardSectionLabels));
    @endphp

    <div class="mx-auto max-w-3xl space-y-5">
        <header class="mb-6">
            <p class="text-sm font-semibold text-primary-800">Sesuaikan aplikasi</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight">Pengaturan</h1>
            <p class="mt-2 text-muted">Atur tampilan, pengingat, dan informasi yang muncul di dashboard.</p>
        </header>

        @if ($errors->any())
            <x-alert type="danger">
                <p class="font-semibold">Periksa kembali pengaturan berikut:</p>
                <ul class="mt-2 list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-alert>
        @endif

        <form method="POST" action="{{ route('settings.update') }}" class="space-y-5">
            @csrf
            @method('PATCH')

            <section class="surface-card space-y-5 p-5 sm:p-7" aria-labelledby="preferences-heading">
                <div>
                    <h2 id="preferences-heading" class="text-xl font-semibold">Preferensi aplikasi</h2>
                    <p class="mt-1 text-sm text-muted">Pilih zona waktu, awal minggu, dan tema yang nyaman buatmu.</p>
                </div>

                <div>
                    <label for="timezone" class="field-label">Zona waktu</label>
                    <select id="timezone" name="timezone" class="form-control" required>
                        <optgroup label="Indonesia">
                            @foreach (['Asia/Jakarta', 'Asia/Makassar', 'Asia/Jayapura'] as $timezone)
                                <option value="{{ $timezone }}" @selected($selectedTimezone === $timezone)>{{ $timezones[$timezone] }}</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="Pilihan lain">
                            @foreach (array_diff_key($timezones, array_flip(['Asia/Jakarta', 'Asia/Makassar', 'Asia/Jayapura'])) as $timezone => $label)
                                <option value="{{ $timezone }}" @selected($selectedTimezone === $timezone)>{{ $label }}</option>
                            @endforeach
                            @if (! array_key_exists($selectedTimezone, $timezones))
                                <option value="{{ $selectedTimezone }}" selected>{{ $selectedTimezone }} · zona waktu saat ini</option>
                            @endif
                        </optgroup>
                    </select>
                    <p class="field-help">Waktu tugas dan jadwal email mengikuti zona waktu ini.</p>
                </div>

                <fieldset>
                    <legend class="field-label">Awal minggu</legend>
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach (['monday' => 'Senin', 'sunday' => 'Minggu'] as $value => $label)
                            <label class="settings-choice">
                                <input type="radio" name="week_starts_on" value="{{ $value }}" @checked(old('week_starts_on', $user->week_starts_on) === $value) required>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    <p class="field-help">Mengubah urutan hari kalender dan hitungan kebiasaan mingguan.</p>
                </fieldset>

                <div>
                    <label for="theme" class="field-label">Tema</label>
                    <select id="theme" name="theme" class="form-control" required>
                        <option value="light" @selected(old('theme', $user->theme ?? 'light') === 'light')>Terang</option>
                        <option value="dark" @selected(old('theme', $user->theme) === 'dark')>Gelap</option>
                        <option value="system" @selected(old('theme', $user->theme ?? 'light') === 'system')>Ikuti pengaturan perangkat</option>
                    </select>
                </div>
            </section>

            <section class="surface-card space-y-5 p-5 sm:p-7" aria-labelledby="reminders-heading">
                <div>
                    <h2 id="reminders-heading" class="text-xl font-semibold">Pengingat &amp; email</h2>
                    <p class="mt-1 text-sm text-muted">Ringkasan berisi tugas, kebiasaan, dan agenda yang perlu diperhatikan.</p>
                    <p class="mt-1 text-sm text-muted">Pengiriman otomatis memerlukan scheduler Laravel yang berjalan setiap menit.</p>
                </div>

                <label class="settings-toggle">
                    <span>
                        <span class="block font-semibold">Ringkasan harian lewat email</span>
                        <span class="mt-1 block text-sm text-muted">Email dikirim ke {{ $user->email }}.</span>
                    </span>
                    <input type="hidden" name="digest_enabled" value="0">
                    <input type="checkbox" name="digest_enabled" value="1" @checked(old('digest_enabled', $user->digest_enabled))>
                </label>

                <div>
                    <label for="digest_time" class="field-label">Jam pengiriman</label>
                    <input id="digest_time" name="digest_time" type="time" value="{{ old('digest_time', $user->digest_time ?: '07:00') }}" class="form-control sm:max-w-xs" required>
                    <p class="field-help">Mengikuti zona waktu akun. Default: 07.00.</p>
                </div>

                <fieldset>
                    <legend class="field-label">Hari pengiriman</legend>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                        @foreach ([1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'] as $day => $label)
                            <label class="settings-choice">
                                <input type="checkbox" name="digest_days[]" value="{{ $day }}" @checked(in_array($day, $selectedDays, true))>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('digest_days')
                        <p class="field-error">{{ $message }}</p>
                    @enderror
                </fieldset>
            </section>

            <section class="surface-card space-y-5 p-5 sm:p-7" aria-labelledby="privacy-heading">
                <div>
                    <h2 id="privacy-heading" class="text-xl font-semibold">Privasi</h2>
                    <p class="mt-1 text-sm text-muted">Kendalikan nominal dan kartu yang terlihat saat membuka dashboard.</p>
                </div>

                <label class="settings-toggle">
                    <span>
                        <span class="block font-semibold">Sembunyikan nominal keuangan</span>
                        <span class="mt-1 block text-sm text-muted">Menyamarkan nominal di dashboard, keuangan, laporan, dan utang/piutang.</span>
                    </span>
                    <input type="hidden" name="hide_financial_amounts" value="0">
                    <input type="checkbox" name="hide_financial_amounts" value="1" @checked(old('hide_financial_amounts', $user->hide_financial_amounts))>
                </label>

                <fieldset>
                    <legend class="field-label">Informasi di dashboard</legend>
                    <p class="field-help mb-3 mt-0">Matikan kartu yang tidak ingin kamu lihat. Perlu perhatian mencakup tugas yang terlewat.</p>
                    <input type="hidden" name="dashboard_sections[attention]" value="0">
                    <input type="hidden" name="dashboard_sections[habits]" value="0">
                    <input type="hidden" name="dashboard_sections[today_tasks]" value="0">
                    <input type="hidden" name="dashboard_sections[upcoming]" value="0">
                    <input type="hidden" name="dashboard_sections[finance]" value="0">
                    <div class="space-y-2">
                        @foreach ($dashboardSectionLabels as $key => $label)
                            <label class="settings-toggle">
                                <span class="font-medium">{{ $label }}</span>
                                <input type="checkbox" name="dashboard_sections[{{ $key }}]" value="1" @checked(in_array($key, $selectedSections, true))>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            </section>

            <div class="flex justify-end">
                <x-button type="submit" variant="primary">Simpan pengaturan</x-button>
            </div>
        </form>
    </div>
@endsection
