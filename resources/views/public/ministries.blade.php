<x-public-layout>
    <section class="bg-slate-950 py-16 text-white">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <p class="text-sm font-bold uppercase tracking-[0.22em] text-yellow-300">{{ __('messages.ministries') }}</p>
            <h1 class="mt-4 text-4xl font-extrabold">{{ __('messages.public_ministries_title') }}</h1>
            <p class="mt-4 max-w-3xl text-sm leading-7 text-slate-300">{{ __('messages.public_ministries_summary') }}</p>
        </div>
    </section>

    <section class="bg-slate-50 py-12">
        <div class="mx-auto grid max-w-7xl gap-4 px-4 sm:px-6 md:grid-cols-2 lg:grid-cols-3 lg:px-8">
            @foreach ([
                ['title' => __('messages.children_department'), 'body' => __('messages.children_department_body')],
                ['title' => __('messages.women_department'), 'body' => __('messages.women_department_body')],
                ['title' => __('messages.men_department'), 'body' => __('messages.men_department_body')],
                ['title' => __('messages.youth_department'), 'body' => __('messages.youth_department_body')],
                ['title' => __('messages.evangelism_department'), 'body' => __('messages.evangelism_department_body')],
                ['title' => __('messages.prayer_department'), 'body' => __('messages.prayer_department_body')],
            ] as $ministry)
                <article class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-bold text-slate-950">{{ $ministry['title'] }}</h2>
                    <p class="mt-3 text-sm leading-7 text-slate-600">{{ $ministry['body'] }}</p>
                </article>
            @endforeach
        </div>
    </section>
</x-public-layout>
