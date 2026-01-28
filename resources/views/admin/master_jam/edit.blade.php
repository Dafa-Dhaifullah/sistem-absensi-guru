<x-admin-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Jam Pelajaran') }} - <span class="text-blue-600">Hari {{ $hari }}</span>
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <form action="{{ route('admin.master-jam.update', $hari) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <p class="text-gray-600 mb-6">Atur rentang jam mulai dan jam selesai untuk setiap jam pelajaran di hari <strong>{{ $hari }}</strong>. Gunakan format 24 jam (HH:MM).</p>

                        <div class="space-y-4">
                            @if ($errors->any())
                                <div class="mb-4 p-4 bg-red-100 border border-red-300 text-red-800 rounded-md">
                                    <p class="font-bold">Terdapat kesalahan:</p>
                                    <ul class="list-disc list-inside text-sm">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @forelse ($jamPelajaran as $jam)
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-center p-4 border rounded-md hover:bg-gray-50">
                                    <div class="md:col-span-1">
                                        <span class="font-semibold text-lg text-gray-700">Jam ke-{{ $jam->jam_ke }}</span>
                                    </div>
                                    <div class="md:col-span-1">
                                        <x-input-label for="jam_mulai_{{ $jam->jam_ke }}" :value="__('Jam Mulai')" />
                                        
                                        <!-- PERBAIKAN DI SINI: Formatting Carbon H:i -->
                                        <x-text-input 
                                            id="jam_mulai_{{ $jam->jam_ke }}" 
                                            class="block mt-1 w-full" 
                                            type="time" 
                                            name="jam_mulai[{{ $jam->jam_ke }}]" 
                                            :value="old('jam_mulai.' . $jam->jam_ke, \Carbon\Carbon::parse($jam->jam_mulai)->format('H:i'))" 
                                            required 
                                        />
                                    </div>
                                    <div class="md:col-span-1">
                                        <x-input-label for="jam_selesai_{{ $jam->jam_ke }}" :value="__('Jam Selesai')" />
                                        
                                        <!-- PERBAIKAN DI SINI: Formatting Carbon H:i -->
                                        <x-text-input 
                                            id="jam_selesai_{{ $jam->jam_ke }}" 
                                            class="block mt-1 w-full" 
                                            type="time" 
                                            name="jam_selesai[{{ $jam->jam_ke }}]" 
                                            :value="old('jam_selesai.' . $jam->jam_ke, \Carbon\Carbon::parse($jam->jam_selesai)->format('H:i'))" 
                                            required 
                                        />
                                    </div>
                                </div>
                            @empty
                                <p class="text-red-500 text-center py-4">Data master jam pelajaran untuk hari ini tidak ditemukan.</p>
                            @endforelse
                        </div>

                        <div class="flex items-center gap-4 mt-8 pt-4 border-t">
                            <x-primary-button>{{ __('Simpan Perubahan') }}</x-primary-button>
                            <a href="{{ route('admin.master-jam.index') }}" class="text-gray-600 hover:text-gray-900 font-medium">{{ __('Batal') }}</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>