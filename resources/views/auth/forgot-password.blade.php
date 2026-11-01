<x-guest-layout>
    <div class="mb-6">
        <p class="text-sm font-semibold text-primary-800">Bantuan akun</p>
        <h1 class="mt-1 text-2xl font-semibold">Atur ulang password</h1>
        <p class="mt-2 text-sm leading-6 text-muted">Masukkan alamat email akun Anda. Kami akan mengirim tautan untuk membuat password baru.</p>
    </div>
    @if (session('status'))
        <x-alert type="success" class="mb-5">{{ session('status') }}</x-alert>
    @endif
    @if ($errors->any())
        <x-alert type="danger" class="mb-5">{{ $errors->first() }}</x-alert>
    @endif
    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf
        <div>
            <x-input-label for="email" value="Alamat email" />
            <x-text-input id="email" name="email" type="email" class="mt-1" :value="old('email')" required autofocus autocomplete="email" maxlength="255" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" aria-describedby="email-error" />
            <x-input-error id="email-error" :messages="$errors->get('email')" />
        </div>
        <button type="submit" class="btn-primary w-full" data-loading-text="Mengirim...">Kirim tautan pengaturan ulang</button>
        <p class="border-t border-line pt-5 text-center text-sm text-muted"><a href="{{ route('login') }}" class="font-semibold text-primary-800 underline-offset-4 hover:underline">Kembali ke halaman masuk</a></p>
    </form>
</x-guest-layout>
