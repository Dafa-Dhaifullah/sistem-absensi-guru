<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Log Aktivitas QR Code') }}</h2>
    </x-slot>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <p class="mb-4 text-sm text-gray-600">
                        Halaman ini mencatat setiap QR Code yang digenerate oleh sistem.
                    </p>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Waktu Generate</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dibuat Oleh</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kadaluarsa</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <!-- TAMBAHAN BARU: Header Jumlah Scan -->
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Jumlah Scan</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($logs as $log)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $log->created_at->format('d M Y, H:i:s') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $log->user->name ?? 'Sistem Kios' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ \Carbon\Carbon::parse($log->waktu_kadaluarsa)->format('H:i:s') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                        @php
                                            // Logika status diperbarui untuk menampilkan 'Di-scan'
                                            $status = $log->status;
                                            $color = 'bg-gray-100 text-gray-800'; // Menunggu
                                            
                                            if ($status == 'Di-scan') {
                                                $color = 'bg-green-100 text-green-800';
                                            } elseif (now()->isAfter($log->waktu_kadaluarsa) && $status == 'Menunggu') {
                                                $status = 'Kedaluwarsa';
                                                $color = 'bg-yellow-100 text-yellow-800';
                                            }
                                        @endphp
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $color }}">{{ $status }}</span>
                                    </td>
                                    <!-- TAMBAHAN BARU: Kolom Jumlah Scan -->
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-medium text-gray-900">
                                        {{ $log->jumlah_scan }}
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">Belum ada log QR Code yang tercatat.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">{{ $logs->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
