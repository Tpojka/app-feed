<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Feed extends Model
{
    protected $fillable = [
        'reader_result_id',
        'url',
        'language',
        'logo',
        'title',
        'public_id',
        'description',
        'last_modified',
        'link',
        'host',
    ];

    protected $with = [
        'reader_result',
        'categories',// not so expensive although children:many shouldn't be eager loaded by default
    ];

    /**
     * @return BelongsTo
     */
    public function readerResult()
    {
        return $this->belongsTo(ReaderResult::class);
    }

    /**
     * @return MorphToMany
     */
    public function categories()
    {
        return $this->morphToMany(Category::class, 'categoryable');
    }
}
