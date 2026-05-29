<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center rounded-md border border-transparent bg-red-700 px-4 py-2 text-xs font-semibold uppercase text-white shadow-sm transition duration-150 ease-in-out hover:bg-red-800 focus:bg-red-800 focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2 active:bg-red-900']) }}>
    {{ $slot }}
</button>
