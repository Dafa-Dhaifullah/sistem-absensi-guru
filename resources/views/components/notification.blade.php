@props(['type' => 'success', 'message'])

@php
    $colors = [
        'success' => [
            'bg' => 'bg-green-50', 'border' => 'border-green-500', 'text' => 'text-green-800', 'icon_bg' => 'bg-green-200', 'icon_text' => 'text-green-700',
            'icon' => '<svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>'
        ],
        'error' => [
            'bg' => 'bg-red-50', 'border' => 'border-red-500', 'text' => 'text-red-800', 'icon_bg' => 'bg-red-200', 'icon_text' => 'text-red-700',
            'icon' => '<svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>'
        ],
    ];
    $color = $colors[$type];
@endphp

<div x-data="{ show: true }"
     x-init="setTimeout(() => show = false, 5000)"
     x-show="show"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 transform translate-y-2"
     x-transition:enter-end="opacity-100 transform translate-y-0"
     x-transition:leave="transition ease-in duration-300"
     x-transition:leave-start="opacity-100 transform translate-y-0"
     x-transition:leave-end="opacity-0 transform translate-y-2"
     class="fixed top-20 right-5 z-50 p-4 rounded-lg shadow-lg border {{ $color['bg'] }} {{ $color['border'] }} {{ $color['text'] }} flex items-start gap-4"
     role="alert">
    
    <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center {{ $color['icon_bg'] }} {{ $color['icon_text'] }}">
        {!! $color['icon'] !!}
    </div>
    <div class="flex-grow">
        {{ $message }}
    </div>
    <button @click="show = false" class="opacity-70 hover:opacity-100">&times;</button>
</div>