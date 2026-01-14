<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Worktree extends Model
{
    /** @use HasFactory<\Database\Factories\WorktreeFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'project_id',
        'branch_name',
        'path',
        'preview_url',
        'status',
        'database_isolation',
        'error_message',
        'env_overrides',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'env_overrides' => 'array',
        ];
    }

    /**
     * Get the project that owns this worktree.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the tasks for this worktree.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
