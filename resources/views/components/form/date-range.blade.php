@props([
    'label' => null,
    'fromModel' => 'from',
    'toModel' => 'to',
    'disabled' => false,
    'error' => null,
    'wrapperClass' => null,
])

<div
    @class(['relative w-full', $wrapperClass])
    x-data="{
        open: false,
        fromModel: @js($fromModel),
        toModel: @js($toModel),
        disabled: @js($disabled),

        cursor: null, // YYYY-MM-01
        monthLabel: '',
        days: [],

        from: null,
        to: null,

        parseLocalDate(value) {
            if (! value || typeof value !== 'string') return null;
            const parts = value.split('-').map((v) => Number(v));
            if (parts.length !== 3) return null;
            const [y, m, d] = parts;
            if (! y || ! m || ! d) return null;
            return new Date(y, m - 1, d);
        },

        init() {
            this.syncFromWire();

            const today = new Date();
            const start = this.from ? (this.parseLocalDate(this.from) ?? today) : today;
            this.cursor = this.formatDate(new Date(start.getFullYear(), start.getMonth(), 1));
            this.buildCalendar();

            if ($wire && typeof $wire.$watch === 'function') {
                $wire.$watch(this.fromModel, (value) => {
                    this.from = value || null;
                });

                $wire.$watch(this.toModel, (value) => {
                    this.to = value || null;
                });
            }
        },

        syncFromWire() {
            if (! $wire || typeof $wire.get !== 'function') return;
            this.from = $wire.get(this.fromModel) || null;
            this.to = $wire.get(this.toModel) || null;
        },

        openPicker() {
            if (this.disabled) return;
            this.syncFromWire();
            const start = this.from ? this.parseLocalDate(this.from) : null;
            if (start) {
                this.cursor = this.formatDate(new Date(start.getFullYear(), start.getMonth(), 1));
            }
            this.open = true;
            this.$nextTick(() => this.buildCalendar());
        },

        closePicker() {
            this.open = false;
        },

        prevMonth() {
            const d = this.parseLocalDate(this.cursor) ?? new Date();
            d.setMonth(d.getMonth() - 1);
            this.cursor = this.formatDate(new Date(d.getFullYear(), d.getMonth(), 1));
            this.buildCalendar();
        },

        nextMonth() {
            const d = this.parseLocalDate(this.cursor) ?? new Date();
            d.setMonth(d.getMonth() + 1);
            this.cursor = this.formatDate(new Date(d.getFullYear(), d.getMonth(), 1));
            this.buildCalendar();
        },

        buildCalendar() {
            const first = this.parseLocalDate(this.cursor) ?? new Date();
            const year = first.getFullYear();
            const month = first.getMonth();

            const label = first.toLocaleDateString('es-PE', { month: 'long', year: 'numeric' });
            this.monthLabel = label.charAt(0).toUpperCase() + label.slice(1);

            const firstDow = new Date(year, month, 1).getDay(); // 0 Sun
            const startOffset = (firstDow + 6) % 7; // Monday=0
            const daysInMonth = new Date(year, month + 1, 0).getDate();

            const prevMonthDays = new Date(year, month, 0).getDate();

            const grid = [];

            for (let i = 0; i < startOffset; i++) {
                const day = prevMonthDays - (startOffset - 1 - i);
                const date = new Date(year, month - 1, day);
                grid.push(this.makeDay(date, true));
            }

            for (let day = 1; day <= daysInMonth; day++) {
                const date = new Date(year, month, day);
                grid.push(this.makeDay(date, false));
            }

            while (grid.length % 7 !== 0) {
                const last = grid[grid.length - 1].dateObj;
                const date = new Date(last.getFullYear(), last.getMonth(), last.getDate() + 1);
                grid.push(this.makeDay(date, true));
            }

            this.days = grid;
        },

        makeDay(dateObj, muted) {
            return {
                dateObj,
                date: this.formatDate(dateObj),
                day: dateObj.getDate(),
                muted,
            };
        },

        formatDate(dateObj) {
            const y = dateObj.getFullYear();
            const m = String(dateObj.getMonth() + 1).padStart(2, '0');
            const d = String(dateObj.getDate()).padStart(2, '0');
            return `${y}-${m}-${d}`;
        },

        isSame(a, b) {
            return a && b && a === b;
        },

        isBetween(date) {
            if (! this.from || ! this.to) return false;
            return date > this.from && date < this.to;
        },

        choose(date) {
            if (this.disabled) return;

            this.syncFromWire();

            if (! this.from || (this.from && this.to)) {
                this.from = date;
                this.to = null;
                $wire.set(this.fromModel, this.from, true);
                $wire.set(this.toModel, null, true);
                return;
            }

            if (date < this.from) {
                this.to = this.from;
                this.from = date;
            } else {
                this.to = date;
            }

            $wire.set(this.fromModel, this.from, true);
            $wire.set(this.toModel, this.to, true);
            this.closePicker();
        },

        clear() {
            this.from = null;
            this.to = null;
            $wire.set(this.fromModel, null, true);
            $wire.set(this.toModel, null, true);
        },

        displayValue() {
            const from = this.from;
            const to = this.to;
            if (! from && ! to) return '';
            if (from && ! to) return from;
            return `${from} → ${to}`;
        },
    }"
    x-on:keydown.escape.window="open = false"
