{{-- resources/views/components/icon-box.blade.php --}}
@props(['color' => 'gray', 'icon' => 'question-mark-circle'])

@php
    $colors = [
        'gray' => 'bg-gray-100 dark:bg-gray-900 text-gray-600 dark:text-gray-400',
        'green' => 'bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-400',
        'blue' => 'bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400',
        'red' => 'bg-red-100 dark:bg-red-900 text-red-600 dark:text-red-400',
        'yellow' => 'bg-yellow-100 dark:bg-yellow-900 text-yellow-600 dark:text-yellow-400',
    ];
@endphp

<div class="w-8 h-8 rounded-full flex items-center justify-center {{ $colors[$color] ?? $colors['gray'] }}">
    <x-lucide-icon name="{{ $icon }}" class="w-4 h-4" />
</div>
