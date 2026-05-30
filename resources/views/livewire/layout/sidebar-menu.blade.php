<div class="space-y-6">
    @foreach ($navGroups as $groupIndex => $group)
        <section>
            <button
                type="button"
                @click="openGroups['group{{ $groupIndex }}'] = ! openGroups['group{{ $groupIndex }}']"
                class="flex w-full items-center justify-between px-3 text-xs font-bold uppercase tracking-[0.16em] text-gray-500"
            >
                <span>{{ $group['label'] }}</span>
                <svg class="h-4 w-4 transition" :class="{ 'rotate-90': openGroups['group{{ $groupIndex }}'] }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 6 6 6-6 6" />
                </svg>
            </button>

            <div x-show="openGroups['group{{ $groupIndex }}']" x-cloak class="mt-2 space-y-1">
                @foreach ($group['items'] as $item)
                    <a
                        href="{{ $item['href'] }}"
                        @if (! str_contains($item['href'], '#')) wire:navigate @endif
                        @if ($mobile) @click="mobileOpen = false" @endif
                        @class([
                            'group flex items-center gap-3 rounded-md px-3 py-2.5 text-sm font-semibold transition',
                            'bg-red-50 text-red-800 shadow-sm ring-1 ring-red-100' => $item['active'],
                            'text-gray-700 hover:bg-gray-50 hover:text-gray-950' => ! $item['active'],
                        ])
                    >
                        <span @class([
                            'inline-flex h-9 w-9 items-center justify-center rounded-md',
                            'bg-white text-red-700 shadow-sm' => $item['active'],
                            'bg-gray-100 text-gray-500 group-hover:bg-white group-hover:text-red-700' => ! $item['active'],
                        ])>
                            <x-sidebar-icon :name="$item['icon']" class="h-5 w-5" />
                        </span>
                        <span class="min-w-0 flex-1 truncate">{{ $item['label'] }}</span>
                    </a>

                    @if ($item['active'] && count($item['children']) > 0)
                        <div class="ml-8 border-l border-red-100 py-1 pl-4">
                            @foreach ($item['children'] as $child)
                                <a
                                    href="{{ $child['href'] }}"
                                    @if ($mobile) @click="mobileOpen = false" @endif
                                    class="block rounded-md px-3 py-2 text-sm font-medium text-gray-600 transition hover:bg-red-50 hover:text-red-800"
                                >
                                    {{ $child['label'] }}
                                </a>
                            @endforeach
                        </div>
                    @endif
                @endforeach
            </div>
        </section>
    @endforeach
</div>
