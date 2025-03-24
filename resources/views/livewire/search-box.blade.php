<div id="search" x-data="{}" x-init="$el.querySelector('input').id = 'searchInput'; 
            window.addEventListener('keydown', (e) => {
                if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                    e.preventDefault();
                    document.getElementById('searchInput').focus();
                }
            })">
    <flux:input id="searchInput" label="{{ __('Search') }}: " icon="magnifying-glass" kbd="⌘K"
        wire:model.debounce.500ms.live="needle" />
{{-- wire:keydown.meta.k="search" --}}
</div>