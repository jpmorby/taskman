<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Log in to your account')" :description="__('Enter your email and password below to log in')" />

    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    <form wire:submit="login" class="flex flex-col gap-6">
        <!-- Email Address -->
        <flux:input wire:model="email" :label="__('Email address')" type="email" required autofocus autocomplete="email"
            placeholder="email@example.com" />

        <!-- Password -->
        <div class="relative">
            <flux:input wire:model="password" :label="__('Password')" type="password" required
                autocomplete="current-password" :placeholder="__('Password')" />

            @if (Route::has('password.request'))
                <flux:link class="absolute right-0 top-0 text-sm" :href="route('password.request')" wire:navigate>
                    {{ __('Forgot your password?') }}
                </flux:link>
            @endif
        </div>

        <!-- Remember Me -->
        <flux:checkbox wire:model="remember" :label="__('Remember me')" />

        <div class="flex items-center justify-end">
            <flux:button variant="primary" type="submit" class="w-full">{{ __('Log in') }}</flux:button>
        </div>
        <hr>
        <div class="form-group">
            <div class="col-md-6 col-md-offset-4">
                <a href="{{ url('/login/github') }}" class="btn btn-github"><i class="fa fa-github"></i> Github</a>
                <a href="{{ url('/login/google') }}" class="btn btn-google" class="btn btn-google"><i
                        class="fa fa-google"></i> Google</a>
                <div id="g_id_onload"
                    data-client_id="108917130607-uak7dsj6u643f16eqgj36gafsbcifor0.apps.googleusercontent.com"
                    data-context="signin" data-ux_mode="popup" data-login_uri="https://task.me.uk/login/google/callback"
                    data-nonce="" data-auto_select="true" data-itp_support="true">
                </div>

                <div class="g_id_signin" data-type="standard" data-shape="rectangular" data-theme="outline"
                    data-text="signin_with" data-size="large" data-logo_alignment="left">
                </div>
            </div>
        </div>
    </form>

    @if (Route::has('register'))
        <div class="space-x-1 text-center text-sm text-zinc-600 dark:text-zinc-400">
            {{ __('Don\'t have an account?') }}
            <flux:link :href="route('register')" wire:navigate>{{ __('Sign up') }}</flux:link>
        </div>
    @endif
</div>