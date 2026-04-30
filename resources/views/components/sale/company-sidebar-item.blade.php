@props([
    'modalName' => 'company-select',
    'storageKey' => 'company-selector',
    'tooltip' => 'Empresas',
])

<flux:modal.trigger :name="$modalName">
    <flux:sidebar.item
        :tooltip="$tooltip"
        x-data="{
            storageKey: '{{ addslashes($storageKey) }}',
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
        {{ $attributes->class('mt-1 cursor-pointer') }}
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
