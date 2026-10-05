<div class="form-group">
    <div class="col-md-6 col-md-offset-4 text-center">
        @if (Route::has('login.socialite') && \App\Livewire\Auth\Login::enabledProviders() !== [])
            <flux:table>
                <flux:table.rows>
                    <flux:table.row>
                        @foreach (\App\Livewire\Auth\Login::enabledProviders() as $provider)
                            <flux:table.cell>
                                <flux:button href="{{ route('login.socialite', $provider) }}" icon="{{ $provider }}" variant="outline" class="btn btn-{{ $provider }}">
                                    {{ Str::title($provider) }}
                                </flux:button>
                            </flux:table.cell>
                        @endforeach
                    </flux:table.row>
                </flux:table.rows>
            </flux:table>
        @endif
    </div>
</div>
