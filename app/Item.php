<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Item extends Model
{
    protected $fillable = [
        'feed_id',
        'title',
        'public_id',
        'description',
        'last_modified',
        'link',
    ];

    protected $with = [
        'feed',
        'categories',// not so expensive although children:many shouldn't be eager loaded by default
    ];

    protected $appends = [
        'first_media'
    ];

    /**
     * @return BelongsTo
     */
    public function feed()
    {
        return $this->belongsTo(Feed::class);
    }

    /**
     * @return MorphToMany
     */
    public function categories()
    {
        return $this->morphToMany(Category::class, 'categoryable');
    }

    /**
     * @return HasOne
     */
    public function author()
    {
        return $this->hasOne(Author::class);
    }

    /**
     * @return HasMany
     */
    public function media()
    {
        return $this->hasMany(Media::class);
    }

    /**
     * @return Model|HasMany|object|null
     */
    public function getFirstMediaAttribute()
    {
        return $this->media()->latest()->first();
    }
}
