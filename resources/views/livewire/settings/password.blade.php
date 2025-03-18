<section class="w-full">
    @include('partials.settings-heading')
@if(Auth::user()->hasPassword())
    <x-settings.layout :heading="__('Update password')" :subheading="__('Ensure your account is using a long, random password to stay secure')">
        <form wire:submit="updatePassword" class="mt-6 space-y-6">
            <flux:input
                wire:model="current_password"
                :label="__('Current password')"
                type="password"
                required
                autocomplete="current-password"
            />
            <flux:input
                wire:model="password"
                :label="__('New password')"
                type="password"
                required
                autocomplete="new-password"
            />
            <flux:input
                wire:model="password_confirmation"
                :label="__('Confirm Password')"
                type="password"
                required
                autocomplete="new-password"
            />

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full">{{ __('Save') }}</flux:button>
                </div>

                <x-action-message class="me-3" on="password-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>
    </x-settings.layout>
    @else
                <x-settings.layout>
        <x-action-message class="me-3" on="password-reset-sent">
            {{ __('Password reset email sent.') }}
        </x-action-message>
                    <form wire:submit="sendPasswordResetLink" class="flex flex-col gap-6">
                        <flux:input type="hidden" wire:model="email" />
                        <flux:button variant="primary" type="submit">Click here to request a password reset email</flux:button>
                    </form>
                    </x-settings.layout>
    @endif
</section>
