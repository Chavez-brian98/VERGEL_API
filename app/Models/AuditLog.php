<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $table = 'audit_log';

    public $timestamps = false;

    protected $fillable = [
        'employee_id',
        'affected_table',
        'affected_record_id',
        'action',
        'old_data',
        'new_data',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'affected_record_id' => 'integer',
            'old_data' => 'array',
            'new_data' => 'array',
        ];
    }
}
