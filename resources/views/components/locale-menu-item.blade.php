<flux:navmenu.item wire:click="setLocale('{{ $locale }}')">{{ __($lang) }}
    @if(App::getLocale() === $locale)
        <svg class="w-5 h-5 text-green-500 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
            stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
    </svg> @endif
</flux:navmenu.item>