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
        'claude_code_api_key',
        'opencode_api_key',
        'claude_code_last_tested_at',
        'opencode_last_tested_at',
    ];

    protected function casts(): array
    {
        return [
            'claude_code_last_tested_at' => 'datetime',
            'opencode_last_tested_at' => 'datetime',
            'claude_code_api_key' => 'encrypted',
            'opencode_api_key' => 'encrypted',
        ];
    }

    public function project(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
