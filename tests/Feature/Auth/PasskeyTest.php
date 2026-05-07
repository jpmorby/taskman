<?php

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\LaravelPasskeys\Database\Factories\PasskeyFactory;

uses(RefreshDatabase::class);

it('user has a passkeys relationship', function () {
    $user = User::factory()->create();

    expect($user->passkeys())->toBeInstanceOf(HasMany::class);
});

it('user can have multiple passkeys', function () {
    $user = User::factory()->create();

    PasskeyFactory::new()
        ->for($user, 'authenticatable')
        ->create(['name' => 'MacBook']);

    PasskeyFactory::new()
        ->for($user, 'authenticatable')
        ->create(['name' => 'iPhone']);

    expect($user->passkeys()->count())->toBe(2);
});

it('settings/passkeys route requires authentication', function () {
    $response = $this->get(route('settings.passkeys'));

    $response->assertRedirect(route('login'));
});

it('authenticated user can access settings/passkeys', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('settings.passkeys'));

    $response->assertOk();
});

use App\Livewire\Settings\Passkeys as PasskeysPage;
use Livewire\Livewire;
use Spatie\LaravelPasskeys\Actions\GeneratePasskeyRegisterOptionsAction;
use Spatie\LaravelPasskeys\Actions\StorePasskeyAction;
use Spatie\LaravelPasskeys\Models\Passkey;

it('displays existing passkeys on the settings page', function () {
    $user = User::factory()->create();
    PasskeyFactory::new()->for($user, 'authenticatable')->create(['name' => 'Work MacBook']);

    Livewire::actingAs($user)
        ->test(PasskeysPage::class)
        ->assertSee('Work MacBook');
});

it('dispatches passkey-register event when startRegistration is called', function () {
    $user = User::factory()->create();
    $mockOptionsJson = '{"challenge":"dGVzdA","rp":{"name":"Taskman"}}';

    $mock = Mockery::mock(GeneratePasskeyRegisterOptionsAction::class);
    $mock->shouldReceive('execute')->once()->andReturn($mockOptionsJson);
    app()->instance(GeneratePasskeyRegisterOptionsAction::class, $mock);

    Livewire::actingAs($user)
        ->test(PasskeysPage::class)
        ->set('newPasskeyName', 'Work MacBook')
        ->call('startRegistration')
        ->assertDispatched('passkey-register');
});

it('stores a passkey via StorePasskeyAction', function () {
    $user = User::factory()->create();
    $credential = ['id' => 'cred-id', 'type' => 'public-key', 'rawId' => 'cred-id'];

    $mock = Mockery::mock(StorePasskeyAction::class);
    $mock->shouldReceive('execute')->once();
    app()->instance(StorePasskeyAction::class, $mock);

    session()->put('passkey_register_options', '{"challenge":"dGVzdA"}');

    Livewire::actingAs($user)
        ->test(PasskeysPage::class)
        ->set('newPasskeyName', 'Work MacBook')
        ->call('confirmPasskey', $credential);
});

it('deletes a passkey when user has a password set', function () {
    $user = User::factory()->create(['password' => bcrypt('secret')]);
    $passkey = PasskeyFactory::new()->for($user, 'authenticatable')->create(['name' => 'Old Phone']);

    Livewire::actingAs($user)
        ->test(PasskeysPage::class)
        ->call('removePasskey', $passkey->id);

    expect(Passkey::find($passkey->id))->toBeNull();
});

it('blocks deletion of last passkey when user has no password', function () {
    $user = User::factory()->create(['password' => null]);
    $passkey = PasskeyFactory::new()->for($user, 'authenticatable')->create(['name' => 'Only Key']);

    Livewire::actingAs($user)
        ->test(PasskeysPage::class)
        ->call('removePasskey', $passkey->id)
        ->assertHasErrors(['passkeys']);

    expect(Passkey::find($passkey->id))->not->toBeNull();
});

it('allows deletion of one of multiple passkeys even with no password', function () {
    $user = User::factory()->create(['password' => null]);
    $passkey1 = PasskeyFactory::new()->for($user, 'authenticatable')->create(['name' => 'Key One']);
    PasskeyFactory::new()->for($user, 'authenticatable')->create(['name' => 'Key Two']);

    Livewire::actingAs($user)
        ->test(PasskeysPage::class)
        ->call('removePasskey', $passkey1->id);

    expect(Passkey::find($passkey1->id))->toBeNull();
});
