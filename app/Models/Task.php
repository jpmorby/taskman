<?php

namespace App\Models;

use App\Enums\PriorityLevel;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str; // Make sure the namespace is correct
use Stevebauman\Purify\Facades\Purify;

/**
 * Larastan reads column types from the migration, which does not describe two
 * things about this model: `desc` can legitimately be null on an unsaved
 * instance, and the two rendered forms below are accessors with no column.
 *
 * @property string|null $desc
 * @property-read string $desc_html
 * @property-read string $desc_preview
 */
class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'desc',
        'due',
        'priority',
        'user_id',
        'uuid',
        'completed',
        'slug',
    ];

    // Add proper casting for the priority field using the correct namespace
    protected $casts = [
        'priority' => PriorityLevel::class,
        'due' => 'datetime',
        'completed' => 'boolean',
    ];

    // Boot method to automatically generate UUIDs
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($task) {
            $task->uuid = $task->uuid ?? Str::uuid();
        });
    }

    /**
     * The description rendered for display.
     *
     * Sanitising happens here rather than on write. `desc` is markdown source,
     * not HTML: running it through Purify on the way in mangles legitimate
     * content — `&` becomes `&amp;` and an autolink like <https://example.com>
     * is deleted outright — and that loss is not recoverable. So the column
     * keeps exactly what the user typed and every render goes through this one
     * accessor, which strips raw HTML in the markdown pass and then purifies
     * the HTML that CommonMark itself produced.
     */
    protected function descHtml(): Attribute
    {
        return Attribute::get(fn (): string => $this->desc === null
            ? ''
            : Purify::clean(Str::markdown($this->desc, [
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ])));
    }

    /**
     * A plain-text truncation of the description, for collapsed table rows.
     *
     * Derived from the rendered HTML so that rows written before sanitisation
     * moved to render time — which hold entity-encoded text — do not come out
     * double-encoded when Blade escapes them.
     */
    protected function descPreview(): Attribute
    {
        return Attribute::get(fn (): string => Str::limit(
            trim(html_entity_decode(strip_tags($this->desc_html), ENT_QUOTES | ENT_HTML5)),
            50,
        ));
    }

    // Your existing relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
