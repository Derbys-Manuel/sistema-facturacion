@props([
    'value' => 0,
    'subtitle' => null,
    'prefix' => '',
    'suffix' => '',
    'decimals' => 0,
    'duration' => 280,
])

@php
    $value = is_numeric($value) ? (float) $value : 0;
    $decimals = max(0, (int) $decimals);
    $duration = max(50, (int) $duration);
@endphp

<div
    {{ $attributes->class([
        'rounded-2xl bg-gray-100/50 p-3 shadow-inner transition-shadow',
        'hover:shadow-md',
        'dark:border-white/10 dark:bg-zinc-900',
    ]) }}
    x-data="{
        target: {{ $value }},
        current: 0,
        prefix: @js((string) $prefix),
        suffix: @js((string) $suffix),
        decimals: {{ $decimals }},
        duration: {{ $duration }},
        frame: null,

        init() {
            this.animate(this.target)
        },

        format(n) {
            const value = Number.isFinite(n) ? n : 0
            return value.toLocaleString('es-PE', {
                minimumFractionDigits: this.decimals,
                maximumFractionDigits: this.decimals,
            })
        },

        animate(to) {
            const from = Number.isFinite(this.current) ? this.current : 0
            const target = Number.isFinite(to) ? to : 0

            if (this.frame) {
                cancelAnimationFrame(this.frame)
            }

            const startAt = performance.now()
            const duration = this.duration

            const step = (now) => {
                const t = Math.min(1, (now - startAt) / duration)
                const eased = 1 - Math.pow(1 - t, 3)
                this.current = from + (target - from) * eased
                if (t < 1) {
                    this.frame = requestAnimationFrame(step)
                } else {
                    this.current = target
                    this.frame = null
                }
            }

            this.frame = requestAnimationFrame(step)
        },
    }"
>
    <div class="flex items-start justify-between gap-4">
        <div class="min-w-0">
            <div class="text-2xl font-semibold tabular-nums text-emerald-600">
                <span x-text="prefix + format(current) + suffix"></span>
            </div>
            @if (filled($subtitle))
                <div class="mt-2 flex items-center gap-2 text-[13px] font-semibold tracking-tight text-zinc-600 dark:text-zinc-300">
                    <div class="h-2 w-2 rounded-full bg-emerald-600/90"></div>
                    <div class="truncate">
                        {{ $subtitle }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
