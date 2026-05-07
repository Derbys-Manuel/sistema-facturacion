@props([
    'open' => false,
    'url' => null,
    'title' => 'PDF generado',
    'closeAction' => 'closePdfPreview',
    'newAction' => 'startNewInvoice',
    'newLabel' => 'Ingresar nuevo',
    'listAction' => 'goToVouchers',
    'listLabel' => 'Ir a listado',
    'showFooterActions' => true,
    'closedEvent' => 'pdf-modal-closed',
])

@if ($open)
    <div
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
        x-data="{
            leaving: false,
            closing: false,
            
            closeModal() {
                this.$dispatch('{{ $closedEvent }}');
                $wire.{{ $closeAction }}();
            }
        }"
        x-on:click.self="closeModal()"
        x-on:keydown.escape.window="closeModal()"
        :class="leaving ? 'cursor-wait' : ''"
    >
        <div class="w-full max-w-5xl overflow-hidden rounded-2xl border border-black/10 bg-white shadow-xl">
            <div class="flex items-center justify-between border-b border-black/10 px-4 py-3">
                <div class="text-sm font-semibold text-zinc-800">
                    {{ $title }}
                </div>

                <x-form.button
                    variant="ghost"
                    size="sm"
                    type="button"
                    x-on:click="closing = true; closeModal()"
                    x-bind:disabled="closing"
                >
                    <span x-show="!closing" x-cloak>
                        Cerrar
                    </span>

                    <span x-show="closing" x-cloak class="inline-flex items-center gap-2">
                        <flux:icon.loading class="size-4 animate-spin" />
                        <span>Cerrando...</span>
                    </span>
                </x-form.button>
            </div>

            <div class="space-y-2 p-4">
                <div class="overflow-hidden rounded-2xl border border-black/10 bg-white">
                    @if ($url)
                        <div x-data="{ loading: true }" class="relative">
                            <div
                                x-show="loading"
                                x-cloak
                                class="absolute inset-0 flex h-[60vh] items-center justify-center bg-white text-sm text-black/60"
                            >
                                Cargando PDF...
                            </div>

                            <iframe
                                title="pdf-preview"
                                src="{{ $url }}"
                                class="h-[74vh] w-full overflow-auto"
                                @load="loading = false"
                            ></iframe>
                        </div>
                    @else
                        <div class="flex h-[60vh] items-center justify-center text-sm text-black/60">
                            No hay PDF disponible.
                        </div>
                    @endif
                </div>

                @if ($showFooterActions)
                    <div class="flex flex-col gap-3 sm:flex-row">
                        @if (filled($newAction) && filled($newLabel))
                            <x-form.button
                                variant="ghost"
                                type="button"
                                class="flex-1 bg-gray-200"
                                wire:click="{{ $newAction }}"
                            >
                                {{ $newLabel }}
                            </x-form.button>
                        @endif

                        @if (filled($listAction) && filled($listLabel))
                            <x-form.button
                                variant="success"
                                type="button"
                                class="inline-flex min-h-10 flex-1 items-center justify-center gap-2"
                                wire:click="{{ $listAction }}"
                                wire:target="{{ $listAction }}"
                                wire:loading.attr="disabled"
                                x-on:click="leaving = true"
                                x-bind:disabled="leaving"
                            >
                                <span
                                    class="inline-flex items-center justify-center"
                                    x-show="!leaving"
                                    x-cloak
                                >
                                    {{ $listLabel }}
                                </span>

                                <span
                                    class="inline-flex items-center justify-center gap-2"
                                    x-show="leaving"
                                    x-cloak
                                >
                                    <flux:icon.loading class="size-4 animate-spin" />
                                    <span>Espere...</span>
                                </span>
                            </x-form.button>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
@endif