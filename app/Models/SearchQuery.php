<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchQuery extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'search_queries';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'query',
        'module',
    ];

    /**
     * Get the user that owns the search query.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
