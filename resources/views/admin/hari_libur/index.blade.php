<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Manajemen Hari Libur') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="mb-4">
                        <a href="{{ route('admin.hari-libur.create') }}">
                            <x-primary-button>{{ __('Tambah Hari Libur') }}</x-primary-button>
                        </a>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Keterangan</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($semuaHariLibur as $libur)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ \Carbon\Carbon::parse($libur->tanggal)->locale('id_ID')->isoFormat('dddd, D MMMM YYYY') }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $libur->keterangan }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <form x-ref="form{{$libur->id }}" action="{{ route('admin.hari-libur.destroy', $libur->id) }}" method="POST" class="inline">
                                                @csrf
                                                    @method('DELETE')
                                                    <button type="button" @click.prevent="
                                                        Swal.fire({
                                                            title: 'Anda Yakin?',
                                                            text: 'Data hari libur ini akan dihapus.',
                                                            icon: 'warning',
                                                            showCancelButton: true,
                                                            confirmButtonColor: '#d33',
                                                            cancelButtonColor: '#3085d6',
                                                            confirmButtonText: 'Ya, hapus!',
                                                            cancelButtonText: 'Batal'
                                                        }).then((result) => {
                                                            if (result.isConfirmed) {
                                                                $refs.form{{ $libur->id }}.submit();
                                                            }
                                                        })
                                                    " class="inline-block px-3 py-1 rounded-md text-xs font-medium transition-colors duration-150 bg-red-600 text-white hover:bg-red-700">Hapus</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-6 py-4 text-center text-gray-500">Belum ada data hari libur.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">{{ $semuaHariLibur->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
