<?php

namespace App\Models\Chantiers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChecklistSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'checklist_template_id',
        'chantier_task_id',
        'submitted_by',
        'data',
        'signature_path',
    ];

    protected function casts(): array
    {
        return [
            'data' => \Illuminate\Database\Eloquent\Casts\AsArrayObject::class,
        ];
    }

    public function template()
    {
        return $this->belongsTo(ChecklistTemplate::class, 'checklist_template_id');
    }

    public function task()
    {
        return $this->belongsTo(ChantierTask::class, 'chantier_task_id');
    }

    public function submitter()
    {
        return $this->belongsTo(\App\Models\User::class, 'submitted_by');
    }
}
