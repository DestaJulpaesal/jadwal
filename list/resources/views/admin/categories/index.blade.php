<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Categories') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6">
                    <a href="{{ route('admin.categories.create') }}" class="bg-blue-500 text-white px-4 py-2 rounded-md">Create New Category</a>

                    @if(session('success'))
                        <div class="mt-4 text-green-500">{{ session('success') }}</div>
                    @endif

                    <table class="min-w-full mt-6">
                        <thead>
                            <tr>
                                <th class="py-2 px-4 border">Name</th>
                                <th class="py-2 px-4 border">Color</th>
                                <th class="py-2 px-4 border text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($categories as $category)
                                <tr>
                                    <td class="py-2 px-4 border">{{ $category->nama }}</td>
                                    <td class="py-2 px-4 border">{{ $category->color }}</td>
                                    <td class="py-2 px-4 border text-center">
                                        <a href="{{ route('categories.edit', $category) }}" class="bg-yellow-500 text-white px-4 py-2 rounded-md">Edit</a>

                                        <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" style="display:inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded-md" onclick="return confirm('Are you sure?')">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
