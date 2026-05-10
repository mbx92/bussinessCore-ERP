<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceSendLog extends Model
{
    protected $fillable = [
        'project_id',
        'invoice_number',
        'recipient_email',
        'status',
        'message',
        'sent_by',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];
}
