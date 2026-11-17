<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeAgreement extends Model
{
    protected $casts = [
        'schedule1_snapshot' => 'array',
        'fwis_attached'      => 'boolean',
        'ceis_attached'      => 'boolean',
        'employee_signed_at' => 'datetime',
        'employer_signed_at' => 'datetime',
        'commencement_date'  => 'date',
    ];

    protected $fillable = [
        'staff_id', 'user_id', 'created_by', 'agreement_number',
        'employee_name', 'employee_address', 'employee_phone', 'employee_email',
        'employment_type', 'classification_level', 'security_license_no',
        'guaranteed_hours', 'commencement_date', 'schedule1_snapshot',
        'fwis_attached', 'ceis_attached',
        'pdf_path', 'signed_pdf_path', 'signing_token', 'status',
        'employee_signature_name', 'employee_signature_image_path',
        'employee_signed_at', 'employee_signed_ip',
        'employer_signature_name', 'employer_signature_image_path',
        'employer_signed_at', 'employer_signed_by', 'employer_signed_ip',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isFullyExecuted(): bool
    {
        return $this->status === 'fully_executed';
    }
}
