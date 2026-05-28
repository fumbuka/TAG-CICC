<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('TAG-CICC Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">Membership</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-950">Members</p>
                    <p class="mt-2 text-sm text-gray-600">Registration, zones, and department assignments.</p>
                </div>

                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">Departments</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-950">Idara</p>
                    <p class="mt-2 text-sm text-gray-600">Dynamic departments and leadership positions.</p>
                </div>

                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">Finance</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-950">Sadaka & Zaka</p>
                    <p class="mt-2 text-sm text-gray-600">Offerings, tithes, contributions, kapu, and gunia.</p>
                </div>

                <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                    <p class="text-sm font-medium text-gray-500">Reports</p>
                    <p class="mt-2 text-2xl font-semibold text-gray-950">Calendar</p>
                    <p class="mt-2 text-sm text-gray-600">Annual plan, department reports, and performance.</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
