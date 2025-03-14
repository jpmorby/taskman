<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;
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