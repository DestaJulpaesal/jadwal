<section class="space-y-5">
    <header>
        <h2 class="text-lg font-semibold text-danger">Hapus akun</h2>
        <p class="mt-1 text-sm leading-6 text-muted">Penghapusan akun akan menghapus data pribadi Anda secara permanen. Tindakan ini tidak dapat dibatalkan.</p>
    </header>

    <x-button type="button" x-data x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')" variant="danger">Hapus akun</x-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-5 sm:p-6">
            @csrf
            @method('delete')
            <h2 class="text-lg font-semibold">Yakin ingin menghapus akun?</h2>
            <p id="account" class="mt-2 text-sm leading-6 text-muted">Semua data akun akan dihapus permanen. Ketik HAPUS dan masukkan password untuk melanjutkan.</p>
            <x-error-summary :errors="$errors->userDeletion->messages()" title="Akun belum dihapus. Periksa konfirmasi berikut:" />
            <div class="mt-5">
                <x-field id="confirmation" label="Ketik HAPUS untuk mengonfirmasi" help="Konfirmasi ini memastikan penghapusan akun memang disengaja."
                    :error="$errors->userDeletion->first('confirmation')" required>
                    <x-text-input id="confirmation" name="confirmation" type="text" class="mt-1" value="{{ old('confirmation') }}"
                        required maxlength="4" autocapitalize="characters" autocomplete="off" />
                </x-field>
            </div>
            <div class="mt-5">
                <x-input-label for="password" value="Password Anda (wajib)" />
                <x-text-input id="password" name="password" type="password" class="mt-1" autocomplete="current-password" required aria-invalid="{{ $errors->userDeletion->has('password') ? 'true' : 'false' }}" aria-describedby="delete-password-error" />
                <x-input-error id="delete-password-error" :messages="$errors->userDeletion->get('password')" class="mt-2" />
                <p class="field-help">Password ini diperlukan untuk memastikan Anda pemilik akun.</p>
            </div>
            <div class="form-actions-bar sticky -mx-5 mt-6 flex flex-col-reverse gap-2 border-t border-line bg-surface/95 p-4 pb-[calc(1rem+env(safe-area-inset-bottom))] backdrop-blur sm:mx-0 sm:flex-row sm:justify-end sm:rounded-xl sm:border-0 sm:bg-transparent sm:p-0">
                <x-secondary-button x-on:click="$dispatch('close')">Batal</x-secondary-button>
                <x-danger-button>Hapus akun</x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
