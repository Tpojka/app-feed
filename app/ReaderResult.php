<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ReaderResult extends Model
{
    protected $fillable = [
        'modified_since',
        'date',
        'document_content',
        'url',
    ];

    /**
     * @return HasOne
     */
    public function feed()
    {
        return $this->hasOne(Feed::class);
    }
}
