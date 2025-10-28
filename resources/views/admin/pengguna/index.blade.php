<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Manajemen Pengguna') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-4">
                        <a href="{{ route('admin.pengguna.create') }}">
                            <x-primary-button>{{ __('Tambah Pengguna Baru') }}</x-primary-button>
                        </a>
                        
                        <a href="{{ route('admin.pengguna.import.form') }}" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">
                            Import Pengguna
                        </a>
                    </div>
                    <div>
                            <a href="{{ route('admin.pengguna.arsip') }}" class="text-sm text-gray-600 hover:text-gray-900 flex items-center">
                                <svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M5.5 16.5A1.5 1.5 0 014 15V6.5a1.5 1.5 0 013 0V15a1.5 1.5 0 01-1.5 1.5zM8 15V6.5a1.5 1.5 0 013 0V15a1.5 1.5 0 01-3 0zM13.5 15V6.5a1.5 1.5 0 013 0V15a1.5 1.5 0 01-3 0z" /><path fill-rule="evenodd" d="M1 4a1 1 0 011-1h16a1 1 0 011 1v1a1 1 0 01-1 1H2a1 1 0 01-1-1V4z" clip-rule="evenodd" /></svg>
                                Buka Arsip
                            </a>
                        </div>
                    <div class="mb-4 p-4 bg-gray-50 rounded-lg border">
                        <form action="{{ route('admin.pengguna.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                            <!-- Kolom Pencarian -->
                            <div class="md:col-span-2">
                                <x-input-label for="search" :value="__('Cari Pengguna (Nama, Username, atau NIP)')" />
                                <x-text-input id="search" class="block mt-1 w-full" type="text" name="search" :value="request('search')" placeholder="Ketik pencarian..." />
                            </div>
                            <!-- Filter Role -->
                            <div>
                                <x-input-label for="role" :value="__('Filter Berdasarkan Role')" />
                                <select name="role" id="role" class="block mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Semua Role</option>
                                    <option value="admin" @if(request('role') == 'admin') selected @endif>Admin</option>
                                    <option value="pimpinan" @if(request('role') == 'pimpinan') selected @endif>Pimpinan</option>
                                   
                                    <option value="guru" @if(request('role') == 'guru') selected @endif>Guru</option>
                                </select>
                            </div>
                            <!-- Tombol -->
                            <div>
                                <x-primary-button>Cari</x-primary-button>
                                <a href="{{ route('admin.pengguna.index') }}" class="text-sm text-gray-500 hover:text-gray-800 ml-2">Reset</a>
                            </div>
                        </form>
                    </div>

                    {{-- Notifikasi akan muncul di sini dari layout --}}

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Peran</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No. WA</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($semuaPengguna as $user)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $user->name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $user->username }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 capitalize">{{ $user->role }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $user->no_wa ?? '-' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-4 items-center">
                                                 <div>
                                                    <a href="{{ route('admin.pengguna.show', $user->id) }}" class="text-gray-600 hover:text-gray-900">Detail</a>
                                                </div>
                                                <div>
                                                    <a href="{{ route('admin.pengguna.edit', $user->id) }}" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                                                </div>
                                                
                                                <div x-data>
                                                    <form x-ref="resetForm{{ $user->id }}" action="{{ route('admin.pengguna.resetPassword', $user->id) }}" method="POST">
                                                        @csrf
                                                        <button type="button" @click.prevent="
                                                            Swal.fire({
                                                                title: 'Yakin reset password?',
                                                                text: 'Password pengguna ini akan diubah ke default!',
                                                                icon: 'warning',
                                                                showCancelButton: true,
                                                                confirmButtonColor: '#3085d6',
                                                                cancelButtonColor: '#d33',
                                                                confirmButtonText: 'Ya, reset!',
                                                                cancelButtonText: 'Batal'
                                                            }).then((result) => {
                                                                if (result.isConfirmed) {
                                                                    $refs.resetForm{{ $user->id }}.submit();
                                                                }
                                                            })
                                                        " class="text-yellow-600 hover:text-yellow-900">Reset Pass</button>
                                                    </form>
                                                </div>
                                                
                                                @if(Auth::id() !== $user->id)
                                                <div x-data>
                                                    <form x-ref="deleteForm{{ $user->id }}" action="{{ route('admin.pengguna.destroy', $user->id) }}" method="POST">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="button" @click.prevent="
                                                            Swal.fire({
                                                                title: 'Yakin hapus?',
                                                                text: 'Data pengguna ini akan dihapus!',
                                                                icon: 'warning',
                                                                showCancelButton: true,
                                                                confirmButtonColor: '#d33',
                                                                cancelButtonColor: '#3085d6',
                                                                confirmButtonText: 'Ya, hapus!',
                                                                cancelButtonText: 'Batal'
                                                            }).then((result) => {
                                                                if (result.isConfirmed) {
                                                                    $refs.deleteForm{{ $user->id }}.submit();
                                                                }
                                                            })
                                                        " class="text-red-600 hover:text-red-900">Hapus</button>
                                                    </form>
                                                </div>
                                                @endif
                                            </div>
                                            </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                            Data pengguna belum tersedia.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $semuaPengguna->links() }}
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-admin-layout>

