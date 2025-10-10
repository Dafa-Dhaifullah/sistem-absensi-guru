<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Manajemen Jadwal Pelajaran') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    <div class="mb-4 flex gap-4">
                        <a href="{{ route('admin.jadwal-pelajaran.create') }}">
                            <x-primary-button>{{ __('Tambah Jadwal Baru') }}</x-primary-button>
                        </a>
                        <a href="{{ route('admin.jadwal-pelajaran.import.form') }}">
                            <x-secondary-button>{{ __('Import Jadwal') }}</x-secondary-button>
                        </a>
                    </div>

                    {{-- Notifikasi akan muncul di sini dari layout --}}

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Guru</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hari</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Jam Ke-</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kelas</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mata Pelajaran</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Blok</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($semuaJadwal as $jadwal)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $jadwal->user->name ?? 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $jadwal->hari }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-900">{{ $jadwal->jam_ke }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $jadwal->kelas }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $jadwal->mata_pelajaran ?? '-' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $jadwal->tipe_blok }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="{{ route('admin.jadwal-pelajaran.edit', $jadwal->id) }}" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                                            
                                            <div x-data class="inline">
                                                <form x-ref="form{{ $jadwal->id }}" action="{{ route('admin.jadwal-pelajaran.destroy', $jadwal->id) }}" method="POST" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" @click.prevent="
                                                        Swal.fire({
                                                            title: 'Anda Yakin?',
                                                            text: 'Jadwal untuk kelas {{ $jadwal->kelas }} pada jam ke-{{$jadwal->jam_ke}} akan dihapus.',
                                                            icon: 'warning',
                                                            showCancelButton: true,
                                                            confirmButtonColor: '#d33',
                                                            cancelButtonColor: '#3085d6',
                                                            confirmButtonText: 'Ya, hapus!',
                                                            cancelButtonText: 'Batal'
                                                        }).then((result) => {
                                                            if (result.isConfirmed) {
                                                                $refs.form{{ $jadwal->id }}.submit();
                                                            }
                                                        })
                                                    " class="text-red-600 hover:text-red-900 ml-2">Hapus</button>
                                                </form>
                                            </div>
                                            </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                            Data jadwal pelajaran belum tersedia.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $semuaJadwal->links() }}
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-admin-layout>