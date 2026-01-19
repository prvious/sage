<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentSetting extends Model
{
    /** @use HasFactory<\Database\Factories\AgentSettingFactory> */
    use HasFactory;

    protected $fillable = [
        'project_id',
        'default_agent',
    ];

    protected function casts(): array
    {
        return [];
    }

    public function project(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
