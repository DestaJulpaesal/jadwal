<x-guest-layout>
    <div class="mb-6">
        <p class="text-sm font-semibold text-primary-800">Bantuan akun</p>
        <h1 class="mt-1 text-2xl font-semibold">Atur ulang kata sandi</h1>
        <p class="mt-2 text-sm leading-6 text-muted">Masukkan alamat email akunmu untuk menerima tautan pengaturan ulang kata sandi.</p>
    </div>
    <x-error-summary :errors="$errors->messages()" title="Tautan belum dikirim. Periksa isian berikut:" />
    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf
        <div>
            <x-input-label for="email" value="Alamat email (wajib)" />
            <x-text-input id="email" name="email" type="email" inputmode="email" autocapitalize="none" enterkeyhint="done" class="mt-1" :value="old('email')" required autofocus autocomplete="email" maxlength="255" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" aria-describedby="email-help email-error" />
            <p id="email-help" class="field-help">Tautan pengaturan ulang akan dikirim ke alamat email akunmu.</p>
            <x-input-error id="email-error" :messages="$errors->get('email')" />
        </div>
        <x-button type="submit" data-loading-text="Mengirim..." data-reset-cooldown="{{ session()->has('feedback') ? 60 : 0 }}" variant="primary" class="w-full">Kirim tautan pengaturan ulang</x-button>
        <p class="border-t border-line pt-5 text-center text-sm text-muted"><a href="{{ route('login') }}" class="font-semibold text-primary-800 underline-offset-4 hover:underline">Kembali ke halaman masuk</a></p>
    </form>
</x-guest-layout>
