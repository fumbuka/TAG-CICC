<svg width="120" height="120" viewBox="0 0 42 42" role="img" aria-label="{{ $label }} {{ $value }}%">
    <circle cx="21" cy="21" r="15.9155" fill="#fff7ed"></circle>
    <circle cx="21" cy="21" r="15.9155" fill="transparent" stroke="#fee2e2" stroke-width="6"></circle>
    <circle
        cx="21"
        cy="21"
        r="15.9155"
        fill="transparent"
        stroke="{{ $color ?? '#b91c1c' }}"
        stroke-dasharray="{{ $value }} {{ 100 - $value }}"
        stroke-dashoffset="25"
        stroke-linecap="round"
        stroke-width="6"
    ></circle>
    <text x="21" y="21.8" fill="#111827" font-size="8" font-weight="800" text-anchor="middle">{{ $value }}%</text>
</svg>
<div class="donut-label">{{ $label }}</div>
