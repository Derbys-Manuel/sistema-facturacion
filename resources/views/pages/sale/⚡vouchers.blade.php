<?php

use Livewire\Component;

new class extends Component
{
    //
};
?>

@php
    $records = [
        [
            'id' => 'VCH-0001',
            'type' => 'Boleta',
            'serie' => 'B001',
            'correlative' => '00000012',
            'client' => 'Consumidor final',
            'total' => 28.50,
            'status' => 'Aprobado',
            'issued_at' => '2026-04-28 10:14',
        ],
        [
            'id' => 'VCH-0002',
            'type' => 'Factura',
            'serie' => 'F001',
            'correlative' => '00000003',
            'client' => 'ACME S.A.C. (20600000001)',
            'total' => 118.00,
            'status' => 'Rechazado',
            'issued_at' => '2026-04-28 12:02',
        ],
        [
            'id' => 'VCH-0003',
            'type' => 'Boleta',
            'serie' => 'B001',
            'correlative' => '00000013',
            'client' => 'Juan Pérez (12345678)',
            'total' => 9.90,
            'status' => 'Borrador',
            'issued_at' => '2026-04-29 09:40',
        ],
    ];
@endphp

<div
    class="p-6"
    x-data="{
        query: '',
        status: 'all',
        type: 'all',
        selectedId: null,
        rows: @js($records),

        get filtered() {
            const q = this.query.trim().toLowerCase();

            return this.rows.filter((row) => {
                const matchesQuery = !q
                    || String(row.serie).toLowerCase().includes(q)
                    || String(row.correlative).toLowerCase().includes(q)
                    || String(row.client).toLowerCase().includes(q);

                const matchesStatus = this.status === 'all'
                    || String(row.status).toLowerCase() === String(this.status).toLowerCase();

                const matchesType = this.type === 'all'
                    || String(row.type).toLowerCase() === String(this.type).toLowerCase();

                return matchesQuery && matchesStatus && matchesType;
            });
        },

        badgeClass(status) {
            const s = String(status).toLowerCase();
            if (s === 'aprobado') return 'bg-emerald-50 text-emerald-700 ring-emerald-600/20';
            if (s === 'rechazado') return 'bg-red-50 text-red-700 ring-red-600/20';
            if (s === 'borrador') return 'bg-zinc-50 text-zinc-700 ring-zinc-600/20';
            return 'bg-zinc-50 text-zinc-700 ring-zinc-600/20';
        },
    }"
>
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-sm font-semibold uppercase tracking-wide text-zinc-800">
                Comprobantes
            </h1>
            <p class="mt-1 text-xs text-zinc-500">
                Lista local (demo) con filtros instantáneos.
            </p>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <flux:input
                x-model="query"
                placeholder="Buscar por cliente, serie o correlativo..."
                class="h-10 w-full sm:w-80"
            />

            <div class="flex gap-2">
                <select
                    x-model="type"
                    class="h-10 rounded-md border border-zinc-200 bg-white px-3 text-sm text-zinc-700 shadow-sm"
                >
                    <option value="all">Todos</option>
                    <option value="Boleta">Boleta</option>
                    <option value="Factura">Factura</option>
                </select>

                <select
                    x-model="status"
                    class="h-10 rounded-md border border-zinc-200 bg-white px-3 text-sm text-zinc-700 shadow-sm"
                >
                    <option value="all">Estado</option>
                    <option value="Aprobado">Aprobado</option>
                    <option value="Rechazado">Rechazado</option>
                    <option value="Borrador">Borrador</option>
                </select>
            </div>
        </div>
    </div>

    <x-ui.table
        :columns="['Tipo', 'Serie', 'Correlativo', 'Cliente', 'Fecha', 'Total', 'Estado', 'Acciones']"
        striped
        selectable
        x-on:ui-table-selected="selectedId = $event.detail.id"
    >
        <template x-for="row in filtered" :key="row.id">
            <tr
                class="transition-colors odd:bg-zinc-50/60 hover:bg-zinc-50 cursor-pointer"
                x-bind:data-row-id="row.id"
                x-bind:class="selected === row.id ? 'bg-emerald-50' : ''"
            >
                <x-ui.table.cell x-text="row.type" />
                <x-ui.table.cell class="font-mono text-xs" x-text="row.serie" />
                <x-ui.table.cell class="font-mono text-xs" x-text="row.correlative" />
                <x-ui.table.cell class="max-w-[28ch] truncate" x-text="row.client" />
                <x-ui.table.cell class="text-xs text-zinc-500" x-text="row.issued_at" />
                <x-ui.table.cell class="tabular-nums font-semibold" x-text="`S/ ${Number(row.total).toFixed(2)}`" />
                <x-ui.table.cell>
                    <span
                        class="inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold ring-1 ring-inset"
                        x-bind:class="badgeClass(row.status)"
                        x-text="row.status"
                    ></span>
                </x-ui.table.cell>
                <x-ui.table.cell>
                    <div class="flex items-center justify-end gap-2">
                        <button
                            type="button"
                            class="rounded-md border border-zinc-200 bg-white px-2 py-1 text-xs font-semibold text-zinc-700 shadow-sm hover:bg-zinc-50"
                            x-on:click.stop="selectedId = row.id"
                        >
                            Ver
                        </button>

                        <button
                            type="button"
                            class="rounded-md border border-zinc-200 bg-white px-2 py-1 text-xs font-semibold text-zinc-700 shadow-sm hover:bg-zinc-50"
                            x-on:click.stop="alert(`Acción demo para ${row.serie}-${row.correlative}`)"
                        >
                            Acción
                        </button>
                    </div>
                </x-ui.table.cell>
            </tr>
        </template>

        <template x-if="filtered.length === 0">
            <tr>
                <td colspan="8" class="px-4 py-8 text-center text-sm text-zinc-500">
                    Sin resultados.
                </td>
            </tr>
        </template>
    </x-ui.table>

    <div class="mt-4 rounded-lg border border-zinc-200 bg-white p-4 text-sm text-zinc-700">
        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">
                    Selección
                </p>
                <p class="mt-1 text-sm font-semibold text-zinc-800" x-text="selectedId ?? 'Ninguno'"></p>
            </div>

            <button
                type="button"
                class="rounded-md border border-zinc-200 bg-white px-3 py-2 text-xs font-semibold text-zinc-700 shadow-sm hover:bg-zinc-50"
                x-on:click="selectedId = null"
            >
                Limpiar
            </button>
        </div>
    </div>
</div>
