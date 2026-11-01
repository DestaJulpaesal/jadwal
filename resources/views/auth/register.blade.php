<x-guest-layout>
    <div class="mb-6">
        <p class="text-sm font-semibold text-primary-800">Mulai mengatur hari Anda</p>
        <h1 class="mt-1 text-2xl font-semibold">Buat akun</h1>
        <p class="mt-2 text-sm text-muted">Daftar gratis untuk menyimpan jadwal pribadi Anda.</p>
    </div>
    @if ($errors->any())
        <x-alert type="danger" class="mb-5">
            <p class="font-semibold">Periksa kembali isian berikut:</p>
            <ul class="mt-2 list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </x-alert>
    @endif
    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf
        <div>
            <x-input-label for="name" value="Nama lengkap" />
            <x-text-input id="name" name="name" type="text" class="mt-1" :value="old('name')" required autofocus maxlength="255" autocomplete="name" aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}" aria-describedby="name-error" />
            <x-input-error id="name-error" :messages="$errors->get('name')" />
        </div>
        <div>
            <x-input-label for="email" value="Alamat email" />
            <x-text-input id="email" name="email" type="email" class="mt-1" :value="old('email')" required maxlength="255" autocomplete="email" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" aria-describedby="email-error" />
            <x-input-error id="email-error" :messages="$errors->get('email')" />
        </div>
        <div>
            <x-input-label for="password" value="Password" />
            <div class="mt-1 flex gap-2">
                <x-text-input id="password" name="password" type="password" class="min-w-0 flex-1" required autocomplete="new-password" data-password-field aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}" aria-describedby="password-help password-error" />
                <button type="button" class="btn-secondary px-3" data-password-toggle data-password-show-icon="{{ asset('icons/sprite.svg#icon-lihat-password') }}" data-password-hide-icon="{{ asset('icons/sprite.svg#icon-sembunyikan-password') }}" aria-label="Tampilkan password">
                    <x-icon name="lihat-password" class="h-5 w-5" data-password-icon />
                    <span data-password-label>Tampilkan</span>
                </button>
            </div>
            <p id="password-help" class="field-help">Gunakan password yang kuat dan simpan dengan aman.</p>
            <x-input-error id="password-error" :messages="$errors->get('password')" />
        </div>
        <div>
            <x-input-label for="password_confirmation" value="Ulangi password" />
            <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1" required autocomplete="new-password" aria-invalid="{{ $errors->has('password_confirmation') ? 'true' : 'false' }}" aria-describedby="password-confirmation-error" />
            <x-input-error id="password-confirmation-error" :messages="$errors->get('password_confirmation')" />
        </div>
        <button type="submit" class="btn-primary w-full" data-loading-text="Membuat akun..."><x-icon name="tambah" class="h-5 w-5" />Daftar</button>
        <p class="border-t border-line pt-5 text-center text-sm text-muted">Sudah punya akun? <a href="{{ route('login') }}" class="font-semibold text-primary-800 underline-offset-4 hover:underline">Masuk</a></p>
    </form>
</x-guest-layout>
