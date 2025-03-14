<?php

namespace App\Livewire;

use Auth;
use Carbon\Traits\Timestamp;
use Exception;
use Flux\Flux;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Attributes\Rule;
use Livewire\Component;
use App\Models\Task;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class TodoList extends Component
{
    use WithPagination;

    #[Rule('required|min:5|max:250')]
    public $title;
    #[Rule('required')]
    public string $desc;

    #[Rule('required')]
    public $priority;
    #[Rule('required|string|max:255')]
    public string $slug;
    public array $media;
    #[Rule("date|required")]
    public $due;
    public bool $completed = false;
    public $editItem;
    public $user_id;
    public $tasks;

    #[Rule('string|min:3')]
    public $needle = '';

    public $searchResults;
    public $sortBy = 'due';
    public $sortDirection = 'asc';

    public function mount()
    {
        // $this->fetch();
        // dd(Auth::check());

        $this->user_id = Auth::id();
    }
    public function sort($index)
    {
        $this->sortBy = $index;
        $this->render();
    }
    public function create()
    {
        Log::info("create");

        $this->slug = Str::of($this->title)->slug();

        Log::debug("Slug: $this->slug");
        $this->priority = 'LOW';
        $this->validate();

        auth()->user()->tasks()->create([
            'user_id'   => auth()->id(),
            'title'     => $this->title,
            'slug'      => $this->slug,
            'desc'      => $this->desc,
            'due'       => $this->due,
            'completed' => false
        ]);

        $this->reset('title', 'desc', 'due');
        Flux::modal("addTask")->close();
        $this->dispatch('task-created');
        $this->render();

    }

    public function delete($id)
    {
        Log::info("Delete ($id)");
        Task::findOrFail($id)->delete();
        // delete a task from the db
        $this->dispatch('task-deleted');

    }

    public function update()
    {
        // edit/update an existing task
        Log::info("update");

        $this->dispatch('task-updated');
    }

    public function store()
    {
        $this->dispatch('task-updated');

        // save record into db
        Log::info("Store");
    }

    public function fetch(int $records = 100)
    {
        // fetch records from the DB -- perhaphs paginate?
        // return 
        $this->tasks = Task::latest()
            ->where('title', 'like' . '%{$needle}%')
            ->paginate(10);
    }

    public function toggleCompleted($id)
    {
        debug("id $id");
        // debug("Completed: $this->completed");

        // $task = Task::findorfail($id); //->update('completed' => $completed);

        $task = auth()->user()->tasks()->findOrFail($id);
        auth()->user()->tasks()->findOrFail($id)->update([
            'completed' => !$task->completed,
        ]);


        // save the updated record
        // Task::where('id', '=', $id)->update($this);
    }
    public function find(string $needle)
    {
        // return Task::findOrFail($needle);
        $this->needle = $needle;
        $this->render();
    }
    public function render()
    {
        $this->tasks = auth()->user()->tasks()
            ->where('title', 'like', "%{$this->needle}%")
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(3);

        return view(
            'livewire.todo-list'

            // 'livewire.todo-list',
            // [
            //     'tasks' => auth()->user()->tasks()
            //         ->where('title', 'like', "%{$this->needle}%")
            //         ->orderBy($this->sortBy, $this->sortDirection)
            //         ->paginate(3)
            // ]
        );
    }

    public function addTask()
    {
        Log::info("addTask");

        Flux::modal("addTask")->show();


    }
}