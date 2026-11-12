<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A user opened the day of a document change that brought the whole document (it was "new").
 */
class DocumentView extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'document_change_id', 'viewed_at'];

    protected function casts(): array
    {
        return ['viewed_at' => 'immutable_datetime'];
    }
}
