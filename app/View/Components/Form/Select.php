<?php

namespace App\View\Components\Form;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Select extends Component
{
    public function __construct(
        public ?string $label = null,
        public string $type = 'backend',
        public string $placeholder = 'Seleccionar...',
        public string $searchPlaceholder = 'Buscar...',
        public array $options = [],
        public ?string $selectedLabel = null,
        public ?string $iconLeft = null,
        public ?string $hint = null,
        public ?string $error = null,
        public bool $disabled = false,
        public bool $clearable = false,

        public ?string $searchAction = null,
        public ?string $selectAction = null,
        public ?string $clearAction = null,

        public ?string $optionActionLabel = null,
        public ?string $optionActionIcon = null,
        public ?string $optionAction = null,

        public bool $clearAfterSelect = false,
        public int $selectFeedbackMs = 350,
    ) {}

    public function render(): View|Closure|string
    {
        return view('components.form.select');
    }
}