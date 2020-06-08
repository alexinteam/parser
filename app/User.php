<?php declare(strict_types=1);

namespace App;

use Illuminate\Database\Eloquent\Model;

class User extends Model {


    protected $casts = [
        'metadata' => 'array'
    ];

    protected $fillable = [
        'metadata',
    ];

    /**
     * Scope a query to only include active users.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('active', 1);
    }

    /**
     * Scope a query to only include parsed users.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeParsed($query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('parsed', 1);
    }

    /**
     * Scope a query to only include only not parsed users.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNotParsed($query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('parsed', 0);
    }

    public function scopeAgeNotSet($query) {
        return $query->where('age', 0);
    }

}