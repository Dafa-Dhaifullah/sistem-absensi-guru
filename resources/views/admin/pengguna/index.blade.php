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
                    </div>

                    {{-- Notifikasi akan muncul di sini dari layout --}}

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Peran</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No. WA</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($semuaPengguna as $user)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $user->name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $user->email }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 capitalize">{{ $user->role }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $user->no_wa ?? '-' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-4 items-center">
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

