<div wire.model.live="languages" x-data="">
    <flux:dropdown position="bottom" align="end">
        <flux:button icon-trailing="chevron-down">{{ $locale }}</flux:button>

        <flux:navmenu>
            <x-locale-menu-item locale="en" lang="English" />
            <x-locale-menu-item locale="fr" lang="French" />
            <x-locale-menu-item locale="es" lang="Spanish" />
            <x-locale-menu-item locale="ru" lang="Russian" />
            <x-locale-menu-item locale="de" lang="German" />
            <x-locale-menu-item locale="it" lang="Italian" />
            <x-locale-menu-item locale="pt" lang="Portuguese" />
            {{--
            <x-locale-menu-item locale="ar" lang="Arabic" />
            <x-locale-menu-item locale="za" lang="Afrikaans" />
            --}}
        </flux:navmenu>
    </flux:dropdown>
</div>