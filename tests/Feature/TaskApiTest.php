<?php

namespace Tests\Feature;

use App\Enums\PriorityLevel;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class TaskApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private string $token;

    protected function setUp(): void
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
            'Authorization' => 'Bearer '.$this->token,
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

        $response = $this->getJson('/api/v1/tasks/'.$task->id, [
            'Authorization' => 'Bearer '.$this->token,
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

        $response = $this->getJson('/api/v1/tasks/'.$task->id, [
            'Authorization' => 'Bearer '.$this->token,
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
            'Authorization' => 'Bearer '.$this->token,
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
            'Authorization' => 'Bearer '.$this->token,
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

        $response = $this->putJson('/api/v1/tasks/'.$task->id, $updateData, [
            'Authorization' => 'Bearer '.$this->token,
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

        $response = $this->deleteJson('/api/v1/tasks/'.$task->id, [], [
            'Authorization' => 'Bearer '.$this->token,
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

        $response = $this->patchJson('/api/v1/tasks/'.$task->id.'/toggle-completion', [], [
            'Authorization' => 'Bearer '.$this->token,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('task.completed', true);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'completed' => true,
        ]);

        // Toggle again
        $response = $this->patchJson('/api/v1/tasks/'.$task->id.'/toggle-completion', [], [
            'Authorization' => 'Bearer '.$this->token,
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
            'Authorization' => 'Bearer '.$this->token,
        ]);

        $response->assertStatus(200);
        $this->assertCount(3, $response->json('tasks.data'));

        // Test uncompleted filter
        $response = $this->getJson('/api/v1/tasks?status=uncompleted', [
            'Authorization' => 'Bearer '.$this->token,
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
            'Authorization' => 'Bearer '.$this->token,
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
            'Authorization' => 'Bearer '.$this->token,
        ]);

        $response->assertStatus(200);
        $this->assertEquals($task2->id, $response->json('tasks.data.0.id'));

        // Test descending sort
        $response = $this->getJson('/api/v1/tasks?sort_by=due&sort_direction=desc', [
            'Authorization' => 'Bearer '.$this->token,
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
            'Authorization' => 'Bearer '.$this->token,
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
            'Authorization' => 'Bearer '.$this->token,
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

    public function test_import_rejects_an_invalid_priority()
    {
        $response = $this->postJson('/api/v1/tasks/import', $this->importPayload([
            $this->importTask(['title' => 'Valid Imported Task']),
            $this->importTask(['title' => 'Bogus Priority Task', 'priority' => 'BOGUS']),
        ]), [
            'Authorization' => 'Bearer '.$this->token,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['data.tasks.1.priority']);

        // Nothing may be written when any task in the file is rejected
        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_import_rejects_a_task_that_is_not_an_array()
    {
        $response = $this->postJson('/api/v1/tasks/import', $this->importPayload([
            'not-a-task',
        ]), [
            'Authorization' => 'Bearer '.$this->token,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['data.tasks.0']);

        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_import_rejects_a_task_with_a_non_string_uuid()
    {
        $response = $this->postJson('/api/v1/tasks/import', $this->importPayload([
            $this->importTask(['uuid' => ['nested' => 'array']]),
        ]), [
            'Authorization' => 'Bearer '.$this->token,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['data.tasks.0.uuid']);

        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_import_ignores_attributes_the_backup_file_may_not_set()
    {
        $response = $this->postJson('/api/v1/tasks/import', $this->importPayload([
            $this->importTask([
                'title' => 'Smuggled Attribute Task',
                'id' => 999,
                'user_id' => 12345,
                'completed_at' => now()->toIso8601String(),
                'created_at' => now()->subYear()->toIso8601String(),
            ]),
        ]), [
            'Authorization' => 'Bearer '.$this->token,
        ]);

        $response->assertStatus(200);

        $task = Task::firstOrFail();

        $this->assertNotEquals(999, $task->id);
        $this->assertEquals($this->user->id, $task->user_id);
        $this->assertArrayNotHasKey('completed_at', $task->getAttributes());
        $this->assertTrue($task->created_at->isToday());
    }

    public function test_import_is_rolled_back_when_a_task_fails_to_save()
    {
        // A failure part way through the file must leave no tasks behind at all
        Event::listen('eloquent.creating: '.Task::class, function ($task) {
            if ($task->title === 'Second Imported Task') {
                throw new RuntimeException('Simulated database failure');
            }
        });

        $response = $this->postJson('/api/v1/tasks/import', $this->importPayload([
            $this->importTask(['title' => 'First Imported Task']),
            $this->importTask(['title' => 'Second Imported Task']),
        ]), [
            'Authorization' => 'Bearer '.$this->token,
        ]);

        $response->assertStatus(500);
        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_sorting_rejects_an_unknown_column()
    {
        Task::factory()->count(2)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson('/api/v1/tasks?sort_by=nope', [
            'Authorization' => 'Bearer '.$this->token,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sort_by']);
    }

    public function test_sorting_rejects_an_unknown_direction()
    {
        $response = $this->getJson('/api/v1/tasks?sort_by=due&sort_direction=sideways', [
            'Authorization' => 'Bearer '.$this->token,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sort_direction']);
    }

    public function test_per_page_is_capped()
    {
        Task::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->getJson('/api/v1/tasks?per_page=1000000', [
            'Authorization' => 'Bearer '.$this->token,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['per_page']);

        $response = $this->getJson('/api/v1/tasks?per_page=0', [
            'Authorization' => 'Bearer '.$this->token,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['per_page']);

        // The top of the allowed range is still accepted
        $response = $this->getJson('/api/v1/tasks?per_page=100', [
            'Authorization' => 'Bearer '.$this->token,
        ]);

        $response->assertStatus(200);
        $this->assertEquals(100, $response->json('tasks.per_page'));
    }

    public function test_a_completed_task_survives_an_export_import_round_trip()
    {
        $completed = Task::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'A Completed Round Trip Task',
            'desc' => 'This task was finished before the backup was taken',
            'priority' => PriorityLevel::HIGH->value,
            'due' => now()->addDays(4),
            'completed' => true,
        ]);

        $export = $this->getJson('/api/v1/tasks/export', [
            'Authorization' => 'Bearer '.$this->token,
        ]);

        $export->assertStatus(200);

        // Lose the tasks, then restore the untouched export
        Task::query()->delete();
        $this->assertDatabaseCount('tasks', 0);

        $response = $this->postJson('/api/v1/tasks/import', [
            'data' => $export->json(),
            'duplicate_action' => 'skip',
        ], [
            'Authorization' => 'Bearer '.$this->token,
        ]);

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('stats.imported'));

        $restored = $this->user->tasks()->firstOrFail();

        $this->assertEquals($completed->uuid, $restored->uuid);
        $this->assertEquals($completed->title, $restored->title);
        $this->assertEquals($completed->desc, $restored->desc);
        $this->assertEquals($completed->priority, $restored->priority);
        $this->assertEquals($completed->due->toDateString(), $restored->due->toDateString());
        $this->assertTrue($restored->completed);
    }

    /**
     * A single task as it appears inside a backup file.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function importTask(array $overrides = []): array
    {
        return array_merge([
            'uuid' => Str::uuid()->toString(),
            'title' => 'An Imported Task',
            'desc' => 'An imported task description',
            'priority' => PriorityLevel::MEDIUM->value,
            'due' => now()->addDays(3)->toIso8601String(),
            'completed' => false,
        ], $overrides);
    }

    /**
     * A complete import request body wrapping the given tasks.
     *
     * @param  array<int, mixed>  $tasks
     * @return array<string, mixed>
     */
    private function importPayload(array $tasks, string $duplicateAction = 'skip'): array
    {
        return [
            'data' => [
                'metadata' => [
                    'version' => '1.0',
                    'created_at' => now()->toIso8601String(),
                    'user_id' => $this->user->id,
                    'user_email' => $this->user->email,
                    'task_count' => count($tasks),
                ],
                'tasks' => $tasks,
            ],
            'duplicate_action' => $duplicateAction,
        ];
    }
}
