<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Task Status Detail') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-md sm:rounded-lg">
                <div class="mb-4">
                    <h3 class="text-lg font-bold">Name:</h3>
                    <p>{{ $status->name }}</p>
                </div>
                <a href="{{ route('admin.statuses.index') }}" class="text-blue-600 hover:underline">Back to list</a>
            </div>
        </div>
    </div>
</x-app-layout>
