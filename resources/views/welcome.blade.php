<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />

        <title>{{ config('app.name', 'Norapay') }} &mdash; {{ __('Bina laman produk. Terima tempahan.') }}</title>

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
                        <flux:button href="{{ route('dashboard') }}" wire:navigate variant="primary" size="sm">
                            {{ __('Papan pemuka') }}
                        </flux:button>
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

        <main>
            {{-- Hero --}}
            <section class="border-b border-zinc-200 bg-zinc-50">
                <div class="mx-auto grid max-w-6xl items-center gap-12 px-6 py-16 sm:py-24 lg:grid-cols-2 lg:gap-8">
                    <div>
                        <span class="inline-flex items-center gap-1.5 rounded-full border border-zinc-200 bg-white px-3 py-1 text-xs font-medium tracking-wide text-zinc-600">
                            <span class="size-1.5 rounded-full bg-emerald-500"></span>
                            {{ __('Untuk penjual COD & dropship di Malaysia') }}
                        </span>

                        <h1 class="mt-5 text-4xl font-semibold tracking-tight text-balance text-zinc-900 sm:text-5xl">
                            {{ __('Bina laman produk.') }}<br>
                            {{ __('Terima tempahan.') }}
                            <span class="text-zinc-400">{{ __('Tiada kod diperlukan.') }}</span>
                        </h1>

                        <p class="mt-5 max-w-lg text-lg text-pretty text-zinc-600">
                            {{ __('Cipta laman produk dalam minit, kongsi pautan di WhatsApp, Instagram atau TikTok, dan urus setiap tempahan dari satu papan pemuka.') }}
                        </p>

                        <div class="mt-8 flex flex-wrap items-center gap-3">
                            @if (Route::has('register'))
                                <flux:button href="{{ route('register') }}" wire:navigate variant="primary">
                                    {{ __('Mula percuma') }}
                                </flux:button>
                            @endif

                            <flux:button href="#macam-mana" variant="ghost">
                                {{ __('Macam mana ia berfungsi') }}
                            </flux:button>
                        </div>
                    </div>

                    {{-- Signature: a mocked-up order slip, torn at the seam --}}
                    <div class="mx-auto w-full max-w-sm">
                        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-lg shadow-zinc-900/5">
                            <div class="flex items-center justify-between">
                                <span class="font-mono text-xs font-medium tracking-widest text-zinc-400 uppercase">{{ config('app.name', 'Norapay') }} &middot; {{ __('Contoh') }}</span>
                                <span class="size-2 rounded-full bg-emerald-500"></span>
                            </div>

                            <div class="mt-4 flex gap-3">
                                <div class="flex size-16 shrink-0 items-center justify-center rounded-lg bg-zinc-50">
                                    <flux:icon name="shopping-bag" class="size-6 text-zinc-300" />
                                </div>

                                <div class="min-w-0">
                                    <p class="truncate font-medium text-zinc-900">{{ __('Kasut Sukan Ultra') }}</p>
                                    <p class="text-sm text-zinc-500">{{ __('Saiz 40 · Hitam') }}</p>
                                    <p class="mt-1 inline-flex items-baseline gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-emerald-800">
                                        <span class="font-mono text-xs">RM</span>
                                        <span class="text-sm font-semibold">89.00</span>
                                    </p>
                                </div>
                            </div>

                            <div class="ticket-seam mt-5 pt-5 [--seam-bg:var(--color-white)]">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="font-mono text-[11px] tracking-widest text-zinc-400 uppercase">{{ __('No. Tempahan') }}</p>
                                        <p class="font-mono text-sm font-semibold text-zinc-900">#NP-1042</p>
                                    </div>

                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">
                                        <flux:icon name="check" class="size-3.5" />
                                        {{ __('Diterima') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- How it works --}}
            <section id="macam-mana" class="mx-auto max-w-6xl px-6 py-16 sm:py-24">
                <div class="max-w-xl">
                    <span class="font-mono text-xs font-medium tracking-widest text-zinc-400 uppercase">{{ __('Macam mana ia berfungsi') }}</span>
                    <h2 class="mt-2 text-3xl font-semibold tracking-tight text-zinc-900">{{ __('Dari produk kepada tempahan, tiga langkah.') }}</h2>
                </div>

                <div class="mt-12 grid gap-10 sm:grid-cols-3 sm:gap-8">
                    @foreach ([
                        ['label' => __('Cipta produk'), 'text' => __('Masukkan nama, harga, gambar dan penerangan ringkas. Laman produk anda siap dalam beberapa minit.')],
                        ['label' => __('Kongsi pautan'), 'text' => __('Hantar pautan laman produk terus kepada pelanggan melalui WhatsApp, Instagram atau TikTok.')],
                        ['label' => __('Terima tempahan'), 'text' => __('Pelanggan isi borang ringkas. Setiap tempahan masuk terus ke papan pemuka anda.')],
                    ] as $index => $step)
                        <div class="relative pl-12 sm:pl-0">
                            <span class="absolute top-0 left-0 flex size-8 items-center justify-center rounded-full bg-zinc-900 font-mono text-sm text-white sm:static sm:mb-4">
                                {{ $index + 1 }}
                            </span>

                            <flux:heading size="lg">{{ $step['label'] }}</flux:heading>
                            <flux:text class="mt-1.5">{{ $step['text'] }}</flux:text>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Features --}}
            <section class="border-y border-zinc-200 bg-zinc-50">
                <div class="mx-auto max-w-6xl px-6 py-16 sm:py-24">
                    <div class="max-w-xl">
                        <span class="font-mono text-xs font-medium tracking-widest text-zinc-400 uppercase">{{ __('Kenapa Norapay') }}</span>
                        <h2 class="mt-2 text-3xl font-semibold tracking-tight text-zinc-900">{{ __('Dibina untuk jualan satu-produk, bukan kedai penuh.') }}</h2>
                    </div>

                    <div class="mt-12 grid gap-4 sm:grid-cols-2">
                        @foreach ([
                            ['icon' => 'bolt', 'title' => __('Laman siap dalam minit'), 'text' => __('Tiada perlu bina laman web penuh. Setiap produk dapat laman tersendiri yang terus boleh dikongsi.')],
                            ['icon' => 'device-phone-mobile', 'title' => __('Dioptimumkan untuk mobile'), 'text' => __('Kebanyakan trafik COD datang dari WhatsApp dan media sosial — laman anda kemas di skrin kecil.')],
                            ['icon' => 'clipboard-document-list', 'title' => __('Urus tempahan di satu tempat'), 'text' => __('Setiap tempahan masuk terus ke papan pemuka, tiada perlu semak mesej yang berselerak.')],
                            ['icon' => 'shield-check', 'title' => __('Borang ringkas, tiada geseran'), 'text' => __('Pelanggan hanya isi nama, telefon dan alamat — lebih ramai yang selesaikan tempahan.')],
                        ] as $feature)
                            <div class="rounded-xl border border-zinc-200 bg-white p-6">
                                <span class="flex size-10 items-center justify-center rounded-lg bg-zinc-900 text-white">
                                    <flux:icon :name="$feature['icon']" class="size-5" />
                                </span>

                                <flux:heading size="lg" class="mt-4">{{ $feature['title'] }}</flux:heading>
                                <flux:text class="mt-1.5">{{ $feature['text'] }}</flux:text>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            {{-- Final CTA --}}
            <section class="mx-auto max-w-6xl px-6 py-16 sm:py-24">
                <div class="flex flex-col items-center gap-6 rounded-2xl bg-zinc-900 px-8 py-14 text-center">
                    <flux:heading size="xl" class="text-white">{{ __('Sedia nak mula jual?') }}</flux:heading>
                    <flux:text class="max-w-md text-zinc-300">{{ __('Cipta laman produk pertama anda hari ini — percuma untuk mula.') }}</flux:text>

                    @if (Route::has('register'))
                        <flux:button href="{{ route('register') }}" wire:navigate variant="primary" class="bg-white! text-zinc-900! hover:bg-zinc-100!">
                            {{ __('Mula percuma') }}
                        </flux:button>
                    @endif
                </div>
            </section>
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

        @fluxScripts
    </body>
</html>
