<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />

        <title>
            {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Norapay') : config('app.name', 'Norapay') }}
        </title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-white text-zinc-900 antialiased">
        <header class="sticky top-0 z-30 border-b border-zinc-200 bg-white/90 backdrop-blur">
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-6">
                <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-2.5">
                    <span class="flex size-8 items-center justify-center rounded-md bg-zinc-900 text-white">
                        <x-app-logo-icon class="size-4.5 fill-current" />
                    </span>
                    <span class="text-lg font-semibold tracking-tight">{{ config('app.name', 'Norapay') }}</span>
                </a>

                <nav class="flex items-center gap-4">
                    <a href="{{ route('products.index') }}" wire:navigate class="hidden text-sm font-medium text-zinc-600 hover:text-zinc-900 sm:inline-block">
                        {{ __('Produk') }}
                    </a>

                    @auth
                        @if (auth()->user()->is_admin)
                            <flux:button href="{{ route('dashboard') }}" wire:navigate variant="primary" size="sm">
                                {{ __('Papan pemuka') }}
                            </flux:button>
                        @else
                            <flux:button href="{{ route('profile.edit') }}" wire:navigate variant="primary" size="sm">
                                {{ __('Akaun saya') }}
                            </flux:button>
                        @endif
                    @else
                        @if (Route::has('login'))
                            <flux:button href="{{ route('login') }}" wire:navigate variant="ghost" size="sm" class="hidden sm:inline-flex">
                                {{ __('Log masuk') }}
                            </flux:button>
                        @endif

                        @if (Route::has('register'))
                            <flux:button href="{{ route('register') }}" wire:navigate variant="primary" size="sm">
                                {{ __('Mula percuma') }}
                            </flux:button>
                        @endif
                    @endauth
                </nav>
            </div>
        </header>

        <main class="mx-auto w-full max-w-7xl p-6 lg:p-8">
            {{ $slot }}
        </main>

        <footer class="border-t border-zinc-200 py-10">
            <div class="mx-auto flex max-w-6xl flex-col items-center gap-4 px-6 text-center sm:flex-row sm:justify-between sm:text-start">
                <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-2">
                    <span class="flex size-6 items-center justify-center rounded-md bg-zinc-900 text-white">
                        <x-app-logo-icon class="size-3.5 fill-current" />
                    </span>
                    <span class="text-sm font-semibold tracking-tight">{{ config('app.name', 'Norapay') }}</span>
                </a>

                <flux:text size="sm">&copy; {{ now()->year }} {{ config('app.name', 'Norapay') }}. {{ __('Hak cipta terpelihara.') }}</flux:text>
            </div>
        </footer>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
