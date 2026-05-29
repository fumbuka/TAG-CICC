<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <div class="mb-8">
        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-red-700">{{ __('messages.secure_login') }}</p>
        <h2 class="mt-2 text-2xl font-semibold text-gray-950">{{ __('messages.welcome_back') }}</h2>
        <p class="mt-2 text-sm text-gray-600">{{ __('messages.login_help') }}</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="login" class="space-y-5">
        <!-- Email or Phone -->
        <div>
            <x-input-label for="login" :value="__('messages.email_or_phone')" />
            <x-text-input wire:model="form.login" id="login" class="mt-1 block w-full" type="text" name="login" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('form.login')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('messages.password')" />

            <x-text-input wire:model="form.password" id="password" class="mt-1 block w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('form.password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block">
            <label for="remember" class="inline-flex items-center">
                <input wire:model="form.remember" id="remember" type="checkbox" class="rounded border-gray-300 text-red-700 shadow-sm focus:ring-red-600" name="remember">
                <span class="ms-2 text-sm text-gray-600">{{ __('messages.remember_me') }}</span>
            </label>
        </div>

        <div class="flex items-center justify-between gap-3">
            @if (Route::has('password.request'))
                <a class="rounded-md text-sm font-medium text-gray-600 underline decoration-amber-400 underline-offset-4 hover:text-gray-950 focus:outline-none focus:ring-2 focus:ring-red-600 focus:ring-offset-2" href="{{ route('password.request') }}" wire:navigate>
                    {{ __('messages.forgot_password') }}
                </a>
            @endif

            <x-primary-button class="px-6">
                {{ __('messages.login') }}
            </x-primary-button>
        </div>
    </form>
</div>
