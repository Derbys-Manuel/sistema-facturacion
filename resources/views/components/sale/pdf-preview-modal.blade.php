@props([
    'open' => false,
    'url' => null,
    'title' => 'PDF generado',
    'closeAction' => 'closePdfPreview',
    'newAction' => 'startNewInvoice',
    'newLabel' => 'Ingresar nuevo',
    'listAction' => 'goToVouchers',
    'listLabel' => 'Ir a listado',
])

@if ($open)
    <div
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
        wire:click.self="{{ $newAction }}"
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
                    wire:click="{{ $newAction }}"
                >
                    Cerrar
                </x-form.button>
            </div>

            <div class="space-y-2 p-4">
                <div class="rounded-2xl border border-black/10 overflow-hidden bg-white">
                    @if ($url)
                        <div x-data="{ loading: true }" class="relative">
                            <div
                                x-show="loading"
                                class="absolute inset-0 flex h-[60vh] items-center justify-center text-sm text-black/60 bg-white"
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

                <div class="flex flex-col sm:flex-row gap-3">
                    <x-form.button
                        variant="ghost"
                        type="button"
                        class="flex-1 bg-gray-200"
                        wire:click="{{ $newAction }}"
                    >
                        {{ $newLabel }}
                    </x-form.button>

                    <x-form.button
                        variant="success"
                        type="button"
                        class="flex-1"
                        wire:click="{{ $listAction }}"
                    >
                        {{ $listLabel }}
                    </x-form.button>
                </div>
            </div>
        </div>
    </div>
@endif

