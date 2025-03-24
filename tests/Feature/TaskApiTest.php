<?php

namespace Tests\Feature;

use App\Enums\PriorityLevel;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TaskApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $token;

    public function setUp(): void
    {
        parent::setUp();

        // Create a user
        $this->user = User::factory()->create();

        // Create a token
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    public function test_unauthenticated_users_cannot_access_api()
    {
        $response = $this->getJson('/api/v1/tasks');
        $response->assertStatus(401);
    }

    public function test_can_get_all_tasks()
    {
        // Create some tasks for the user
        Task::factory()->count(5)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson('/api/v1/tasks', [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'tasks' => [
                    'data' => [
                        '*' => [
                            'id',
                            'title',
                            'desc',
                            'due',
                            'priority',
                            'completed',
                            'user_id',
                            'uuid',
                            'slug',
                        ],
                    ],
                ],
            ]);

        $this->assertCount(5, $response->json('tasks.data'));
    }

    public function test_can_get_single_task()
    {
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson('/api/v1/tasks/' . $task->id, [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'task' => [
                    'id',
                    'title',
                    'desc',
                    'due',
                    'priority',
                    'completed',
                    'user_id',
                    'uuid',
                    'slug',
                ],
            ])
            ->assertJsonPath('task.id', $task->id);
    }

    public function test_cannot_get_other_users_task()
    {
        $otherUser = User::factory()->create();
        $task = Task::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->getJson('/api/v1/tasks/' . $task->id, [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertStatus(404);
    }

    public function test_can_create_task()
    {
        $taskData = [
            'title' => 'Test Task Title',
            'desc' => 'This is a test task description',
            'priority' => PriorityLevel::MEDIUM->value,
            'due' => now()->addDays(7)->toDateTimeString(),
        ];

        $response = $this->postJson('/api/v1/tasks', $taskData, [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'task' => [
                    'id',
                    'title',
                    'desc',
                    'due',
                    'priority',
                    'completed',
                    'user_id',
                    'uuid',
                    'slug',
                ],
            ])
            ->assertJsonPath('task.title', $taskData['title'])
            ->assertJsonPath('task.desc', $taskData['desc'])
            ->assertJsonPath('task.priority', $taskData['priority'])
            ->assertJsonPath('task.completed', false)
            ->assertJsonPath('task.user_id', $this->user->id);

        $this->assertDatabaseHas('tasks', [
            'title' => $taskData['title'],
            'user_id' => $this->user->id,
        ]);
    }

    public function test_task_validation_on_create()
    {
        $response = $this->postJson('/api/v1/tasks', [
            'title' => 'Test', // Too short
            'desc' => '',      // Empty
            'priority' => 'not-a-valid-priority',
        ], [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'desc', 'priority']);
    }

    public function test_can_update_task()
    {
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $updateData = [
            'title' => 'Updated Task Title',
            'desc' => 'This is an updated description',
            'priority' => PriorityLevel::HIGH->value,
        ];

        $response = $this->putJson('/api/v1/tasks/' . $task->id, $updateData, [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'task',
            ])
            ->assertJsonPath('task.title', $updateData['title'])
            ->assertJsonPath('task.desc', $updateData['desc'])
            ->assertJsonPath('task.priority', $updateData['priority']);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => $updateData['title'],
            'desc' => $updateData['desc'],
        ]);
    }

    public function test_can_delete_task()
    {
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->deleteJson('/api/v1/tasks/' . $task->id, [], [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
            ]);

        $this->assertDatabaseMissing('tasks', [
            'id' => $task->id,
        ]);
    }

    public function test_can_toggle_task_completion()
    {
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
            'completed' => false,
        ]);

        $response = $this->patchJson('/api/v1/tasks/' . $task->id . '/toggle-completion', [], [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('task.completed', true);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'completed' => true,
        ]);

        // Toggle again
        $response = $this->patchJson('/api/v1/tasks/' . $task->id . '/toggle-completion', [], [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('task.completed', false);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'completed' => false,
        ]);
    }

    public function test_can_filter_tasks_by_status()
    {
        // Create completed tasks
        Task::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'completed' => true,
        ]);

        // Create uncompleted tasks
        Task::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'completed' => false,
        ]);

        // Test completed filter
        $response = $this->getJson('/api/v1/tasks?status=completed', [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('tasks.data'));

        // Test uncompleted filter
        $response = $this->getJson('/api/v1/tasks?status=uncompleted', [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('tasks.data'));
    }

    public function test_can_search_tasks()
    {
        // Create tasks with specific titles
        Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Find this task',
        ]);

        Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Another title',
            'desc' => 'But find this in description',
        ]);

        Task::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson('/api/v1/tasks?search=find', [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('tasks.data'));
    }

    public function test_can_sort_tasks()
    {
        // Create tasks with different due dates
        $task1 = Task::factory()->create([
            'user_id' => $this->user->id,
            'due' => now()->addDays(5),
        ]);

        $task2 = Task::factory()->create([
            'user_id' => $this->user->id,
            'due' => now()->addDays(1),
        ]);

        $task3 = Task::factory()->create([
            'user_id' => $this->user->id,
            'due' => now()->addDays(10),
        ]);

        // Test ascending sort
        $response = $this->getJson('/api/v1/tasks?sort_by=due&sort_direction=asc', [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertStatus(200);
        $this->assertEquals($task2->id, $response->json('tasks.data.0.id'));

        // Test descending sort
        $response = $this->getJson('/api/v1/tasks?sort_by=due&sort_direction=desc', [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertStatus(200);
        $this->assertEquals($task3->id, $response->json('tasks.data.0.id'));
    }

    public function test_can_export_tasks()
    {
        // Create some tasks
        Task::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson('/api/v1/tasks/export', [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'metadata' => [
                    'version',
                    'created_at',
                    'user_id',
                    'user_email',
                    'task_count',
                ],
                'tasks',
            ]);

        $this->assertCount(3, $response->json('tasks'));
    }

    public function test_can_import_tasks()
    {
        // Create export data structure
        $tasksData = Task::factory()->count(3)->make([
            'user_id' => $this->user->id,
        ])->toArray();

        // Add UUIDs to the tasks
        foreach ($tasksData as &$task) {
            $task['uuid'] = Str::uuid()->toString();
        }

        $importData = [
            'data' => [
                'metadata' => [
                    'version' => '1.0',
                    'created_at' => now()->toIso8601String(),
                    'user_id' => $this->user->id,
                    'user_email' => $this->user->email,
                    'task_count' => count($tasksData),
                ],
                'tasks' => $tasksData,
            ],
            'duplicate_action' => 'skip',
        ];

        $response = $this->postJson('/api/v1/tasks/import', $importData, [
            'Authorization' => 'Bearer ' . $this->token,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'stats' => [
                    'imported',
                    'updated',
                    'skipped',
                ],
            ]);

        $this->assertEquals(3, $response->json('stats.imported'));
        $this->assertDatabaseCount('tasks', 3);
    }
}