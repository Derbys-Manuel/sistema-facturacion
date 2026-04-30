<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky collapsible="true" class="border-e border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <a
                    href="{{ route('vouchers') }}"
                    wire:navigate
                    class="group flex h-10 items-center gap-2 px-2 text-zinc-800 dark:text-zinc-100
                        in-data-flux-sidebar-collapsed-desktop:w-10 in-data-flux-sidebar-collapsed-desktop:px-0 in-data-flux-sidebar-collapsed-desktop:justify-center"
                >
                    <div class="flex items-center justify-center h-7 min-w-7 rounded-md bg-emerald-600 text-white shadow-sm">
                        <flux:icon name="arrow-trending-up" variant="micro" class="size-5" />
                    </div>

                    <span class="truncate text-base font-bold tracking-tight text-zinc-800 dark:text-zinc-100 in-data-flux-sidebar-collapsed-desktop:hidden">
                        Facturador SUNAT
                    </span>
                </a>
                <flux:sidebar.collapse class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
            </flux:sidebar.header>
            <flux:sidebar.nav>
                <div class="px-3 py-2 text-xs font-semibold uppercase tracking-wide text-zinc-400 in-data-flux-sidebar-collapsed-desktop:hidden">
                    {{ __('Menu') }}
                </div>

                <flux:sidebar.item icon="document-duplicate" :href="route('vouchers')" :current="request()->routeIs('vouchers')" wire:navigate>
                    {{ __('Comprobantes') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="banknotes" :href="route('create-boleta')" :current="request()->routeIs('create-boleta')" wire:navigate>
                    {{ __('Boleta') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="banknotes" :href="route('create-factura')" :current="request()->routeIs('create-factura')" wire:navigate>
                    {{ __('Factura') }}
                </flux:sidebar.item>
            </flux:sidebar.nav>
            <flux:spacer />
            <flux:sidebar.nav>
                <x-sale.company-sidebar-item />
            </flux:sidebar.nav>
        </flux:sidebar>
        <!-- Mobile Header -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
            <x-app-logo href="{{ route('vouchers') }}" wire:navigate />
        </flux:header>
        <flux:modal
            name="company-select"
            class="max-w-lg bg-gray-100 p-10"
            scroll="body"
            :dismissible="false"
        >
            <livewire:company-select />
        </flux:modal>
        {{ $slot }}
        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
