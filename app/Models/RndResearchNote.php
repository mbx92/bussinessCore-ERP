<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RndResearchNote extends Model
{
    protected $fillable = [
        'rnd_project_id',
        'title',
        'content',
        'created_by',
    ];

    public function project()
    {
        return $this->belongsTo(RndProject::class, 'rnd_project_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attachments()
    {
        return $this->hasMany(RndResearchNoteAttachment::class)->latest();
    }
}
