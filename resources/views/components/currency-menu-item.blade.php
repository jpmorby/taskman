@props(['code', 'name', 'symbol' => '', 'current' => null])

<flux:navmenu.item wire:click="setCurrency('{{ $code }}')">{{ $symbol }} {{ __($name) }}
    @if ($current === $code)
        <svg class="w-5 h-5 text-green-500 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
            stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
    </svg> @endif
</flux:navmenu.item>
