<div class="overflow-hidden rounded-lg bg-white p-6 shadow-sm">
    @if (session('success'))
        <div class="mb-4 rounded bg-green-100 p-3 text-green-800">{{ session('success') }}</div>
    @endif

    @if ($errors->has('account'))
        <div class="mb-4 rounded bg-red-100 p-3 text-red-800">{{ $errors->first('account') }}</div>
    @endif

    <h3 class="mb-4 text-lg font-semibold">Daftar akun</h3>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b text-gray-600">
                    <th class="px-3 py-2">Nama</th>
                    <th class="px-3 py-2">Email</th>
                    <th class="px-3 py-2">Peran</th>
                    <th class="px-3 py-2">Status</th>
                    <th class="px-3 py-2">Dibuat</th>
                    <th class="px-3 py-2">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $user)
                    <tr class="border-b last:border-0">
                        <td class="px-3 py-3">{{ $user->name }}</td>
                        <td class="px-3 py-3">{{ $user->email }}</td>
                        <td class="px-3 py-3">{{ $user->isAdmin() ? 'Admin' : 'Pengguna' }}</td>
                        <td class="px-3 py-3">
                            <span class="rounded px-2 py-1 text-xs {{ $user->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' }}">
                                {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="px-3 py-3">{{ $user->created_at->format('d M Y') }}</td>
                        <td class="px-3 py-3">
                            @if ($user->id !== auth()->id())
                                <div class="flex flex-wrap gap-3">
                                    <form method="POST" action="{{ route('admin.users.status', $user) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="text-blue-700 hover:underline">
                                            {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Hapus akun ini beserta semua tugas dan kategorinya? Tindakan ini tidak dapat dibatalkan.')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-red-700 hover:underline">Hapus akun</button>
                                    </form>
                                </div>
                            @else
                                <span class="text-gray-500">Akun Anda</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-3 py-6 text-center text-gray-500">Belum ada akun.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $users->links() }}</div>
</div>
