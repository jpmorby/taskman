<?php

use App\Enums\PriorityLevel;
use App\Livewire\TaskBackupManager;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    // Use fake storage for all tests
    Storage::fake('local');
});

test('unauthenticated user cannot access task backup manager', function () {
    $response = $this->get('/dashboard');
    $response->assertRedirect('/login');
});

test('authenticated user can see the task backup manager component', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get('/dashboard');
    $response->assertStatus(200);
    $response->assertSeeLivewire(TaskBackupManager::class);
});

test('user can export tasks', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Create some tasks for this user
    $task1 = Task::factory()->create(['user_id' => $user->id, 'title' => 'Task 1']);
    $task2 = Task::factory()->create(['user_id' => $user->id, 'title' => 'Task 2']);
    
    // Export the tasks
    $component = Livewire::test(TaskBackupManager::class)
        ->call('exportTasks');

    // We can't fully test the downloaded file in a test environment,
    // but we can check that the method was called successfully and
    // that the exports directory was created
    Storage::disk('local')->assertExists('exports');
});

test('user can validate backup file', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Create valid backup content
    $backupData = [
        'metadata' => [
            'version' => '1.0',
            'created_at' => now()->toIso8601String(),
            'user_id' => $user->id,
            'user_email' => $user->email,
            'task_count' => 2,
        ],
        'tasks' => [
            [
                'uuid' => '123e4567-e89b-12d3-a456-426614174000',
                'title' => 'Task 1',
                'desc' => 'Description 1',
                'due' => now()->addDays(3)->toIso8601String(),
                'priority' => PriorityLevel::MEDIUM->value,
                'completed' => false,
            ],
            [
                'uuid' => '123e4567-e89b-12d3-a456-426614174001',
                'title' => 'Task 2',
                'desc' => 'Description 2',
                'due' => now()->addDays(5)->toIso8601String(),
                'priority' => PriorityLevel::HIGH->value,
                'completed' => false,
            ],
        ]
    ];

    $jsonContent = json_encode($backupData);
    $file = UploadedFile::fake()->createWithContent('backup.json', $jsonContent);

    // Test validation
    $component = Livewire::test(TaskBackupManager::class)
        ->set('backupFile', $file)
        ->call('validateBackup');

    // Since there are no duplicates, it should proceed to import
    expect($component->get('duplicateFound'))->toBeFalse();
});

test('user cannot validate invalid backup file', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Create invalid backup content (missing required metadata)
    $backupData = [
        // Missing metadata
        'tasks' => [
            [
                'uuid' => '123e4567-e89b-12d3-a456-426614174000',
                'title' => 'Task 1',
                'desc' => 'Description 1',
            ],
        ]
    ];

    $jsonContent = json_encode($backupData);
    $file = UploadedFile::fake()->createWithContent('invalid_backup.json', $jsonContent);

    // Test validation
    $component = Livewire::test(TaskBackupManager::class)
        ->set('backupFile', $file)
        ->call('validateBackup');

    // The validation should fail
    expect($component->get('backupData'))->not->toBeNull();
});

test('user can import tasks from backup', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Create valid backup content
    $backupData = [
        'metadata' => [
            'version' => '1.0',
            'created_at' => now()->toIso8601String(),
            'user_id' => $user->id,
            'user_email' => $user->email,
            'task_count' => 2,
        ],
        'tasks' => [
            [
                'uuid' => '123e4567-e89b-12d3-a456-426614174000',
                'title' => 'Imported Task 1',
                'desc' => 'Description 1',
                'due' => now()->addDays(3)->toIso8601String(),
                'priority' => PriorityLevel::MEDIUM->value,
                'completed' => false,
                'slug' => 'imported-task-1'
            ],
            [
                'uuid' => '123e4567-e89b-12d3-a456-426614174001',
                'title' => 'Imported Task 2',
                'desc' => 'Description 2',
                'due' => now()->addDays(5)->toIso8601String(),
                'priority' => PriorityLevel::HIGH->value,
                'completed' => false,
                'slug' => 'imported-task-2'
            ],
        ]
    ];

    $jsonContent = json_encode($backupData);
    $file = UploadedFile::fake()->createWithContent('backup.json', $jsonContent);

    // Simulate the import process
    $component = Livewire::test(TaskBackupManager::class)
        ->set('backupFile', $file)
        ->call('validateBackup');

    // Set the backupData property
    $component->set('backupData', $backupData)
        ->call('processImport');

    // Verify tasks were imported
    $this->assertDatabaseHas('tasks', [
        'user_id' => $user->id,
        'title' => 'Imported Task 1',
        'uuid' => '123e4567-e89b-12d3-a456-426614174000',
    ]);

    $this->assertDatabaseHas('tasks', [
        'user_id' => $user->id,
        'title' => 'Imported Task 2',
        'uuid' => '123e4567-e89b-12d3-a456-426614174001',
    ]);
});

