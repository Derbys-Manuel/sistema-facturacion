<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>

<div class="min-h-screen w-full bg-slate-50 text-slate-900">
    <div class="flex min-h-screen flex-col">
        <header class="sticky top-0 z-10 border-b border-slate-200 bg-white/80 backdrop-blur ">
            <div class="px-6 py-4">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 class="text-lg font-semibold tracking-tight">Ventas</h1>
                        <p class="text-sm text-slate-500">Listado de documentos de venta</p>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <div class="inline-flex items-center gap-2 rounded-full bg-amber-50 px-3 py-1 text-xs font-medium text-amber-800 ring-1 ring-inset ring-amber-200">
                        <span class="h-2 w-2 rounded-full bg-amber-500" aria-hidden="true"></span>
                        Pendiente
                        <span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-amber-900">S/ 0.00</span>
                    </div>

                    <div class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-800 ring-1 ring-inset ring-emerald-200">
                        <span class="h-2 w-2 rounded-full bg-emerald-500" aria-hidden="true"></span>
                        Pagado
                        <span class="ml-1 rounded-full bg-emerald-100 px-2 py-0.5 text-emerald-900">S/ 0.00</span>
                    </div>

                    <div class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700 ring-1 ring-inset ring-slate-200">
                        <span class="h-2 w-2 rounded-full bg-slate-400" aria-hidden="true"></span>
                        Total
                        <span class="ml-1 rounded-full bg-white px-2 py-0.5 text-slate-900">S/ 0.00</span>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-hidden">
            <div class="h-full overflow-y-auto px-6 py-6">
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex flex-col gap-3 border-b border-slate-200 p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-2 w-2 rounded-full bg-slate-400"></span>
                            <p class="text-sm font-medium text-slate-900">Últimas ventas</p>
                        </div>
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                            <div class="grid grid-cols-2 gap-2 sm:flex sm:items-center">
                                <flux:modal.trigger name="sale-create">
                                    <flux:button>Nueva venta</flux:button>
                                </flux:modal.trigger>
                            </div>
                            <button type="button" class="h-9 rounded-lg border border-slate-200 bg-white px-3 text-sm text-slate-700 shadow-sm hover:bg-slate-50">Filtros</button>
                        </div>
                        
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    <th scope="col" class="px-4 py-3">Documento</th>
                                    <th scope="col" class="px-4 py-3">Cliente</th>
                                    <th scope="col" class="px-4 py-3">Emisión</th>
                                    <th scope="col" class="px-4 py-3">Vence</th>
                                    <th scope="col" class="px-4 py-3">Forma</th>
                                    <th scope="col" class="px-4 py-3">Estado</th>
                                    <th scope="col" class="px-4 py-3 text-right">Total</th>
                                    <th scope="col" class="px-4 py-3">SUNAT</th>
                                    <th scope="col" class="px-4 py-3 text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200">
                                <tr class="hover:bg-slate-50/70">
                                    <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-slate-900">F001-00001234</td>
                                    <td class="px-4 py-3 text-sm text-slate-700">
                                        <div class="flex flex-col">
                                            <span class="font-medium text-slate-900">Juan Pérez</span>
                                            <span class="text-xs text-slate-500">DNI 12345678</span>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">24/04/2026</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">24/05/2026</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">Crédito</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm">
                                        <span class="inline-flex items-center gap-2 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-800 ring-1 ring-inset ring-amber-200">
                                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500" aria-hidden="true"></span>
                                            Pendiente
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-slate-900">S/ 1,250.00</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm">
                                        <span class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700 ring-1 ring-inset ring-slate-200">
                                            <span class="h-1.5 w-1.5 rounded-full bg-slate-400" aria-hidden="true"></span>
                                            Por enviar
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                        <div class="inline-flex items-center gap-2">
                                            <button type="button" class="h-8 rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50">Ver</button>
                                            <button type="button" class="h-8 rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50">Imprimir</button>
                                        </div>
                                    </td>
                                </tr>

                                <tr class="hover:bg-slate-50/70">
                                    <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-slate-900">B001-00000077</td>
                                    <td class="px-4 py-3 text-sm text-slate-700">
                                        <div class="flex flex-col">
                                            <span class="font-medium text-slate-900">María López</span>
                                            <span class="text-xs text-slate-500">RUC 20123456789</span>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">24/04/2026</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">24/04/2026</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">Contado</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm">
                                        <span class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-800 ring-1 ring-inset ring-emerald-200">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500" aria-hidden="true"></span>
                                            Pagado
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-slate-900">S/ 180.00</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm">
                                        <span class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-800 ring-1 ring-inset ring-emerald-200">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500" aria-hidden="true"></span>
                                            Enviado
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                        <div class="inline-flex items-center gap-2">
                                            <button type="button" class="h-8 rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50">Ver</button>
                                            <button type="button" class="h-8 rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50">Imprimir</button>
                                        </div>
                                    </td>
                                </tr>

                                <tr class="hover:bg-slate-50/70">
                                    <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-slate-900">F001-00001235</td>
                                    <td class="px-4 py-3 text-sm text-slate-700">
                                        <div class="flex flex-col">
                                            <span class="font-medium text-slate-900">Comercial Andina SAC</span>
                                            <span class="text-xs text-slate-500">RUC 20555555555</span>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">23/04/2026</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">30/04/2026</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">Crédito</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm">
                                        <span class="inline-flex items-center gap-2 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-800 ring-1 ring-inset ring-amber-200">
                                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500" aria-hidden="true"></span>
                                            Pendiente
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-slate-900">S/ 3,420.50</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm">
                                        <span class="inline-flex items-center gap-2 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700 ring-1 ring-inset ring-slate-200">
                                            <span class="h-1.5 w-1.5 rounded-full bg-slate-400" aria-hidden="true"></span>
                                            Observado
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                        <div class="inline-flex items-center gap-2">
                                            <button type="button" class="h-8 rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50">Ver</button>
                                            <button type="button" class="h-8 rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50">Imprimir</button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <flux:modal 
        name="sale-create"     
        style="width: 1400px; height: 90vh; max-width: none; overflow: hidden;"
        scroll="body" :dismissible="false">
        <livewire:sale.create />
    </flux:modal>
</div>
