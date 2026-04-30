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
                <flux:modal.trigger name="company-select">
                    <flux:sidebar.item
                        tooltip="Empresas"
                        x-data="{
                            storageKey: 'company-selector',
                            label: null,
                            ruc: null,

                            init() {
                                this.load()

                                window.addEventListener('company-selected', (e) => {
                                    this.label = e?.detail?.label ?? null
                                    this.ruc = e?.detail?.ruc ?? null
                                })

                                window.addEventListener('storage', (e) => {
                                    if (! e) return
                                    if (e.key === this.storageKey || e.key === `${this.storageKey}:label` || e.key === `${this.storageKey}:ruc`) {
                                        this.load()
                                    }
                                })
                            },

                            load() {
                                const label = localStorage.getItem(`${this.storageKey}:label`)
                                const ruc = localStorage.getItem(`${this.storageKey}:ruc`)
                                this.label = label && label.length ? label : null
                                this.ruc = ruc && ruc.length ? ruc : null
                            },

                            initials() {
                                const text = this.label || 'Empresas'
                                return text.trim().slice(0, 1).toUpperCase()
                            },
                        }"
                        class="mt-1 cursor-pointer"
                    >
                        <x-slot:icon>
                            <div class="relative">
                                <div class="flex size-7 items-center justify-center rounded-full bg-emerald-600 text-xs font-semibold text-white shadow-sm">
                                    <span x-text="initials()"></span>
                                </div>
                                <div class="absolute -bottom-0.5 -right-0.5 size-2 rounded-full bg-emerald-400 ring-2 ring-white dark:ring-zinc-900"></div>
                            </div>
                        </x-slot:icon>

                        <div class="min-w-0">
                            <div class="truncate text-sm font-semibold text-zinc-800 dark:text-white" x-text="label || 'Empresas'"></div>
                            <div class="mt-0.5 truncate text-xs text-zinc-500 dark:text-white/60" x-show="label" x-text="ruc || '-'"></div>
                        </div>
                    </flux:sidebar.item>
                </flux:modal.trigger>
            </flux:sidebar.nav>
        </flux:sidebar>
        <!-- Mobile Header -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
            <x-app-logo href="{{ route('dashboard') }}" wire:navigate />
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
