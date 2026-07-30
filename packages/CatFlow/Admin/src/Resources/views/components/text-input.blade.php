@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'bg-white border-gray-300 text-gray-900 placeholder-gray-400 focus:border-navy-500 focus:ring-navy-500 rounded-lg shadow-sm text-sm']) }}>
