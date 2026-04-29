<?php

use Livewire\Component;
use App\Models\Company;

new class extends Component
{
    public string $storageName = 'company-selector';

    public array $companies = [];

    public function mount(): void
    {
        $this->loadCompanies();
    }

    public function loadCompanies(): void
    {
        $this->companies = Company::query()
            ->select('id', 'company_name', 'ruc')
            ->get()
            ->map(fn ($company) => [
                'id' => $company->id,
                'label' => $company->company_name,
                'description' => $company->ruc,
                'icon' => 'building-office',
            ])
            ->toArray();
    }
};
?>

<div
    x-data="{
        selected: null,
        storageKey: @js($storageName),

        init() {
            this.selected = localStorage.getItem(this.storageKey)
        },

        select(id) {
            this.selected = id
            localStorage.setItem(this.storageKey, id)

            window.dispatchEvent(new CustomEvent('company-selected', {
                detail: { id }
            }))
        },

        isSelected(id) {
            return this.selected === id
        }
    }"
    class="w-full"
>
    <div
        class="grid gap-3"
        style="grid-template-columns: repeat({{ max(count($companies),1) }}, minmax(0,1fr));"
    >
        @foreach ($companies as $company)
            <button
                type="button"
                x-on:click="select({{ Js::from($company['id']) }})"
                class="group relative min-h-28 overflow-hidden rounded-xl border p-4 text-left transition-all duration-300 ease-out hover:-translate-y-1 hover:scale-[1.02] active:scale-[0.98]"
                x-bind:class="isSelected({{ Js::from($company['id']) }}) ? 'border-zinc-900 bg-zinc-900 text-white shadow-xl' : 'border-zinc-200 bg-white text-zinc-700 shadow-sm hover:border-zinc-300 hover:bg-zinc-50 hover:shadow-md'"
            >
                <div
                    class="absolute inset-0 opacity-0 transition-opacity duration-300 group-hover:opacity-100"
                    x-bind:class="isSelected({{ Js::from($company['id']) }}) ? 'bg-white/5' : 'bg-zinc-100/70'"
                ></div>

                <div class="relative flex h-full flex-col justify-between gap-4">
                    <div class="flex items-center justify-between">
                        <div
                            class="flex size-9 shrink-0 items-center justify-center rounded-lg transition-all duration-300"
                            x-bind:class="isSelected({{ Js::from($company['id']) }}) ? 'bg-white text-zinc-900 scale-110 rotate-3' : 'bg-zinc-100 text-zinc-500 group-hover:bg-zinc-200 group-hover:scale-110'"
                        >
                            <flux:icon :name="$company['icon']" class="size-4" />
                        </div>

                        <span
                            class="flex size-5 items-center justify-center rounded-full border transition-all duration-300"
                            x-bind:class="isSelected({{ Js::from($company['id']) }}) ? 'border-white bg-white text-zinc-900 opacity-100 scale-100' : 'border-zinc-300 opacity-40 group-hover:opacity-100'"
                        >
                            <flux:icon
                                name="check"
                                class="size-3"
                                x-show="isSelected({{ Js::from($company['id']) }})"
                            />
                        </span>
                    </div>

                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate text-xs font-semibold">
                                {{ $company['label'] }}
                            </p>

                            <p
                                class="mt-1 truncate text-xs transition-colors duration-300"
                                x-bind:class="isSelected({{ Js::from($company['id']) }}) ? 'text-zinc-300' : 'text-zinc-400 group-hover:text-zinc-500'"
                            >
                                {{ $company['description'] }}
                            </p>
                        </div>
                    </div>
                </div>
            </button>
        @endforeach
    </div>
</div>