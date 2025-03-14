<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Livewire\Features\SupportPagination\HandlesPagination;
use Livewire\WithPagination;

class Task extends Model
{
    use HasFactory, WithPagination;
    //

    public function casts()
    {
        return [
            'due'       => 'date',
            'completed' => 'boolean'
        ];
    }

    // protected $guarded = [];
    protected $fillable = [
        "title",
        "desc",
        "slug",
        "user_id",
        "due",
        "completed",
        "priority",
    ];
}