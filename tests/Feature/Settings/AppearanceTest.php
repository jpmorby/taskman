<?php

use App\Livewire\Settings\Appearance;
use App\Models\User;
use Livewire\Livewire;

test('appearance component can be rendered', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get('/settings/appearance');
    $response->assertStatus(200);
    $response->assertSeeLivewire(Appearance::class);
});

test('appearance component returns correct view', function () {
    $component = Livewire::test(Appearance::class);
    
    // Make sure the component renders without errors
    // Since this is a placeholder component with no functionality yet,
    // we're just checking that it renders without throwing exceptions
    $component->assertStatus(200);
    
    // If the component eventually implements functionality,
    // additional tests should be added here
});