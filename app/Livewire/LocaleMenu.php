<?php

/**
 *
 * (C) Jon Morby 2025.  All Rights Reserved.
 *
 */

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\App;

/**
 * This component is used to set the application locale.
 * It is used in the header of the application.
 */
/**
 * @property string $locale
 */
class LocaleMenu extends Component
{
    public string $locale;

    public function __construct()
    {
        $this->locale = Session::get('locale', App::getLocale());
    }
    public function render()
    {
        return view('livewire.locale-menu');
    }
/*
     * This method is used to set the application locale.
     *
     * @param string $cc
     * @return \Illuminate\Http\RedirectResponse
     */
    public function setLocale($cc)
    {
        $this->locale = $cc;
        App::setLocale($cc);
        Session::put('locale', $cc);
        $this->dispatch('locale-changed', $cc);
        return redirect(request()->header('Referer'));
    }
}