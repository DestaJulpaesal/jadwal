<x-guest-layout>
    <div class="mb-6">
        <p class="text-sm font-semibold text-primary-800">Bantuan akun</p>
        <h1 class="mt-1 text-2xl font-semibold">Atur ulang password</h1>
        <p class="mt-2 text-sm leading-6 text-muted">Masukkan alamat email akun Anda. Kami akan mengirim tautan untuk membuat password baru.</p>
    </div>
    @if (session('status'))
        <x-alert type="success" class="mb-5">{{ session('status') }}</x-alert>
    @endif
    <x-error-summary :errors="$errors->messages()" title="Tautan belum dikirim. Periksa isian berikut:" />
    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf
        <div>
            <x-input-label for="email" value="Alamat email (wajib)" />
            <x-text-input id="email" name="email" type="email" inputmode="email" autocapitalize="none" enterkeyhint="done" class="mt-1" :value="old('email')" required autofocus autocomplete="email" maxlength="255" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" aria-describedby="email-help email-error" />
            <p id="email-help" class="field-help">Kami mengirim tautan pengaturan ulang ke alamat email akun Anda.</p>
            <x-input-error id="email-error" :messages="$errors->get('email')" />
        </div>
        <x-button type="submit" data-loading-text="Mengirim..." variant="primary" class="w-full">Kirim tautan pengaturan ulang</x-button>
        <p class="border-t border-line pt-5 text-center text-sm text-muted"><a href="{{ route('login') }}" class="font-semibold text-primary-800 underline-offset-4 hover:underline">Kembali ke halaman masuk</a></p>
    </form>
</x-guest-layout>
