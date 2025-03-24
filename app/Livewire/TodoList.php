<?php

namespace App\Livewire;

use App\Enums\PriorityLevel;
use App\Models\Task;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithPagination;
use Stevebauman\Purify\Facades\Purify;

class TodoList extends Component
{
    use WithPagination;

    #[Rule('required|min:5|max:250')]
    public $title;

    #[Rule('required')]
    public string $desc;

    #[Rule('required')]
    public $priority;

    public string $slug;

    public array $media;

    // #[Rule('date|after_or_equal:today')]
    public $due;

    public int $tableLength = 10;

    public bool $completed = false;

    public $editItem;

    public $viewItem;

    public $viewTask;

    public $user_id;

    #[Rule('string|min:3')]
    public $needle = '';

    public $searchResults;

    public $sortBy = 'due';

    public $sortDirection = 'asc';

    // Change this property to store the active priority filter
    public $activePriorityFilter = '';

    public string $activeFilter = 'active';

    public function mount()
    {
        $this->user_id = Auth::id();

    }

    public function sort($index)
    {
        if ($this->sortBy === $index) {
            // If clicking the same column, toggle direction
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            // New column, set as the sort field and default to ascending
            $this->sortBy = $index;
            $this->sortDirection = 'asc';
        }
    }

    public function addTask()
    {

        Log::debug('addTask');

        $this->priority = PriorityLevel::LOW;

        Flux::modal('addTask')->show();

    }

    public function badgeColour(PriorityLevel $priority)
    {
        switch ($priority) {
            case PriorityLevel::CRITICAL:
                return 'red';
            case PriorityLevel::HIGH:
                return 'orange';
            case PriorityLevel::MEDIUM:
                return 'lime';
            case PriorityLevel::LOW:
                return 'cyan';
            case PriorityLevel::NONE:
                return 'gray';
            default:
                return 'pink';
        }
    }

    public function create()
    {
        if ($this->editItem) {
            // Validate with explicit error catching
            try {
                $validated = $this->validate();
            } catch (\Illuminate\Validation\ValidationException $e) {
                // Log validation errors
                Log::error('Validation failed: '.json_encode($e->errors()));
                throw $e;
            }

            // If we are editing an existing task, we don't want to create a new one
            Log::debug('create - editItem');

            try {
                Auth::user()->tasks()->findOrFail($this->editItem->id)->update([
                    'title' => Purify::clean($this->title),
                    'slug' => Str::of($this->title)->slug(),
                    'desc' => Purify::clean($this->desc),
                    'due' => $this->due,
                    'priority' => $this->priority,
                ]);

                $this->editItem = null;
                $this->reset(['title', 'desc', 'due', 'priority', 'slug']);
                Flux::modal('addTask')->close();
                $this->dispatch('task-updated');
            } catch (\Exception $e) {
                Log::error('Update failed: '.$e->getMessage());
                throw $e;
            }

            return;
        }

        Log::debug('create');

        try {
            // Set slug before validation
            $this->slug = Str::of($this->title)->slug();
            Log::debug("Slug: $this->slug");

            // Validate with explicit error catching
            $validated = $this->validate();

            Auth::user()->tasks()->create([
                'user_id' => Auth::id(),
                'title' => Purify::clean($this->title),
                'slug' => $this->slug,
                'desc' => Purify::clean($this->desc),
                'due' => $this->due,
                'priority' => $this->priority,
                'completed' => false,
            ]);

            $this->reset(['title', 'desc', 'due', 'priority', 'slug']);
            Flux::modal('addTask')->close();
            $this->dispatch('task-created');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Log validation errors
            Log::error('Validation failed: '.json_encode($e->errors()));
            throw $e;
        } catch (\Exception $e) {
            Log::error('Create task failed: '.$e->getMessage());
            throw $e;
        }
    }

    public function edit($id)
    {
        Log::debug("Edit ($id)");

        $this->editItem = Task::findOrFail($id);

        $this->title = Purify::clean($this->editItem->title);
        $this->desc = Purify::clean($this->editItem->desc);
        $this->due = $this->editItem->due;
        $this->priority = $this->editItem->priority;

        Flux::modal('addTask')->show();
    }

    public function update()
    {
        // edit/update an existing task
        Log::debug('update');

        $this->dispatch('task-updated');
    }

    public function delete($id)
    {
        Log::debug("Delete ($id)");

        Task::findOrFail($id)->delete();

        Flux::toast('Task Successfully Removed', heading: 'Success', variant: 'success');

        $this->dispatch('task-deleted');

    }

    public function updated(string $name, mixed $value): void
    {
        // dd($name, $value);
    }

