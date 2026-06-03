@props(['name' => 'circle'])

@switch($name)
    @case('dashboard')
        <svg {{ $attributes }} fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 5a1 1 0 0 1 1-1h5v6H4V5Zm10-1h5a1 1 0 0 1 1 1v3h-6V4ZM4 14h6v6H5a1 1 0 0 1-1-1v-5Zm10-2h6v7a1 1 0 0 1-1 1h-5v-8Z" />
        </svg>
        @break

    @case('people')
        <svg {{ $attributes }} fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 19c0-2.21-1.79-4-4-4s-4 1.79-4 4M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm6.5 7c0-1.66-1.11-3.06-2.63-3.5M17 6.5a3 3 0 0 1 0 5.83M5.5 19c0-1.66 1.11-3.06 2.63-3.5M7 6.5a3 3 0 0 0 0 5.83" />
        </svg>
        @break

    @case('visitors')
        <svg {{ $attributes }} fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-6 8c.7-3.05 3.1-5 6-5 1.2 0 2.3.33 3.23.93M18 14v6m3-3h-6" />
        </svg>
        @break

    @case('departments')
        <svg {{ $attributes }} fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 7h16M4 12h16M4 17h16M7 4v16m10-16v16" />
        </svg>
        @break

    @case('zones')
        <svg {{ $attributes }} fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 21s7-5.2 7-11a7 7 0 1 0-14 0c0 5.8 7 11 7 11Zm0-8a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" />
        </svg>
        @break

    @case('services')
        <svg {{ $attributes }} fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 4h10v16H7V4Zm2.5 4h5M9.5 12h5M9.5 16h3" />
        </svg>
        @break

    @case('finance')
        <svg {{ $attributes }} fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 8h16v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8Zm0 0 2-4h12l2 4M8 13h4m-4 3h8" />
        </svg>
        @break

    @case('sms')
        <svg {{ $attributes }} fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 6h14a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H9l-4 3v-3H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2Zm3 4h8M8 13h5" />
        </svg>
        @break

    @case('calendar')
        <svg {{ $attributes }} fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 3v4m10-4v4M4 9h16M6 5h12a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z" />
        </svg>
        @break

    @case('reports')
        <svg {{ $attributes }} fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 3h7l5 5v13H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Zm7 0v5h5M8 14h8M8 18h5" />
        </svg>
        @break

    @case('leadership')
        <svg {{ $attributes }} fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3 5 7v6c0 4.1 2.7 6.9 7 8 4.3-1.1 7-3.9 7-8V7l-7-4Zm0 5v5l3 2" />
        </svg>
        @break

    @case('users')
        <svg {{ $attributes }} fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-6 8c.7-3.05 3.1-5 6-5s5.3 1.95 6 5M18 8h3m-1.5-1.5v3" />
        </svg>
        @break

    @default
        <svg {{ $attributes }} fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 5v14m7-7H5" />
        </svg>
@endswitch
