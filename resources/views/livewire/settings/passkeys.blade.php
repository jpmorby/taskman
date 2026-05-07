<section
    class="w-full"
    x-data="passkeyRegister"
    @passkey-register.window="register($event.detail.options)"
>
    @include('partials.settings-heading')

    <x-settings.layout
        :heading="__('Passkeys')"
        :subheading="__('Manage your passkeys for passwordless sign-in')"
    >
        @if ($errors->has('passkeys'))
            <flux:callout variant="danger" class="mb-4">
                {{ $errors->first('passkeys') }}
            </flux:callout>
        @endif

        <template x-if="error">
            <flux:callout variant="danger" class="mb-4" x-text="error"></flux:callout>
        </template>

        {{-- Existing passkeys list --}}
        @if ($passkeys->isNotEmpty())
            <div class="mb-6 divide-y divide-zinc-200 dark:divide-zinc-700 rounded-lg border border-zinc-200 dark:border-zinc-700">
                @foreach ($passkeys as $passkey)
                    <div class="flex items-center justify-between px-4 py-3">
                        <div>
                            <flux:text class="font-medium">{{ $passkey->name }}</flux:text>
                            <flux:text size="sm" class="text-zinc-500">
                                {{ __('Added') }} {{ $passkey->created_at->diffForHumans() }}
                            </flux:text>
                        </div>

                        <flux:modal.trigger name="confirm-delete-{{ $passkey->id }}">
                            <flux:button
                                variant="danger"
                                size="sm"
                                x-on:click.prevent="$dispatch('open-modal', 'confirm-delete-{{ $passkey->id }}')"
                                wire:click="confirmDelete({{ $passkey->id }})"
                            >
                                {{ __('Remove') }}
                            </flux:button>
                        </flux:modal.trigger>

                        <flux:modal name="confirm-delete-{{ $passkey->id }}" class="max-w-md" focusable>
                            <div class="flex flex-col gap-4">
                                <flux:heading size="lg">{{ __('Remove passkey?') }}</flux:heading>
                                <flux:subheading>
                                    {{ __('This will remove ":name". You will no longer be able to sign in with it.', ['name' => $passkey->name]) }}
                                </flux:subheading>
                                <div class="flex justify-end gap-2">
                                    <flux:modal.close>
                                        <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                                    </flux:modal.close>
                                    <flux:button variant="danger" wire:click="removePasskey({{ $passkey->id }})">
                                        {{ __('Remove') }}
                                    </flux:button>
                                </div>
                            </div>
                        </flux:modal>
                    </div>
                @endforeach
            </div>
        @else
            <flux:text class="mb-6 text-zinc-500">
                {{ __('No passkeys registered yet.') }}
            </flux:text>
        @endif

        {{-- Add passkey --}}
        <flux:modal.trigger name="add-passkey">
            <flux:button
                variant="primary"
                x-on:click.prevent="$dispatch('open-modal', 'add-passkey')"
            >
                {{ __('Add passkey') }}
            </flux:button>
        </flux:modal.trigger>

        <flux:modal name="add-passkey" class="max-w-md" focusable>
            <div class="flex flex-col gap-4">
                <flux:heading size="lg">{{ __('Add a passkey') }}</flux:heading>
                <flux:subheading>
                    {{ __('Give this passkey a name so you can identify it later.') }}
                </flux:subheading>
                <flux:input
                    wire:model="newPasskeyName"
                    :label="__('Passkey name')"
                    placeholder="{{ __('e.g. Work MacBook') }}"
                    autofocus
                />
                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                    </flux:modal.close>
                    <flux:button variant="primary" wire:click="startRegistration">
                        {{ __('Add passkey') }}
                    </flux:button>
                </div>
            </div>
        </flux:modal>
    </x-settings.layout>
</section>