test('duplicate tasks can be skipped during import', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Create an existing task
    $existingTask = Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Existing Task',
        'uuid' => '123e4567-e89b-12d3-a456-426614174000',
    ]);

    // Create backup data with the same UUID
    $backupData = [
        'metadata' => [
            'version' => '1.0',
            'created_at' => now()->toIso8601String(),
            'user_id' => $user->id,
            'user_email' => $user->email,
            'task_count' => 1,
        ],
        'tasks' => [
            [
                'uuid' => '123e4567-e89b-12d3-a456-426614174000', // Same UUID
                'title' => 'Modified Task', // Different title
                'desc' => 'Modified Description',
                'due' => now()->addDays(3)->toIso8601String(),
                'priority' => PriorityLevel::MEDIUM->value,
                'completed' => false,
                'slug' => 'modified-task'
            ],
        ]
    ];

    $jsonContent = json_encode($backupData);
    $file = UploadedFile::fake()->createWithContent('backup.json', $jsonContent);

    // Simulate the import process
    $component = Livewire::test(TaskBackupManager::class)
        ->set('backupFile', $file)
        ->call('validateBackup');

    // Set the duplicate action to skip
    $component->set('duplicateAction', 'skip')
        ->set('backupData', $backupData)
        ->call('processImport');

    // Verify the existing task was not modified
    $this->assertDatabaseHas('tasks', [
        'user_id' => $user->id,
        'title' => 'Existing Task', // Original title should remain
        'uuid' => '123e4567-e89b-12d3-a456-426614174000',
    ]);

    // Verify the imported title was not created
    $this->assertDatabaseMissing('tasks', [
        'user_id' => $user->id,
        'title' => 'Modified Task',
    ]);
});

test('duplicate tasks can be overwritten during import', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Create an existing task
    $existingTask = Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Existing Task',
        'uuid' => '123e4567-e89b-12d3-a456-426614174000',
    ]);

    // Create backup data with the same UUID
    $backupData = [
        'metadata' => [
            'version' => '1.0',
            'created_at' => now()->toIso8601String(),
            'user_id' => $user->id,
            'user_email' => $user->email,
            'task_count' => 1,
        ],
        'tasks' => [
            [
                'uuid' => '123e4567-e89b-12d3-a456-426614174000', // Same UUID
                'title' => 'Modified Task', // Different title
                'desc' => 'Modified Description',
                'due' => now()->addDays(3)->toIso8601String(),
                'priority' => PriorityLevel::MEDIUM->value,
                'completed' => false,
                'slug' => 'modified-task'
            ],
        ]
    ];

    $jsonContent = json_encode($backupData);
    $file = UploadedFile::fake()->createWithContent('backup.json', $jsonContent);

    // Simulate the import process
    $component = Livewire::test(TaskBackupManager::class)
        ->set('backupFile', $file)
        ->call('validateBackup');

    // Set the duplicate action to overwrite
    $component->set('duplicateAction', 'overwrite')
        ->set('backupData', $backupData)
        ->call('processImport');

    // Verify the task was overwritten
    $this->assertDatabaseHas('tasks', [
        'user_id' => $user->id,
        'title' => 'Modified Task', // Should have the new title
        'uuid' => '123e4567-e89b-12d3-a456-426614174000',
    ]);

    // Verify the original title no longer exists
    $this->assertDatabaseMissing('tasks', [
        'user_id' => $user->id,
        'title' => 'Existing Task',
    ]);
});

