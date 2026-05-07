<?php

namespace App\Livewire\Settings;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Spatie\LaravelPasskeys\Actions\GeneratePasskeyRegisterOptionsAction;
use Spatie\LaravelPasskeys\Actions\StorePasskeyAction;

class Passkeys extends Component
{
    public Collection $passkeys;

    public string $newPasskeyName = '';

    public ?int $confirmingDeleteId = null;

    public function mount(): void
    {
        $this->loadPasskeys();
    }

    public function startRegistration(): void
    {
        $optionsJson = app(GeneratePasskeyRegisterOptionsAction::class)->execute(auth()->user());
        session()->put('passkey_register_options', $optionsJson);
        $this->dispatch('passkey-register', options: $optionsJson);
    }

    public function confirmPasskey(array $credential): void
    {
        $optionsJson = session()->pull('passkey_register_options');

        if (! $optionsJson) {
            $this->addError('passkeys', __('Registration session expired. Please try again.'));

            return;
        }

        app(StorePasskeyAction::class)->execute(
            auth()->user(),
            json_encode($credential),
            $optionsJson,
            parse_url(config('app.url'), PHP_URL_HOST),
            ['name' => $this->newPasskeyName],
        );

        $this->newPasskeyName = '';
        $this->loadPasskeys();
    }

    public function confirmDelete(int $id): void
    {
        $this->confirmingDeleteId = $id;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDeleteId = null;
    }

    public function removePasskey(int $id): void
    {
        DB::transaction(function () use ($id) {
            $passkey = auth()->user()->passkeys()->lockForUpdate()->findOrFail($id);

            if (auth()->user()->passkeys()->count() === 1 && ! auth()->user()->hasPassword()) {
                $this->addError('passkeys', __('You cannot remove your only passkey without a password set.'));

                return;
            }

            $passkey->delete();
            $this->confirmingDeleteId = null;
            $this->loadPasskeys();
        });
    }

    public function render(): View
    {
        return view('livewire.settings.passkeys');
    }

    private function loadPasskeys(): void
    {
        $this->passkeys = auth()->user()->passkeys()->orderByDesc('created_at')->get();
    }
}
