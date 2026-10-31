<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl">Manajemen Akun</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @include('admin.users.index')
        </div>
    </div>
</x-app-layout>
