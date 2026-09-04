<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialIdentity extends Model
{
    protected $fillable = ['user_id', 'provider_name', 'provider_id'];

    /**
     * May resolve to null for rows created before the cascading foreign key was
     * added, if the account they belonged to was deleted. Callers must handle that.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
