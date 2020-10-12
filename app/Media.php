<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Media extends Model
{
    protected $fillable = [
        'item_id',
        'node_name',
        'type',
        'url',
        'length',
        'title',
        'description',
        'thumbnail',
    ];

    protected $with = [
        'item'
    ];

    /**
     * @return BelongsTo
     */
    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
