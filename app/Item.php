<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use willvincent\Rateable\Rateable;

class Item extends Model
{
    use Rateable;

    protected $fillable = [
        'feed_id',
        'title',
        'public_id',
        'description',
        'last_modified',
        'link',
        'host',
    ];

    protected $with = [
        'feed',
        'categories',// not so expensive in this particular use although children:many shouldn't be eager loaded by default
    ];

    protected $appends = [
        'author_signature',
        'first_media',
        'avg_rating'
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
     * Gets first available item's author signature (name||uri||email) or returns null
     *
     * @return string|null
     */
    public function getAuthorSignatureAttribute(): ?string
    {
        $return = null;

        try {
            if ($this->author()->isEmpty()) {
                throw new \Exception('No author');
            }
            $return = array_filter($this->author()->first()->getAttributes(), function ($v, $k) {
                return in_array($k, ['name', 'uri', 'email']) && !is_null($v);
            }, ARRAY_FILTER_USE_BOTH);

            if (!is_null($return)) {
                $return = array_shift($return);
            }
        } catch (\Throwable $t) {
            $return = null;
        } finally {
            return $return;
        }
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
        return $this->media()->whereNotNull('url')->latest()->first();
    }

    public function getAvgRatingAttribute()
    {
        $return = null;

        if ($this->averageRating()) {
            $return = (int)round($this->averageRating());
        }

        return $return;
    }
}
