<section class="space-y-5">
    <header>
        <h2 class="text-lg font-semibold text-danger">Hapus akun</h2>
        <p class="mt-1 text-sm leading-6 text-muted">Penghapusan akun akan menghapus semua tugas dan kategori Anda secara permanen. Tindakan ini tidak dapat dibatalkan.</p>
    </header>

    <x-button type="button" x-data x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')" variant="danger">Hapus akun</x-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-5 sm:p-6">
            @csrf
            @method('delete')
            <h2 class="text-lg font-semibold">Yakin ingin menghapus akun?</h2>
            <p class="mt-2 text-sm leading-6 text-muted">Semua tugas dan kategori yang tersimpan di akun ini juga akan dihapus permanen. Masukkan password untuk mengonfirmasi.</p>
            <div class="mt-5">
                <x-input-label for="password" value="Password Anda" />
                <x-text-input id="password" name="password" type="password" class="mt-1" autocomplete="current-password" required aria-invalid="{{ $errors->userDeletion->has('password') ? 'true' : 'false' }}" aria-describedby="delete-password-error" />
                <x-input-error id="delete-password-error" :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>
            <div class="mt-6 flex flex-col-reverse justify-end gap-2 sm:flex-row">
                <x-secondary-button x-on:click="$dispatch('close')">Batal</x-secondary-button>
                <x-danger-button>Hapus akun</x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
