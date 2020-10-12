<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Author extends Model
{
    protected $fillable = [
        'item_id',
        'name',
        'uri',
        'email',
    ];

    protected $with = [
        'item',
    ];

    /**
     * @return BelongsTo
     */
    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
