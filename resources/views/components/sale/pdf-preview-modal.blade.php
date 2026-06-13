@props([
    'open' => false,
    'url' => null,
    'statusUrl' => null,
    'title' => 'PDF generado',
    'closeAction' => 'closePdfPreview',
    'newAction' => 'startNewInvoice',
    'newLabel' => 'Ingresar nuevo',
    'listAction' => 'goToVouchers',
    'listLabel' => 'Ir a listado',
    'showFooterActions' => true,
])

@if ($open)
    <div
        x-show="visible"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
        x-data="{
            visible: true,
            leaving: false,
            pdfUrl: null,
            pdfStatus: '{{ filled($statusUrl) ? 'pending' : (filled($url) ? 'ready' : 'failed') }}',
            pdfError: null,
            pollAttempts: 0,
            pollTimer: null,

            init() {
                if (this.pdfStatus === 'ready') {
                    this.pdfUrl = @js($url);
                    return;
                }

                if (@js($statusUrl)) {
                    this.pollPdfStatus();
                }
            },

            async pollPdfStatus() {
                try {
                    const response = await fetch(@js($statusUrl), {
                        headers: { 'Accept': 'application/json' },
                        credentials: 'same-origin',
                    });

                    if (! response.ok) {
                        throw new Error('No se pudo consultar el estado del PDF.');
                    }

                    const data = await response.json();

                    if (data.status === 'ready') {
                        this.pdfStatus = 'ready';
                        this.pdfUrl = data.url;
                        return;
                    }

                    if (data.status === 'failed') {
                        this.pdfStatus = 'failed';
                        this.pdfError = data.message || 'No se pudo generar el PDF.';
                        return;
                    }

                    this.pollAttempts++;
                    if (this.pollAttempts >= 60) {
                        this.pdfStatus = 'failed';
                        this.pdfError = 'La generación del PDF excedió el tiempo de espera.';
                        return;
                    }

                    this.pollTimer = setTimeout(() => this.pollPdfStatus(), 2000);
                } catch (error) {
                    this.pdfStatus = 'failed';
                    this.pdfError = error.message;
                }
            },

            closeModal() {
                clearTimeout(this.pollTimer);
                this.visible = false;
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
                    x-on:click="closeModal()"
                >
                    Cerrar
                </x-form.button>
            </div>

            <div class="space-y-2 p-4">
                <div class="overflow-hidden rounded-2xl border border-black/10 bg-white">
                    @if ($url || $statusUrl)
                        <div x-data="{ loading: true }" class="relative">
                            <div
                                x-show="pdfStatus === 'pending'"
                                x-cloak
                                class="absolute inset-0 flex h-[60vh] items-center justify-center bg-white text-sm text-black/60"
                            >
                                <div class="flex items-center gap-2">
                                    <flux:icon.loading class="size-4 animate-spin" />
                                    <span>Generando PDF...</span>
                                </div>
                            </div>

                            <iframe
                                x-show="pdfStatus === 'ready'"
                                x-cloak
                                title="pdf-preview"
                                x-bind:src="pdfUrl"
                                class="h-[74vh] w-full overflow-auto"
                                @load="loading = false"
                            ></iframe>

                            <div
                                x-show="pdfStatus === 'failed'"
                                x-cloak
                                class="flex h-[60vh] flex-col items-center justify-center gap-2 px-6 text-center text-sm text-red-600"
                            >
                                <flux:icon.exclamation-triangle class="size-6" />
                                <span x-text="pdfError || 'No se pudo generar el PDF.'"></span>
                            </div>
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
