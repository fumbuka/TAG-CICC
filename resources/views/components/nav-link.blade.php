@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center whitespace-nowrap px-1 pt-1 border-b-2 border-red-600 text-sm font-semibold leading-5 text-gray-950 focus:outline-none focus:border-red-700 transition duration-150 ease-in-out'
            : 'inline-flex items-center whitespace-nowrap px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-900 hover:border-amber-400 focus:outline-none focus:text-gray-900 focus:border-amber-400 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