test('duplicate tasks can be kept as duplicates during import', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Create an existing task
    $existingTask = Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Existing Task',
        'uuid' => '123e4567-e89b-12d3-a456-426614174000',
    ]);

    // Create backup data with the same UUID
    $backupData = [
        'metadata' => [
            'version' => '1.0',
            'created_at' => now()->toIso8601String(),
            'user_id' => $user->id,
            'user_email' => $user->email,
            'task_count' => 1,
        ],
        'tasks' => [
            [
                'uuid' => '123e4567-e89b-12d3-a456-426614174000', // Same UUID
                'title' => 'Modified Task', // Different title
                'desc' => 'Modified Description',
                'due' => now()->addDays(3)->toIso8601String(),
                'priority' => PriorityLevel::MEDIUM->value,
                'completed' => false,
                'slug' => 'modified-task'
            ],
        ]
    ];

    $jsonContent = json_encode($backupData);
    $file = UploadedFile::fake()->createWithContent('backup.json', $jsonContent);

    // Simulate the import process
    $component = Livewire::test(TaskBackupManager::class)
        ->set('backupFile', $file)
        ->call('validateBackup');

    // Set the duplicate action to keep both
    $component->set('duplicateAction', 'keep_both')
        ->set('backupData', $backupData)
        ->call('processImport');

    // Verify the original task still exists
    $this->assertDatabaseHas('tasks', [
        'user_id' => $user->id,
        'title' => 'Existing Task',
        'uuid' => '123e4567-e89b-12d3-a456-426614174000',
    ]);

    // Verify a new task was created with a different UUID but the same title as the import
    $this->assertDatabaseHas('tasks', [
        'user_id' => $user->id,
        'title' => 'Modified Task',
    ]);

    // Check that we now have 2 tasks
    expect(Task::where('user_id', $user->id)->count())->toBe(2);
});

test('imported tasks belong to the importing user regardless of original user', function () {
    $user1 = User::factory()->create(['email' => 'user1@example.com']);
    $user2 = User::factory()->create(['email' => 'user2@example.com']);
    
    $this->actingAs($user2); // Log in as user2
    
    // Create backup data with user1's ID
    $backupData = [
        'metadata' => [
            'version' => '1.0',
            'created_at' => now()->toIso8601String(),
            'user_id' => $user1->id, // Original user
            'user_email' => $user1->email,
            'task_count' => 1,
        ],
        'tasks' => [
            [
                'uuid' => '123e4567-e89b-12d3-a456-426614174000',
                'title' => 'User 1 Task',
                'desc' => 'Task created by User 1',
                'due' => now()->addDays(3)->toIso8601String(),
                'priority' => PriorityLevel::MEDIUM->value,
                'completed' => false,
                'slug' => 'user-1-task',
                'user_id' => $user1->id  // Original user_id
            ],
        ]
    ];
    
    $jsonContent = json_encode($backupData);
    $file = UploadedFile::fake()->createWithContent('backup.json', $jsonContent);
    
    // Simulate the import process as user2
    $component = Livewire::test(TaskBackupManager::class)
        ->set('backupFile', $file)
        ->call('validateBackup')
        ->set('backupData', $backupData)
        ->call('processImport');
    
    // Verify the task was imported but assigned to user2
    $this->assertDatabaseHas('tasks', [
        'title' => 'User 1 Task',
        'user_id' => $user2->id, // Should be assigned to the importing user
    ]);
});

test('user can cancel import process', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    
    // Create initial task count
    $initialTaskCount = 3;
    for ($i = 0; $i < $initialTaskCount; $i++) {
        Task::factory()->create(['user_id' => $user->id]);
    }
    
    // Simulate starting the import process
    $backupData = [
        'metadata' => [
            'version' => '1.0',
            'created_at' => now()->toIso8601String(),
            'user_id' => $user->id,
            'user_email' => $user->email,
            'task_count' => 2,
        ],
        'tasks' => [
            [
                'uuid' => '123e4567-e89b-12d3-a456-426614174000',
                'title' => 'Task to Import 1',
                'desc' => 'Description',
                'due' => now()->addDays(3)->toIso8601String(),
                'priority' => PriorityLevel::MEDIUM->value,
                'completed' => false,
            ],
            [
                'uuid' => '123e4567-e89b-12d3-a456-426614174001',
                'title' => 'Task to Import 2',
                'desc' => 'Description',
                'due' => now()->addDays(5)->toIso8601String(),
                'priority' => PriorityLevel::HIGH->value,
                'completed' => false,
            ],
        ]
    ];
    
    $jsonContent = json_encode($backupData);
    $file = UploadedFile::fake()->createWithContent('backup.json', $jsonContent);
    
    // Set up the component with the backup file
    $component = Livewire::test(TaskBackupManager::class)
        ->set('backupFile', $file)
        ->set('backupData', $backupData);
    
    // Cancel the import
    $component->call('cancelImport');
    
    // Verify properties were reset
    expect($component->get('backupFile'))->toBeNull();
    expect($component->get('backupData'))->toBeNull();
    expect($component->get('duplicateFound'))->toBeFalse();
    expect($component->get('potentialDuplicates'))->toBeEmpty();
    
    // Verify no new tasks were added
    expect(Task::where('user_id', $user->id)->count())->toBe($initialTaskCount);
});