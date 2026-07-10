<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Image extends Model
{
    protected $fillable = ['path',];

    protected $appends = [ 'url'];
    public function imageable()
    {
        return $this->morphTo();
    }

    public function getUrlAttribute()
{
    return asset(Storage::url($this->path));
}
}
