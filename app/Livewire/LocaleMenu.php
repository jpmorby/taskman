<?php

/**
 *
 * (C) Jon Morby 2025.  All Rights Reserved.
 *
 */

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Blade;

class LocaleMenu extends Component
{
    public string $locale;

    public function __construct()
    {
        // $this->locale = app()->getLocale();
        $this->locale = Session::get('locale', app()->getLocale());

    }
    public function render()
    {
        return view('livewire.locale-menu');
    }

    public function setLocale($cc)
    {
        Log::info("Locale set to: $cc");
        $this->locale = $cc;
        app()->setLocale($cc);

        Session::put('locale', $cc);
        $this->dispatch('locale-changed', $cc);
        return redirect(request()->header('Referer'));

    }
}
