<?php

namespace App\ERP\CRM\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmActivity extends Model
{
    protected $table = 'crm_activities';

    protected $fillable = [
        'type',
        'subject',
        'description',
        'activity_date',
        'next_action_date',
        'next_action_note',
        'status',
        'crm_lead_id',
        'crm_customer_id',
        'crm_pipeline_id',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'activity_date' => 'datetime',
            'next_action_date' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(CrmLead::class, 'crm_lead_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(CrmCustomer::class, 'crm_customer_id');
    }

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(CrmPipeline::class, 'crm_pipeline_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