>
    @if ($label)
        <flux:label class="mb-1.5">
            {{ $label }}
        </flux:label>
    @endif

    <div
        class="group relative flex h-10 w-full items-center overflow-hidden rounded-sm border bg-white shadow-sm transition
        {{ $error
            ? 'border-red-400 ring-2 ring-red-100'
            : 'border-zinc-200 hover:border-zinc-300 focus-within:border-emerald-400 focus-within:ring-2 focus-within:ring-emerald-100'
        }}
        {{ $disabled ? 'bg-zinc-100 opacity-80' : '' }}"
    >
        <button
            type="button"
            class="flex h-full w-full items-center gap-2 px-3 text-left text-sm text-zinc-900 disabled:cursor-not-allowed"
            x-on:click="openPicker()"
            @disabled($disabled)
        >
            <flux:icon name="calendar-days" class="size-4 shrink-0 text-zinc-400" />
            <span
                class="flex-1 truncate"
                x-bind:class="displayValue() ? 'text-zinc-900' : 'text-zinc-400'"
                x-text="displayValue() || 'Selecciona un rango...'"
            ></span>
        </button>

        <button
            type="button"
            class="mr-2 inline-flex size-7 items-center justify-center rounded-full text-zinc-400 hover:bg-zinc-50 hover:text-zinc-600"
            x-show="(from || to) && !disabled"
            x-cloak
            x-on:click="clear()"
        >
            <flux:icon name="x-mark" variant="micro" class="size-4" />
        </button>
    </div>

    <div
        x-show="open"
        x-cloak
        x-transition.opacity.scale.origin.top.duration.150ms
        x-on:click.outside="closePicker()"
        class="absolute left-0 right-0 z-[100000] mt-2 overflow-hidden rounded-md border border-zinc-200 bg-white shadow-xl"
    >
        <div class="flex items-center justify-between gap-2 border-b border-zinc-100 px-3 py-2">
            <button
                type="button"
                class="inline-flex size-8 items-center justify-center rounded-md text-zinc-500 hover:bg-zinc-50 hover:text-zinc-700"
                x-on:click="prevMonth()"
            >
                <flux:icon name="chevron-left" variant="micro" class="size-4" />
            </button>

            <div class="text-sm font-semibold text-zinc-800" x-text="monthLabel"></div>

            <button
                type="button"
                class="inline-flex size-8 items-center justify-center rounded-md text-zinc-500 hover:bg-zinc-50 hover:text-zinc-700"
                x-on:click="nextMonth()"
            >
                <flux:icon name="chevron-right" variant="micro" class="size-4" />
            </button>
        </div>

        <div class="grid grid-cols-7 gap-1 px-3 pt-3 text-center text-[11px] font-semibold text-zinc-500">
            <div>L</div>
            <div>M</div>
            <div>M</div>
            <div>J</div>
            <div>V</div>
            <div>S</div>
            <div>D</div>
        </div>

        <div class="grid grid-cols-7 gap-1 p-3">
            <template x-for="day in days" :key="day.date">
                <button
                    type="button"
                    class="relative inline-flex h-9 items-center justify-center rounded-md text-sm transition"
                    x-on:click="choose(day.date)"
                    x-bind:class="[
                        day.muted ? 'text-zinc-300' : 'text-zinc-700 hover:bg-emerald-50 hover:text-emerald-800',
                        isBetween(day.date) ? 'bg-emerald-50 text-emerald-800' : '',
                        isSame(day.date, from) || isSame(day.date, to) ? 'bg-emerald-600 text-white hover:bg-emerald-600 hover:text-white' : '',
                    ].join(' ')"
                    x-bind:aria-label="day.date"
                >
                    <span x-text="day.day"></span>
                </button>
            </template>
        </div>
    </div>
</div>
