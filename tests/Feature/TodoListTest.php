<?php

use App\Enums\PriorityLevel;
use App\Livewire\TodoList;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('unauthenticated user cannot access todo list', function () {
    $response = $this->get('/dashboard');
    $response->assertRedirect('/login');
});

test('authenticated user can see the todo list component', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get('/dashboard');
    $response->assertStatus(200);
    $response->assertSeeLivewire(TodoList::class);
});

test('user can create a new task', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $taskTitle = 'Test Task Title';
    $taskDesc = 'This is a test task description';

    Livewire::test(TodoList::class)
        ->set('title', $taskTitle)
        ->set('desc', $taskDesc)
        ->set('due', now()->addDay())
        ->set('priority', PriorityLevel::MEDIUM)
        ->call('create');

    // Verify task was created in the database
    $this->assertDatabaseHas('tasks', [
        'user_id' => $user->id,
        'title' => $taskTitle,
        'desc' => $taskDesc,
        'slug' => Str::of($taskTitle)->slug(),
        'completed' => false,
    ]);
});

test('task creation requires valid data', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(TodoList::class)
        ->set('title', 'Ab') // Too short
        ->set('desc', '')    // Required
        ->set('due', now()->addDay())
        ->set('priority', PriorityLevel::MEDIUM)
        ->call('create')
        ->assertHasErrors(['title', 'desc']);
});

test('user can edit a task', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Create a task first
    $task = Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Original Title',
        'desc' => 'Original Description',
        'priority' => PriorityLevel::LOW,
    ]);

    $newTitle = 'Updated Task Title';
    $newDesc = 'Updated task description';

    Livewire::test(TodoList::class)
        ->call('edit', $task->id)
        ->set('title', $newTitle)
        ->set('desc', $newDesc)
        ->set('priority', PriorityLevel::HIGH)
        ->call('create'); // The edit form reuses the create method

    // Verify task was updated
    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'user_id' => $user->id,
        'title' => $newTitle,
        'desc' => $newDesc,
        'slug' => Str::of($newTitle)->slug(),
        'priority' => PriorityLevel::HIGH->value,
    ]);
});

test('user can delete a task', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Create a task first
    $task = Task::factory()->create([
        'user_id' => $user->id,
    ]);

    // Make sure it exists
    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
    ]);

    Livewire::test(TodoList::class)
        ->call('delete', $task->id);

    // Verify task was deleted
    $this->assertDatabaseMissing('tasks', [
        'id' => $task->id,
    ]);
});

test('user can toggle task completion status', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Create a task first
    $task = Task::factory()->create([
        'user_id' => $user->id,
        'completed' => false,
    ]);

    Livewire::test(TodoList::class)
        ->call('toggleCompleted', $task->id);

    // Verify task was marked as completed
    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'completed' => true,
    ]);

    // Toggle back to not completed
    Livewire::test(TodoList::class)
        ->call('toggleCompleted', $task->id);

    // Verify task was marked as not completed
    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'completed' => false,
    ]);
});

test('tasks can be filtered by completion status', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Create tasks with different completion statuses
    $completedTask = Task::factory()->create([
        'user_id' => $user->id,
        'completed' => true,
        'title' => 'Completed Task',
    ]);

    $activeTask = Task::factory()->create([
        'user_id' => $user->id,
        'completed' => false,
        'title' => 'Active Task',
    ]);

    // Test active filter
    $component = Livewire::test(TodoList::class)
        ->call('setFilter', 'active');

    expect($component->get('activeFilter'))->toBe('active');

    // Test completed filter
    $component = Livewire::test(TodoList::class)
        ->call('setFilter', 'completed');

    expect($component->get('activeFilter'))->toBe('completed');
});

test('tasks can be filtered by priority', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Create tasks with different priority levels
    $highPriorityTask = Task::factory()->create([
        'user_id' => $user->id,
        'priority' => PriorityLevel::HIGH,
        'title' => 'High Priority Task',
    ]);

    $lowPriorityTask = Task::factory()->create([
        'user_id' => $user->id,
        'priority' => PriorityLevel::LOW,
        'title' => 'Low Priority Task',
    ]);

    // Test priority filter
    $component = Livewire::test(TodoList::class)
        ->set('activePriorityFilter', PriorityLevel::HIGH->value);

    expect($component->get('activePriorityFilter'))->toBe(PriorityLevel::HIGH->value);
});

test('tasks can be searched by title or description', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Create tasks with different titles and descriptions
    $task1 = Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Meeting with John',
        'desc' => 'Discuss project timeline',
    ]);

    $task2 = Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Buy groceries',
        'desc' => 'Get milk, eggs, and bread',
    ]);

    // Test search by title
    $component = Livewire::test(TodoList::class)
        ->set('needle', 'Meeting');

    expect($component->get('needle'))->toBe('Meeting');

    // Test search by description
    $component = Livewire::test(TodoList::class)
        ->set('needle', 'milk');

    expect($component->get('needle'))->toBe('milk');
});

test('tasks can be sorted by different columns', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Test sorting
    $component = Livewire::test(TodoList::class)
        ->call('sort', 'due');

    expect($component->get('sortBy'))->toBe('due');
    expect($component->get('sortDirection'))->toBe('desc'); // Toggle from default 'asc'

    // Toggle sort direction again
    $component->call('sort', 'due');
    expect($component->get('sortDirection'))->toBe('asc');

    // Sort by a different column
    $component->call('sort', 'title');
    expect($component->get('sortBy'))->toBe('title');
    expect($component->get('sortDirection'))->toBe('asc'); // Default for new column
});

test('tasks can be filtered by time ranges', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Create tasks with different due dates
    $todayTask = Task::factory()->create([
        'user_id' => $user->id,
        'due' => now(),
        'title' => 'Due Today',
    ]);

    $nextWeekTask = Task::factory()->create([
        'user_id' => $user->id,
        'due' => now()->addWeek(),
        'title' => 'Due Next Week',
    ]);

    $nextMonthTask = Task::factory()->create([
        'user_id' => $user->id,
        'due' => now()->addMonth(),
        'title' => 'Due Next Month',
    ]);

    // Test today filter
    $component = Livewire::test(TodoList::class)
        ->call('setFilter', 'today');
    expect($component->get('activeFilter'))->toBe('today');

    // Test this week filter
    $component = Livewire::test(TodoList::class)
        ->call('setFilter', 'thisWeek');
    expect($component->get('activeFilter'))->toBe('thisWeek');

    // Test next 7 days filter
    $component = Livewire::test(TodoList::class)
        ->call('setFilter', 'next7Days');
    expect($component->get('activeFilter'))->toBe('next7Days');

    // Test next 30 days filter
    $component = Livewire::test(TodoList::class)
        ->call('setFilter', 'next30Days');
    expect($component->get('activeFilter'))->toBe('next30Days');
});

test('tasks are associated with the correct user', function () {
    // Create two users with their own tasks
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $user1Task = Task::factory()->create([
        'user_id' => $user1->id,
        'title' => 'User 1 Task',
    ]);

    $user2Task = Task::factory()->create([
        'user_id' => $user2->id,
        'title' => 'User 2 Task',
    ]);

    // Log in as user 1
    $this->actingAs($user1);

    // User 1 should see their tasks but not user 2's tasks
    $response = $this->get('/dashboard');
    $response->assertSee('User 1 Task');
    $response->assertDontSee('User 2 Task');

    // Log in as user 2
    $this->actingAs($user2);

    // User 2 should see their tasks but not user 1's tasks
    $response = $this->get('/dashboard');
    $response->assertSee('User 2 Task');
    $response->assertDontSee('User 1 Task');
});
