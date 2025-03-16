<div id="search" class="px-5 py-5 mb-10" x-data="{}" x-init="$el.querySelector('input').id = 'searchInput'; 
            window.addEventListener('keydown', (e) => {
                if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                    e.preventDefault();
                    document.getElementById('searchInput').focus();
                }
            })">
    <flux:input id="searchInput" label="{{ __('Search') }}: " wire:keydown.meta.k="search" kbd="⌘K" icon="magnifying-glass"
        wire:model.debounce.500ms.live="needle" />

</div>