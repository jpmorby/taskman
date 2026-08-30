<div x-data="">
    <flux:dropdown position="bottom" align="end">
        <flux:button icon-trailing="chevron-down" :aria-label="__('Currency')">{{ $currency }}</flux:button>

        <flux:navmenu>
            @foreach ($currencies as $code => $definition)
                <x-currency-menu-item
                    :code="$code"
                    :name="$definition['name']"
                    :symbol="$definition['symbol']"
                    :current="$currency"
                />
            @endforeach
        </flux:navmenu>
    </flux:dropdown>
</div>
