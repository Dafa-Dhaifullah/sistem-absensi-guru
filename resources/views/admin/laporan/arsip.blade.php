<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Arsip Logbook Piket') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    <p class="mb-4 text-gray-600">
                        Daftar catatan harian (logbook) yang diisi oleh Guru Piket.
                         <a href="{{ route('admin.laporan.export.arsip') }}">
            <x-secondary-button>
                {{ __('Export ke Excel') }}
            </x-secondary-button>
        </a>
                    </p>
                   

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kejadian Penting</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tindak Lanjut</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($logbook as $log)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ \Carbon\Carbon::parse($log->tanggal)->isoFormat('dddd, D MMMM YYYY') }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900" style="white-space: pre-wrap;">{{ $log->kejadian_penting ?? '-' }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-900" style="white-space: pre-wrap;">{{ $log->tindak_lanjut ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                            Belum ada catatan logbook.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-4">
                        {{ $logbook->links() }}
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-admin-layout>