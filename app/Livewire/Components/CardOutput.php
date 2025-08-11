<?php

namespace App\Livewire\Components;

use App\Helpers\CardHelper;
use Livewire\Component;

class CardOutput extends Component
{
    public string $name = 'card_number';
    public string $label = 'Numéro de carte';
    public bool $required = false;
    public ?string $value = null;

    public function updatedValue(): void
    {
        if ($this->value) {
            $this->value = CardHelper::formatCardNumber($this->value);
        }
    }

    public function render()
    {
        return view('livewire.components.card-output');
    }
}
