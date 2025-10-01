<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Manajemen Data Pengguna') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                   @php
    $role = request('role');
    $label = $role ? 'Tambah ' . ucwords(str_replace('_', ' ', $role)) . ' Baru' : 'Tambah Pengguna Baru';
@endphp
<div class="mb-4">
    <a href="{{ route('admin.pengguna.create', ['role' => $role]) }}">
        <x-primary-button>{{ $label }}</x-primary-button>
    </a>
    </div>

                    @if (session('success'))
                        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-md">
                            {{ session('success') }}
                        </div>
                    @endif
                    @if (session('error'))
                        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-md">
                            {{ session('error') }}
                        </div>
                    @endif

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Username / NIP</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hak Akses</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kontak</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($semuaPengguna as $user)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $user->name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <div>{{ $user->username }}</div>
                                            <div class="text-xs text-gray-500">{{ $user->nip ?? 'NIP: -' }}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            @php
                                                $roleColor = 'bg-gray-100 text-gray-800';
                                                if ($user->role == 'admin') $roleColor = 'bg-red-100 text-red-800';
                                                if ($user->role == 'kepala_sekolah') $roleColor = 'bg-purple-100 text-purple-800';
                                                if ($user->role == 'piket') $roleColor = 'bg-yellow-100 text-yellow-800';
                                                if ($user->role == 'guru') $roleColor = 'bg-blue-100 text-blue-800';
                                            @endphp
                                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $roleColor }}">
                                                {{ str_replace('_', ' ', $user->role) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $user->no_wa ?? '-' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="{{ route('admin.pengguna.edit', $user->id) }}" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                                            
                                            <form action="{{ route('admin.pengguna.resetPassword', $user->id) }}" method="POST" class="inline" onsubmit="return confirm('Yakin reset password?');">
                                                @csrf
                                                <button type="submit" class="text-yellow-600 hover:text-yellow-900 ml-2">Reset Pass</button>
                                            </form>
                                            
                                            @if(Auth::id() !== $user->id)
                                            <form action="{{ route('admin.pengguna.destroy', $user->id) }}" method="POST" class="inline" onsubmit="return confirm('Yakin hapus?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900 ml-2">Hapus</button>
                                            </form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">Data belum tersedia.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">{{ $semuaPengguna->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>