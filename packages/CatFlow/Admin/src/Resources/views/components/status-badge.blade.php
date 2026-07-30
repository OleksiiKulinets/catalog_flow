@props(['status'])

@php
    $styles = match ($status) {
        'completed' => ['dot' => 'bg-green-500', 'text' => 'text-green-700', 'bg' => 'bg-green-50'],
        'in_progress', 'finalizing' => ['dot' => 'bg-amber-500', 'text' => 'text-amber-700', 'bg' => 'bg-amber-50'],
        'queued', 'uploading' => ['dot' => 'bg-navy-500', 'text' => 'text-navy-700', 'bg' => 'bg-navy-50'],
        'failed', 'expired', 'cancelled', 'cancelling' => ['dot' => 'bg-red-500', 'text' => 'text-red-700', 'bg' => 'bg-red-50'],
        default => ['dot' => 'bg-gray-400', 'text' => 'text-gray-600', 'bg' => 'bg-gray-100'],
    };

    $label = __("batches.status.{$status}");
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium ring-1 ring-inset ring-black/5 {$styles['bg']} {$styles['text']}"]) }}>
    <span class="h-2 w-2 rounded-full {{ $styles['dot'] }}"></span>
    {{ $label }}
</span>
