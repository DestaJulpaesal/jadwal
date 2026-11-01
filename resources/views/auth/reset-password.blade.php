<x-guest-layout>
    <div class="mb-6">
        <p class="text-sm font-semibold text-primary-800">Keamanan akun</p>
        <h1 class="mt-1 text-2xl font-semibold">Buat password baru</h1>
        <p class="mt-2 text-sm text-muted">Gunakan password yang kuat dan tidak dipakai di akun lain.</p>
    </div>
    @if ($errors->any())
        <x-alert type="danger" class="mb-5">{{ $errors->first() }}</x-alert>
    @endif
    <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <div>
            <x-input-label for="email" value="Alamat email" />
            <x-text-input id="email" name="email" type="email" class="mt-1" :value="old('email', $request->email)" required autofocus autocomplete="username" maxlength="255" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" aria-describedby="email-error" />
            <x-input-error id="email-error" :messages="$errors->get('email')" />
        </div>
        <div>
            <x-input-label for="password" value="Password baru" />
            <div class="mt-1 flex gap-2">
                <x-text-input id="password" name="password" type="password" class="min-w-0 flex-1" required autocomplete="new-password" data-password-field aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}" aria-describedby="password-error" />
                <button type="button" class="btn-secondary px-3" data-password-toggle data-password-show-icon="{{ asset('icons/sprite.svg#icon-lihat-password') }}" data-password-hide-icon="{{ asset('icons/sprite.svg#icon-sembunyikan-password') }}" aria-label="Tampilkan password">
                    <x-icon name="lihat-password" class="h-5 w-5" data-password-icon />
                    <span data-password-label>Tampilkan</span>
                </button>
            </div>
            <x-input-error id="password-error" :messages="$errors->get('password')" />
        </div>
        <div>
            <x-input-label for="password_confirmation" value="Ulangi password baru" />
            <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1" required autocomplete="new-password" aria-invalid="{{ $errors->has('password_confirmation') ? 'true' : 'false' }}" aria-describedby="password-confirmation-error" />
            <x-input-error id="password-confirmation-error" :messages="$errors->get('password_confirmation')" />
        </div>
        <button type="submit" class="btn-primary w-full" data-loading-text="Menyimpan...">Simpan password baru</button>
    </form>
</x-guest-layout>
