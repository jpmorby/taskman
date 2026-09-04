<?php

use App\Livewire\TodoList;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * The task id in each of these calls is a Livewire method argument supplied by
 * the browser, so every one of them has to be scoped to the caller's own tasks.
 */
beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->intruder = User::factory()->create();
    $this->victimTask = Task::factory()->create(['user_id' => $this->owner->id]);
});

test('a user cannot delete another users task', function () {
    $this->actingAs($this->intruder);

    expect(fn () => Livewire::test(TodoList::class)->call('delete', $this->victimTask->id))
        ->toThrow(ModelNotFoundException::class);

    $this->assertDatabaseHas('tasks', ['id' => $this->victimTask->id]);
});

test('a user cannot toggle another users task', function () {
    $completed = $this->victimTask->fresh()->completed;

    $this->actingAs($this->intruder);

    expect(fn () => Livewire::test(TodoList::class)->call('toggleCompleted', $this->victimTask->id))
        ->toThrow(ModelNotFoundException::class);

    expect($this->victimTask->fresh()->completed)->toBe($completed);
});

test('a user cannot load another users task into the edit modal', function () {
    $this->actingAs($this->intruder);

    expect(fn () => Livewire::test(TodoList::class)->call('edit', $this->victimTask->id))
        ->toThrow(ModelNotFoundException::class);
});

test('a user cannot read another users task through showCard', function () {
    $this->actingAs($this->intruder);

    expect(fn () => Livewire::test(TodoList::class)->call('showCard', $this->victimTask->id))
        ->toThrow(ModelNotFoundException::class);
});

test('the owner can still act on their own task', function () {
    $this->actingAs($this->owner);

    Livewire::test(TodoList::class)
        ->call('toggleCompleted', $this->victimTask->id)
        ->assertHasNoErrors();

    expect($this->victimTask->fresh()->completed)->toBe(! $this->victimTask->completed);

    Livewire::test(TodoList::class)
        ->call('delete', $this->victimTask->id)
        ->assertHasNoErrors();

    $this->assertDatabaseMissing('tasks', ['id' => $this->victimTask->id]);
});

test('sort ignores a column that is not sortable', function () {
    $this->actingAs($this->owner);

    Livewire::test(TodoList::class)
        ->call('sort', 'id); drop table tasks --')
        ->assertSet('sortBy', 'due')
        ->assertStatus(200);
});

test('sort accepts a whitelisted column', function () {
    $this->actingAs($this->owner);

    Livewire::test(TodoList::class)
        ->call('sort', 'title')
        ->assertSet('sortBy', 'title')
        ->assertSet('sortDirection', 'asc');
});

test('the browser cannot set the page size or sort state directly', function () {
    $this->actingAs($this->owner);

    $component = Livewire::test(TodoList::class);

    expect(fn () => $component->set('tableLength', 1000000))
        ->toThrow(Exception::class);

    expect(fn () => $component->set('sortBy', 'password'))
        ->toThrow(Exception::class);
});
