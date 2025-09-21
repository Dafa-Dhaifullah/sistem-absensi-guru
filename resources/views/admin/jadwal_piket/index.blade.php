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
                        Atur guru yang akan bertugas piket untuk setiap sesi Pagi dan Siang. Jadwal ini akan otomatis berulang setiap minggu.
                    </p>

                    @if (session('success'))
                        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-md">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-md">
                            <div class="font-medium">Whoops! Ada yang salah.</div>
                            <ul class="mt-3 list-disc list-inside text-sm">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('admin.jadwal-piket.update') }}" method="POST">
                        @csrf
                        
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
                                    
                                    @foreach ($hari as $h) <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $h }}</td>
                                        
                                        @foreach ($sesi as $s) <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            
                                            @php
                                                $key = $h . '_' . $s; 
                                                $selected_user_id = $jadwalTersimpan[$key]->user_id ?? null;
                                            @endphp

                                            <select name="jadwal[{{ $h }}][{{ $s }}]" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                                                <option value="">-- Pilih Guru --</option>
                                                
                                                @foreach ($daftarGuruPiket as $guru)
                                                    <option value="{{ $guru->id }}" 
                                                        @if($guru->id == $selected_user_id) selected @endif>
                                                        {{ $guru->name }}
                                                    </option>
                                                @endforeach

                                            </select>
                                        </td>
                                        @endforeach
                                    
                                    </tr>
                                    @endforeach

                                    </tbody>
                            </table>
                        </div>

                        <div class="flex items-center justify-end mt-6">
                            <x-primary-button type="submit">
                                {{ __('Simpan Jadwal Piket') }}
                            </x-primary-button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</x-admin-layout>