<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Task Statuses') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-md sm:rounded-lg">
                <a href="{{ route('admin.statuses.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-md">Add New Status</a>

                @if(session('success'))
                    <div class="mt-4 text-green-600">
                        {{ session('success') }}
                    </div>
                @endif

                <table class="w-full mt-6 border">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="border px-4 py-2 text-left">Name</th>
                            <th class="border px-4 py-2 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($statuses as $status)
                            <tr>
                                <td class="border px-4 py-2">{{ $status->name }}</td>
                                <td class="border px-4 py-2 text-center">
                                    <a href="{{ route('admin.statuses.edit', $status) }}" class="bg-yellow-500 text-white px-3 py-1 rounded">Edit</a>
                                    <form action="{{ route('admin.statuses.destroy', $status) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="bg-red-500 text-white px-3 py-1 rounded" onclick="return confirm('Are you sure?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
