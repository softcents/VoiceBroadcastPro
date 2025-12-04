<?php

namespace App\Livewire\Payments;

use Livewire\Component;

class Cancel extends Component
{
    public function render()
    {
        return view('livewire.payments.cancel', [
            'title' => 'Payment Cancelled',
        ]);
    }
}
