<?php

namespace App\View\Components\Form;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Button extends Component
{
    public function __construct(
        public string $variant = 'primary',
        public string $size = 'md',
        public bool $loading = false,
        public bool $fullWidth = false,
        public bool $disabled = false,
        public string $type = 'button',
        public ?string $leftIcon = null,
        public ?string $rightIcon = null,
    ) {}

    public function render(): View|Closure|string
    {
        return view('components.form.button');
    }
}