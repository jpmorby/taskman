<?php

/**
 * (C) Jon Morby 2025.  All Rights Reserved.
 */

namespace App\Livewire;

use App\Facades\Currency;
use Livewire\Component;

/**
 * This component is used to set the display currency.
 * It is used in the header of the application, alongside the locale menu.
 */
/**
 * @property string $currency
 */
class CurrencyMenu extends Component
{
    public string $currency;

    public function __construct()
    {
        $this->currency = Currency::current();
    }

    public function render()
    {
        return view('livewire.currency-menu', [
            'currencies' => Currency::supported(),
        ]);
    }

    /*
         * This method is used to set the display currency, overriding the
         * currency we would otherwise guess from the locale.
         *
         * @param string $code
         * @return \Illuminate\Http\RedirectResponse
         */
    public function setCurrency($code)
    {
        if (! Currency::isSupported($code)) {
            return null;
        }

        $this->currency = Currency::normalise($code);
        Currency::store($this->currency);
        $this->dispatch('currency-changed', $this->currency);

        return redirect(request()->header('Referer'));
    }
}
