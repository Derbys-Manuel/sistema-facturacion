@props([
    'label' => null,
    'hint' => null,
    'error' => null,
    'iconLeft' => 'calendar-days',
    'placeholder' => 'Selecciona fecha y hora...',
    'disabled' => false,
    'wrapperClass' => null,
])

@php
    $wireModel = $attributes->wire('model')->value();
@endphp

<div
    @class(['relative w-full', $wrapperClass])
    x-data="{
        open: false,
        value: @entangle($wireModel),

        date: null,
        time: null,
        cursor: null,
        monthLabel: '',
        days: [],
        dropdownStyle: '',

        init() {
            this.syncValue();

            if (! this.date) {
                this.date = this.nowLimaDateString();
            }

            if (! this.time) {
                this.time = this.nowLimaTime();
            }

            const start = this.parseLocalDate(this.date);

            this.cursor = this.formatDate(new Date(start.getFullYear(), start.getMonth(), 1));
            this.value = this.formatDateTime();

            this.buildCalendar();

            this.$watch('value', () => {
                this.syncValue();

                if (! this.date) {
                    this.date = this.nowLimaDateString();
                }

                if (! this.time) {
                    this.time = this.nowLimaTime();
                }

                const start = this.parseLocalDate(this.date);

                if (start) {
                    this.cursor = this.formatDate(new Date(start.getFullYear(), start.getMonth(), 1));
                    this.buildCalendar();
                }
            });
        },

        syncValue() {
            if (! this.value || typeof this.value !== 'string') {
                this.date = null;
                this.time = null;
                return;
            }

            const clean = this.value.replace('T', ' ');
            const parts = clean.split(' ');

            this.date = parts[0] || null;
            this.time = parts[1] ? parts[1].substring(0, 5) : null;
        },

        nowLimaDateString() {
            return new Intl.DateTimeFormat('en-CA', {
                timeZone: 'America/Lima',
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
            }).format(new Date());
        },

        nowLimaTime() {
            return new Intl.DateTimeFormat('en-GB', {
                timeZone: 'America/Lima',
                hour: '2-digit',
                minute: '2-digit',
                hour12: false,
            }).format(new Date());
        },

        parseLocalDate(value) {
            if (! value || typeof value !== 'string') return null;

            const date = value.substring(0, 10);
            const parts = date.split('-').map(Number);

            if (parts.length !== 3) return null;

            const [y, m, d] = parts;

            if (! y || ! m || ! d) return null;

            return new Date(y, m - 1, d);
        },

        formatDate(dateObj) {
            const y = dateObj.getFullYear();
            const m = String(dateObj.getMonth() + 1).padStart(2, '0');
            const d = String(dateObj.getDate()).padStart(2, '0');

            return `${y}-${m}-${d}`;
        },

        formatDateTime() {
            if (! this.date) return null;

            const time = this.time || this.nowLimaTime();

            return `${this.date} ${time}:00`;
        },

        displayValue() {
            if (! this.date) return '';

            return `${this.date} ${this.time || this.nowLimaTime()}`;
        },

        openPicker() {
            if (@js($disabled)) return;

            this.syncValue();

            if (! this.date) {
                this.date = this.nowLimaDateString();
            }

            if (! this.time) {
                this.time = this.nowLimaTime();
            }

            const start = this.parseLocalDate(this.date);

            this.cursor = this.formatDate(new Date(start.getFullYear(), start.getMonth(), 1));

            this.open = true;

            this.$nextTick(() => {
                this.buildCalendar();
                this.updateDropdownPosition();
            });
        },

        closePicker() {
            this.open = false;
        },

        updateDropdownPosition() {
            if (! this.$refs.trigger) return;

            const rect = this.$refs.trigger.getBoundingClientRect();

            const gap = 8;
            const dropdownHeight = 390;
            const viewportHeight = window.innerHeight;
            const viewportWidth = window.innerWidth;

            const spaceBelow = viewportHeight - rect.bottom;
            const spaceAbove = rect.top;

            const openUp = spaceBelow < dropdownHeight && spaceAbove > spaceBelow;

            const maxHeight = openUp
                ? Math.max(220, spaceAbove - gap * 2)
                : Math.max(220, spaceBelow - gap * 2);

            const top = openUp
                ? Math.max(gap, rect.top - Math.min(dropdownHeight, maxHeight) - gap)
                : rect.bottom + gap;

            const left = Math.min(
                Math.max(gap, rect.left),
                viewportWidth - rect.width - gap
            );

            this.dropdownStyle = `
                top: ${top}px;
                left: ${left}px;
                width: ${rect.width}px;
                max-height: ${maxHeight}px;
                overflow-y: auto;
            `;
        },

        prevMonth() {
            const d = this.parseLocalDate(this.cursor) ?? this.parseLocalDate(this.nowLimaDateString());

            d.setMonth(d.getMonth() - 1);

            this.cursor = this.formatDate(new Date(d.getFullYear(), d.getMonth(), 1));
            this.buildCalendar();
        },

        nextMonth() {
            const d = this.parseLocalDate(this.cursor) ?? this.parseLocalDate(this.nowLimaDateString());

            d.setMonth(d.getMonth() + 1);

            this.cursor = this.formatDate(new Date(d.getFullYear(), d.getMonth(), 1));
            this.buildCalendar();
        },

        buildCalendar() {
            const first = this.parseLocalDate(this.cursor) ?? this.parseLocalDate(this.nowLimaDateString());

            const year = first.getFullYear();
            const month = first.getMonth();

            const label = first.toLocaleDateString('es-PE', {
                month: 'long',
                year: 'numeric',
            });

            this.monthLabel = label.charAt(0).toUpperCase() + label.slice(1);

            const firstDow = new Date(year, month, 1).getDay();
            const startOffset = (firstDow + 6) % 7;
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const prevMonthDays = new Date(year, month, 0).getDate();

            const grid = [];

            for (let i = 0; i < startOffset; i++) {
                const day = prevMonthDays - (startOffset - 1 - i);
                grid.push(this.makeDay(new Date(year, month - 1, day), true));
            }

            for (let day = 1; day <= daysInMonth; day++) {
                grid.push(this.makeDay(new Date(year, month, day), false));
            }

            while (grid.length % 7 !== 0) {
                const last = grid[grid.length - 1].dateObj;

                grid.push(this.makeDay(new Date(
                    last.getFullYear(),
                    last.getMonth(),
                    last.getDate() + 1
                ), true));
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

        isSame(a, b) {
            return a && b && a === b;
        },

        choose(date) {
            this.date = date;

            if (! this.time) {
                this.time = this.nowLimaTime();
            }

            this.value = this.formatDateTime();

            this.$nextTick(() => this.updateDropdownPosition());
        },

        updateTime() {
            if (! this.date) {
                this.date = this.nowLimaDateString();
            }

            this.value = this.formatDateTime();
        },

        today() {
            this.date = this.nowLimaDateString();
            this.time = this.nowLimaTime();
            this.value = this.formatDateTime();

            const start = this.parseLocalDate(this.date);

            this.cursor = this.formatDate(new Date(start.getFullYear(), start.getMonth(), 1));
            this.buildCalendar();

            this.$nextTick(() => this.updateDropdownPosition());
        },

        clear() {
            this.value = null;
            this.date = null;
            this.time = null;
            this.closePicker();
        },
    }"
    x-on:keydown.escape.window="open = false"
    x-on:resize.window.throttle.50ms="open && updateDropdownPosition()"
    x-on:scroll.window.throttle.50ms="open && updateDropdownPosition()"
    x-on:wheel.window.throttle.50ms="open && updateDropdownPosition()"
>
    @if ($label)
        <flux:label class="mb-3 text-gray-700 text-sm">
            {{ $label }}
        </flux:label>
    @endif

    <div
        x-ref="trigger"
        class="group relative flex h-10 w-full items-center overflow-hidden rounded-sm border bg-white shadow-sm transition
        {{ $error
            ? 'border-red-400 ring-2 ring-red-100'
            : 'border-zinc-200 hover:border-zinc-300 focus-within:border-emerald-400 focus-within:ring-2 focus-within:ring-emerald-100'
        }}
        {{ $disabled ? 'bg-zinc-100 opacity-80' : '' }}"
    >
        <button
            type="button"
            class="flex h-full w-full items-center gap-2 px-3 text-left text-sm disabled:cursor-not-allowed"
            x-on:click="openPicker()"
            @disabled($disabled)
        >
            @if ($iconLeft)
                <flux:icon :name="$iconLeft" class="size-4 shrink-0 text-zinc-400" />
            @endif

            <span
                class="flex-1 truncate"
                x-bind:class="displayValue() ? 'text-zinc-900' : 'text-zinc-400'"
                x-text="displayValue() || '{{ $placeholder }}'"
            ></span>
        </button>

        <button
            type="button"
            class="mr-2 inline-flex size-7 items-center justify-center rounded-full text-zinc-400 hover:bg-zinc-50 hover:text-zinc-600"
            x-show="value && !@js($disabled)"
            x-cloak
            x-on:click="clear()"
        >
            <flux:icon name="x-mark" variant="micro" class="size-4" />
        </button>
    </div>

    <template x-teleport="body">
        <div
            x-show="open"
            x-cloak
            x-transition.opacity.scale.origin.top.duration.150ms
            x-on:click.outside="closePicker()"
            class="fixed z-[999999] rounded-md border border-zinc-200 bg-white shadow-xl scrollbar-thin-stable"
            x-bind:style="dropdownStyle"
        >
            <div class="sticky top-0 z-10 flex items-center justify-between gap-2 border-b border-zinc-100 bg-white px-3 py-2">
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
                            day.muted
                                ? 'text-zinc-300'
                                : 'text-zinc-700 hover:bg-emerald-50 hover:text-emerald-800',

                            isSame(day.date, date)
                                ? 'bg-emerald-600 text-white hover:bg-emerald-600 hover:text-white'
                                : '',
                        ].join(' ')"
                    >
                        <span x-text="day.day"></span>
                    </button>
                </template>
            </div>

            <div class="sticky bottom-0 border-t border-zinc-100 bg-white px-3 py-3">
                <div class="flex items-center gap-2">
                    <div class="flex h-9 flex-1 items-center rounded-sm border border-zinc-200 bg-zinc-50 px-3">
                        <flux:icon name="clock" class="size-4 shrink-0 text-zinc-400" />

                        <input
                            type="time"
                            x-model="time"
                            x-on:change="updateTime()"
                            class="h-full w-full border-0 bg-transparent px-2 text-sm text-zinc-900 outline-none ring-0 focus:ring-0 focus:outline-none"
                        />
                    </div>

                    <button
                        type="button"
                        class="inline-flex h-9 items-center justify-center rounded-sm bg-zinc-100 px-3 text-xs font-medium text-zinc-700 hover:bg-zinc-200"
                        x-on:click="today()"
                    >
                        Ahora
                    </button>

                    <button
                        type="button"
                        class="inline-flex h-9 items-center justify-center rounded-sm bg-emerald-600 px-3 text-xs font-medium text-white hover:bg-emerald-700"
                        x-on:click="closePicker()"
                    >
                        Aplicar
                    </button>
                </div>
            </div>
        </div>
    </template>

    @if ($hint && ! $error)
        <flux:description class="mt-1.5">
            {{ $hint }}
        </flux:description>
    @endif

    @if ($error)
        <flux:error class="mt-1.5">
            {{ $error }}
        </flux:error>
    @endif
</div>