    public function toggleCompleted($id)
    {
        $task = Task::findOrFail($id);

        $task->update([
            'completed' => ! $task->completed,
        ]);
    }

    #[Computed()]
    public function tasks(): LengthAwarePaginator
    {
        // Get the right method name based on the activeFilter property
        $methodName = 'show'.ucfirst($this->activeFilter);

        // If the method exists, use it, otherwise fall back to showAll
        if (method_exists($this, $methodName)) {
            return $this->$methodName();
        }

        return $this->showAll();
    }

    public function render()
    {
        return view('livewire.todo-list');
    }

    public function getListeners(): array
    {
        return [
            'task-updated' => '$refresh',
            'task-created' => '$refresh',
            'task-deleted' => '$refresh',
            'task-list-refresh' => '$refresh', // Add this line for the import functionality

        ];
    }

    // Modify the baseQuery to include the priority filter
    private function baseQuery()
    {
        $query = Auth::user()->tasks()
            ->where(function ($query) {
                $query->where('title', 'like', '%'.$this->needle.'%')
                    ->orWhere('desc', 'like', '%'.$this->needle.'%');
            });

        // Add priority filter if one is selected
        if (! empty($this->activePriorityFilter)) {
            $query->where('priority', $this->activePriorityFilter);
        }

        return $query;
    }

    public function showAll()
    {
        return $this->baseQuery()
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->tableLength);
    }

    public function showActive()
    {
        return $this->baseQuery()
            ->where('completed', false)
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->tableLength);
    }

    public function showCompleted()
    {
        return $this->baseQuery()
            ->where('completed', true)
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->tableLength);
    }

    public function showUncompleted()
    {
        return $this->baseQuery()
            ->where('completed', false)
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->tableLength);
    }

    public function showOverdue()
    {
        return $this->baseQuery()
            ->where('due', '<', now())
            ->where('completed', false)
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->tableLength);
    }

    public function showToday()
    {
        return $this->baseQuery()
            ->where('due', now())
            ->where('completed', false)
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->tableLength);
    }

    public function showThisWeek()
    {
        return $this->baseQuery()
            ->where('due', '>=', now()->startOfWeek())
            ->where('due', '<=', now()->endOfWeek())
            ->where('completed', false)
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->tableLength);
    }

    public function showThisMonth()
    {
        return $this->baseQuery()
            ->where('due', '>=', now()->startOfMonth())
            ->where('due', '<=', now()->endOfMonth())
            ->where('completed', false)
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->tableLength);
    }

    public function showThisYear()
    {
        return $this->baseQuery()
            ->where('due', '>=', now()->startOfYear())
            ->where('due', '<=', now()->endOfYear())
            ->where('completed', false)
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->tableLength);
    }

    public function showNext7Days()
    {
        return $this->baseQuery()
            ->where('due', '>=', now())
            ->where('due', '<=', now()->addDays(7))
            ->where('completed', false)
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->tableLength);
    }

    public function showNext30Days()
    {
        return $this->baseQuery()
            ->where('due', '>=', now())
            ->where('due', '<=', now()->addDays(30))
            ->where('completed', false)
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->tableLength);
    }

    public function showNext90Days()
    {
        return $this->baseQuery()
            ->where('due', '>=', now())
            ->where('due', '<=', now()->addDays(90))
            ->where('completed', false)
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate($this->tableLength);
    }

    // Fix the setFilter method - it's currently returning a query but should be setting a property
    public function setFilter(string $filter)
    {
        $this->activeFilter = $filter;
        $this->resetPage();
    }

    public function updatedNeedle()
    {
        // Reset pagination when search term changes
        $this->resetPage();
    }

    // Update the activePriorityFilter method
    public function updatedActivePriorityFilter()
    {
        $this->resetPage();
    }

    public function activePriorityFilter($priority)
    {
        $this->priority = $priority;
        $this->activeFilter = 'active';
        $this->resetPage();
    }

    public function showCard($id)
    {
        Log::debug("Show Card ($id)");

        $this->viewItem = Task::findOrFail($id);

        $this->slug = $this->viewItem->slug;
        $this->title = Purify::clean($this->viewItem->title);
        $this->desc = Purify::clean($this->viewItem->desc);
        $this->due = $this->viewItem->due;
        $this->priority = $this->viewItem->priority;

        Flux::modal('addTask')->show();
    }

    public function closeTaskWindow()
    {
        Log::debug('Close Task Window');
        $this->viewItem = null;
        $this->editItem = null;
        Flux::modals()->close();

        $this->reset(['title', 'desc', 'due', 'priority', 'slug']);
        Flux::modal('addTask')->close();
    }
}
