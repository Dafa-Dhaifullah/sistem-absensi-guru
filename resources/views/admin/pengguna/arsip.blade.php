<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Arsip Data Pengguna') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-4 flex justify-between items-center">
                        <p class="text-sm text-gray-600">Daftar pengguna yang telah dihapus sementara.</p>
                        <a href="{{ route('admin.pengguna.index') }}" class="text-sm text-blue-600 hover:text-blue-900">&larr; Kembali ke Daftar Aktif</a>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal Dihapus</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($penggunaArsip as $user)
                                    <tr>
                                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $user->name }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-900">{{ $user->role }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-900">{{ $user->deleted_at->isoFormat('D MMMM YYYY') }}</td>
                                        <td class="px-6 py-4 text-sm font-medium">
                                            <div class="flex space-x-4">
                                                <!-- Form Restore -->
                                                <div x-data>
                                                    <form x-ref="formRestore{{$user->id}}" action="{{ route('admin.pengguna.restore', $user->id) }}" method="POST">
                                                        @csrf
                                                        <button type="button" @click.prevent="
                                                            Swal.fire({
                                                                title: 'Pulihkan Pengguna?',
                                                                text: 'Pengguna \'{{ $user->name }}\' akan dikembalikan ke daftar aktif.',
                                                                icon: 'info',
                                                                showCancelButton: true,
                                                                confirmButtonColor: '#3085d6',
                                                                cancelButtonColor: '#6B7280',
                                                                confirmButtonText: 'Ya, pulihkan!',
                                                                cancelButtonText: 'Batal'
                                                            }).then((result) => {
                                                                if (result.isConfirmed) {
                                                                    $refs.formRestore{{$user->id}}.submit();
                                                                }
                                                            })
                                                        " class="text-green-600 hover:text-green-900">Pulihkan</button>
                                                    </form>
                                                </div>

                                                <!-- Form Hapus Permanen -->
                                                <div x-data>
                                                    <form x-ref="formDelete{{$user->id}}" action="{{ route('admin.pengguna.forceDelete', $user->id) }}" method="POST">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="button" @click.prevent="
                                                            Swal.fire({
                                                                title: 'Hapus Permanen?',
                                                                text: 'Data pengguna ini akan dihapus selamanya dan TIDAK BISA dikembalikan!',
                                                                icon: 'warning',
                                                                showCancelButton: true,
                                                                confirmButtonColor: '#d33',
                                                                cancelButtonColor: '#3085d6',
                                                                confirmButtonText: 'Ya, hapus permanen!',
                                                                cancelButtonText: 'Batal'
                                                            }).then((result) => {
                                                                if (result.isConfirmed) {
                                                                    $refs.formDelete{{$user->id}}.submit();
                                                                }
                                                            })
                                                        " class="text-red-600 hover:text-red-900">Hapus Permanen</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="px-6 py-4 text-center text-gray-500">Arsip kosong.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
