<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Category extends Model
{
    protected $fillable = [
        'term',
        'scheme',
        'label',
    ];

    /**
     * @return MorphToMany
     */
    public function feeds()
    {
        return $this->morphedByMany(Feed::class, 'taggable');
    }

    /**
     * @return MorphToMany
     */
    public function items()
    {
        return $this->morphedByMany(Item::class, 'taggable');
    }
}
