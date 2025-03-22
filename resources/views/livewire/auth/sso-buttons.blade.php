<div class="form-group">
    <div class="col-md-6 col-md-offset-4 text-center">
        @if (Route::has('login.socialite'))
            <flux:table class="gap-6 overflow-hidden">
                <flux:table.rows>
                    <flux:table.row>
                        <flux:table.cell>
                            <flux:button size="xs" href="/login/github" icon="github" variant="outline" class="btn btn-github">
                                Github
                            </flux:button>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:button size="xs" href="/login/google" icon="google" variant="outline" class="btn btn-google">
                                    Google
                                </flux:button>
                                </flux:table.cell>
                                <flux:table.cell>
                                <flux:button size="xs" href="/login/discord" icon="discord" variant="outline" class="btn btn-discord">
                                    Discord
                                </flux:button>
                                </flux:table.cell>
                                {{-- <flux:table.cell>
                                    <flux:button size="xs" href="/login/apple" icon="apple" variant="outline" class="btn btn-apple">
                                        Apple
                                    </flux:button>
                                </flux:table.cell> --}}
                        </flux:row>
                        </flux:rows>
            </flux:table>

        @endif
    </div>
</div>