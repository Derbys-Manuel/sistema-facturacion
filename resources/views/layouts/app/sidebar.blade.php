<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky collapsible="true" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('vouchers') }}" wire:navigate />
                <flux:sidebar.collapse class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2" />
            </flux:sidebar.header>
            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Menu')" class="grid">
                    <flux:sidebar.item icon="layout-grid" :href="route('vouchers')" :current="request()->routeIs('vouchers')" wire:navigate>
                        {{ __('Comprobantes') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="clipboard-document-list" :href="route('create-boleta')" :current="request()->routeIs('create-boleta')" wire:navigate>
                        {{ __('Boleta') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="clipboard-document-list" :href="route('create-factura')" :current="request()->routeIs('create-factura')" wire:navigate>
                        {{ __('Factura') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>
            <flux:spacer />
            <flux:sidebar.nav>
                <flux:modal.trigger name="company-select">
                    <flux:sidebar.item icon="folder-git-2" >
                        {{ __('Empresas') }}
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
