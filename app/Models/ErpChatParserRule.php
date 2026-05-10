<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ErpChatParserRule extends Model
{
    protected $fillable = [
        'name',
        'intent_key',
        'keywords',
        'match_mode',
        'priority',
        'is_active',
        'notes',
        'response_text',
    ];

    protected $casts = [
        'keywords' => 'array',
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];
}
