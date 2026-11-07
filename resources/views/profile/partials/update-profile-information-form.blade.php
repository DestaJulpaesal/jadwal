<section>
    <header>
        <h2 class="text-lg font-semibold">Informasi profil</h2>
        <p class="mt-1 text-sm text-muted">Nama dan email yang digunakan untuk akun Anda. Atur zona waktu dan preferensi aplikasi di Pengaturan.</p>
    </header>

    @if (config('auth.email_verification'))
        <form id="send-verification" method="post" action="{{ route('verification.send') }}">@csrf</form>
    @endif

    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="mt-6 space-y-5" data-dirty-guard>
        @csrf
        @method('patch')
        <x-error-summary :errors="$errors->messages()" title="Profil belum diperbarui. Periksa isian berikut:" />

        <div class="profile-photo-editor">
            <x-profile-avatar :user="$user" class="h-20 w-20" />
            <div class="min-w-0 flex-1">
                <label for="avatar" class="field-label">Foto profil <span class="font-normal text-muted">(opsional)</span></label>
                <input id="avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/webp" class="form-control mt-1 p-2" aria-describedby="avatar-help avatar-error">
                <p id="avatar-help" class="field-help">JPG, PNG, atau WebP. Maksimal 5 MB.</p>
                <x-input-error id="avatar-error" :messages="$errors->get('avatar')" />
                @if ($user->profile_photo_path)
                    <label class="mt-2 inline-flex min-h-11 items-center gap-2 text-sm font-medium">
                        <input type="hidden" name="remove_avatar" value="0">
                        <input name="remove_avatar" type="checkbox" value="1" class="h-4 w-4 rounded border-line text-primary-700">
                        Hapus foto profil
                    </label>
                @endif
            </div>
        </div>

        <x-field id="name" label="Nama" help="Nama ini ditampilkan di akun dan sapaan aplikasi." :error="$errors->first('name')" required>
            <x-text-input id="name" name="name" type="text" class="mt-1" :value="old('name', $user->name)" required maxlength="255" autocomplete="name" autocapitalize="words" enterkeyhint="next" data-validate-on-blur aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}" aria-describedby="name-help name-error" />
        </x-field>
        <x-field id="email" label="Alamat email" help="Email dipakai untuk masuk dan menerima pengingat akun." :error="$errors->first('email')" required>
            <x-text-input id="email" name="email" type="email" class="mt-1" :value="old('email', $user->email)" required maxlength="255" autocomplete="email" inputmode="email" autocapitalize="none" enterkeyhint="done" data-validate-on-blur aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" aria-describedby="email-help email-error" />
        </x-field>
        @if (config('auth.email_verification') && $user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
            <div class="rounded-lg bg-canvas p-3">
                <p class="text-sm text-warning">Alamat email Anda belum diverifikasi.</p>
                <x-button form="send-verification" class="mt-2 min-h-11 text-sm font-semibold text-primary-800 underline underline-offset-4">Kirim ulang tautan verifikasi</x-button>
            </div>
        @endif
        <x-form-actions :cancel-url="route('profile.edit')" submit-label="Simpan profil" loading-label="Menyimpan profil…" />
    </form>
</section>
