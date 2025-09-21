<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Manajemen Akun Piket') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    <div class="mb-4">
                        <a href="{{ route('admin.akun-piket.create') }}">
                            <x-primary-button>{{ __('Tambah Akun Piket') }}</x-primary-button>
                        </a>
                    </div>

                    @if (session('success'))
                        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-md">
                            {{ session('success') }}
                        </div>
                    @endif

                    <div class="overflow-x-auto">
                         <table class="min-w-full divide-y divide-gray-200">
    <thead class="bg-gray-50">
        <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th> <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
        </tr>
    </thead>
    <tbody class="bg-white divide-y divide-gray-200">
        @forelse ($semuaPiket as $piket)
            <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $loop->iteration + ($semuaPiket->currentPage() - 1) * $semuaPiket->perPage() }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $piket->name }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">{{ $piket->username }}</td> <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $piket->email ?? '-' }}</td> <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <a href="{{ route('admin.akun-piket.edit', $piket->id) }}" class="text-indigo-600 hover:text-indigo-900">Edit</a>

                    @if(Auth::id() !== $piket->id)
                    <form action="{{ route('admin.akun-piket.destroy', $piket->id) }}" method="POST" class="inline" onsubmit="return confirm('Yakin hapus?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:text-red-900 ml-2">Hapus</button>
                    </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">Data belum tersedia.</td> </tr>
        @endforelse
    </tbody>
</table>
                    </div>
                    <div class="mt-4">{{ $semuaPiket->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>