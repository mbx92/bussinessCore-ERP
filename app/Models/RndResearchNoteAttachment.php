<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RndResearchNoteAttachment extends Model
{
    protected $fillable = [
        'rnd_research_note_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
    ];

    public function note()
    {
        return $this->belongsTo(RndResearchNote::class, 'rnd_research_note_id');
    }
}
