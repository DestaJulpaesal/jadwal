<section>
    <header>
        <h2 class="text-lg font-semibold">Informasi profil</h2>
        <p class="mt-1 text-sm text-muted">Nama dan email yang digunakan untuk akun Anda.</p>
    </header>

    @if (config('auth.email_verification'))
        <form id="send-verification" method="post" action="{{ route('verification.send') }}">@csrf</form>
    @endif

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-5">
        @csrf
        @method('patch')
        @if ($errors->any())
            <x-alert type="danger">
                <p class="font-semibold">Periksa kembali informasi berikut:</p>
                <ul class="mt-2 list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </x-alert>
        @endif

        <div>
            <x-input-label for="name" value="Nama" />
            <x-text-input id="name" name="name" type="text" class="mt-1" :value="old('name', $user->name)" required maxlength="255" autocomplete="name" aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}" aria-describedby="name-error" />
            <x-input-error id="name-error" :messages="$errors->get('name')" />
        </div>
        <div>
            <x-input-label for="email" value="Alamat email" />
            <x-text-input id="email" name="email" type="email" class="mt-1" :value="old('email', $user->email)" required maxlength="255" autocomplete="email" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" aria-describedby="email-error" />
            <x-input-error id="email-error" :messages="$errors->get('email')" />
            @if (config('auth.email_verification') && $user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <p class="mt-2 text-sm text-warning">Alamat email Anda belum diverifikasi.</p>
                <button form="send-verification" class="mt-2 min-h-11 text-sm font-semibold text-primary-800 underline underline-offset-4">Kirim ulang tautan verifikasi</button>
            @endif
        </div>
        <div>
            <label for="timezone" class="field-label inline-flex items-center gap-2"><x-icon name="zona-waktu" class="h-5 w-5" />Zona waktu</label>
            @php
                $timezone = old('timezone', $user->timezone);
            @endphp
            <select id="timezone" name="timezone" class="form-control mt-1" required aria-invalid="{{ $errors->has('timezone') ? 'true' : 'false' }}" aria-describedby="timezone-help timezone-error">
                <optgroup label="Indonesia">
                    <option value="Asia/Jakarta" @selected($timezone === 'Asia/Jakarta')>WIB — Jakarta</option>
                    <option value="Asia/Makassar" @selected($timezone === 'Asia/Makassar')>WITA — Makassar</option>
                    <option value="Asia/Jayapura" @selected($timezone === 'Asia/Jayapura')>WIT — Jayapura</option>
                </optgroup>
                <optgroup label="Pilihan lain">
                    <option value="Asia/Singapore" @selected($timezone === 'Asia/Singapore')>Singapura</option>
                    <option value="Asia/Tokyo" @selected($timezone === 'Asia/Tokyo')>Tokyo</option>
                    <option value="Australia/Sydney" @selected($timezone === 'Australia/Sydney')>Sydney</option>
                    <option value="Europe/London" @selected($timezone === 'Europe/London')>London</option>
                    <option value="America/New_York" @selected($timezone === 'America/New_York')>New York</option>
                    <option value="UTC" @selected($timezone === 'UTC')>UTC</option>
                    @if (! in_array($timezone, ['Asia/Jakarta', 'Asia/Makassar', 'Asia/Jayapura', 'Asia/Singapore', 'Asia/Tokyo', 'Australia/Sydney', 'Europe/London', 'America/New_York', 'UTC'], true))
                        <option value="{{ $timezone }}" selected>{{ $timezone }} · zona waktu saat ini</option>
                    @endif
                </optgroup>
            </select>
            <p id="timezone-help" class="field-help flex items-start gap-1.5"><x-icon name="info" class="mt-0.5 h-4 w-4" />Waktu tugas akan ditampilkan sesuai zona waktu yang Anda pilih.</p>
            <x-input-error id="timezone-error" :messages="$errors->get('timezone')" />
        </div>
        <div class="rounded-lg border border-line p-4">
            <label for="digest_enabled" class="flex min-h-11 items-center gap-3 font-medium">
                <input type="hidden" name="digest_enabled" value="0">
                <input id="digest_enabled" name="digest_enabled" type="checkbox" value="1" @checked(old('digest_enabled', $user->digest_enabled)) class="h-5 w-5 rounded border-line text-primary-700">
                Kirim ringkasan harian lewat email
            </label>
            <p class="ml-8 text-sm text-muted">Email dikirim setelah pukul 07.00 sesuai zona waktu Anda. Ringkasan ini nonaktif secara default.</p>
        </div>
        <button type="submit" class="btn-primary">Simpan perubahan</button>
    </form>
</section>
