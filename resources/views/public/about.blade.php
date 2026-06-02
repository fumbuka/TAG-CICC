<x-public-layout>
    <section class="bg-slate-950 py-16 text-white">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <p class="text-sm font-bold uppercase tracking-[0.22em] text-yellow-300">{{ __('messages.about_church') }}</p>
            <h1 class="mt-4 text-4xl font-extrabold">{{ __('messages.public_about_title') }}</h1>
            <p class="mt-4 max-w-3xl text-sm leading-7 text-slate-300">{{ __('messages.public_about_summary') }}</p>
        </div>
    </section>

    <section class="bg-white py-12">
        <div class="mx-auto grid max-w-7xl gap-8 px-4 sm:px-6 lg:grid-cols-[0.9fr_1.1fr] lg:px-8">
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-6">
                <img src="{{ asset('images/tag-cicc-logo.png') }}" alt="TAG-CICC" class="mx-auto h-44 w-44 object-contain">
                <h2 class="mt-6 text-2xl font-extrabold text-slate-950">{{ __('messages.local_church_name') }}</h2>
                <p class="mt-3 text-sm leading-7 text-slate-600">{{ __('messages.public_about_details') }}</p>
            </div>

            <div class="grid gap-4">
                @foreach ([
                    ['title' => __('messages.our_mission'), 'body' => __('messages.our_mission_body')],
                    ['title' => __('messages.our_vision'), 'body' => __('messages.our_vision_body')],
                    ['title' => __('messages.our_values'), 'body' => __('messages.our_values_body')],
                ] as $item)
                    <article class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                        <h3 class="text-xl font-bold text-slate-950">{{ $item['title'] }}</h3>
                        <p class="mt-3 text-sm leading-7 text-slate-600">{{ $item['body'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
</x-public-layout>
