<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Manajemen Kalender Blok') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    <div class="mb-4">
                        <a href="{{ route('admin.kalender-blok.create') }}">
                            <x-primary-button>{{ __('Tambah Rentang Waktu Baru') }}</x-primary-button>
                        </a>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipe Minggu</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Mulai</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Selesai</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($kalender as $item)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold 
                                            {{ $item->tipe_minggu == 'Minggu 1' ? 'text-blue-600' : 'text-green-600' }}">
                                            {{ $item->tipe_minggu }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ \Carbon\Carbon::parse($item->tanggal_mulai)->locale('id_ID')->isoFormat('D MMMM YYYY') }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ \Carbon\Carbon::parse($item->tanggal_selesai)->locale('id_ID')->isoFormat('D MMMM YYYY') }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="{{ route('admin.kalender-blok.edit', $item->id) }}" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                                            <div x-data class="inline">
                                                <form x-ref="form{{$item->id }}" action="{{ route('admin.kalender-blok.destroy', $item->id) }}" method="POST" class="inline">
                                                @csrf
                                                    @method('DELETE')
                                                    <button type="button" @click.prevent="
                                                        Swal.fire({
                                                            title: 'Anda Yakin?',
                                                            text: 'Data blok kalender ini akan dihapus.',
                                                            icon: 'warning',
                                                            showCancelButton: true,
                                                            confirmButtonColor: '#d33',
                                                            cancelButtonColor: '#3085d6',
                                                            confirmButtonText: 'Ya, hapus!',
                                                            cancelButtonText: 'Batal'
                                                        }).then((result) => {
                                                            if (result.isConfirmed) {
                                                                $refs.form{{ $item->id }}.submit();
                                                            }
                                                        })
                                                    " class="text-red-600 hover:text-red-900 ml-2">Hapus</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">Data belum tersedia.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">{{ $kalender->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>