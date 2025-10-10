<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Manajemen Jadwal Piket Mingguan') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    <p class="mb-4 text-gray-600">
                        Daftar tim guru piket yang bertugas. Klik "Edit" pada setiap slot untuk mengubah guru.
                    </p>

                    <div class="overflow-x-auto border rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hari</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Piket Pagi</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Piket Siang</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                
                                @foreach ($hari as $h)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 w-1/5">{{ $h }}</td>
                                    
                                    @foreach ($sesi as $s)
                                    <td class="px-6 py-4 text-sm text-gray-900 w-2/5">
                                        <a href="{{ route('admin.jadwal-piket.edit', ['hari' => $h, 'sesi' => $s]) }}" 
                                           class="float-right text-xs text-indigo-600 hover:text-indigo-900 font-medium">
                                           [Edit]
                                        </a>

                                        <div class="flex flex-wrap gap-2">
                                            @php
                                                // Ambil data guru untuk slot ini, misal: 'Senin' -> 'Pagi'
                                                $daftarPiket = $jadwalTersimpan->get($h, collect())->get($s, []);
                                            @endphp
                                            
                                            @forelse ($daftarPiket as $piket)
                                                <span class="px-3 py-1 text-xs font-semibold text-gray-800 bg-gray-100 rounded-full">
                                                    {{ $piket->user->name ?? 'Error' }}
                                                </span>
                                            @empty
                                                <span class="text-xs text-gray-400 italic">-- Belum ada guru --</span>
                                            @endforelse
                                        </div>
                                    </td>
                                    @endforeach
                                
                                </tr>
                                @endforeach

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>