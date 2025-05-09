<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = [
        'name',
        'description',
        'status',
        'collection_id'
    ];

    public function collection()
    {
        return $this->belongsTo(Collection::class);
    }
}
