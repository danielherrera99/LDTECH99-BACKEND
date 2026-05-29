<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QueryResultCache extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'query_results_cache';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'module',
        'query',
        'result_data',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'result_data' => 'array',
        ];
    }
}
