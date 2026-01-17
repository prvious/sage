<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    /** @use HasFactory<\Database\Factories\TaskFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'project_id',
        'worktree_id',
        'title',
        'description',
        'status',
        'agent_type',
        'model',
        'agent_output',
        'started_at',
        'completed_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => \App\Enums\TaskStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Get the project that owns this task.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the worktree that owns this task.
     */
    public function worktree(): BelongsTo
    {
        return $this->belongsTo(Worktree::class);
    }

    /**
     * Get the commits for this task.
     */
    public function commits(): HasMany
    {
        return $this->hasMany(Commit::class);
    }

    /**
     * Check if the task is currently running.
     */
    public function isRunning(): bool
    {
        return $this->status === \App\Enums\TaskStatus::InProgress &&
               $this->started_at !== null &&
               $this->completed_at === null;
    }

    /**
     * Scope a query to only include tasks with a given status.
     */
    public function scopeByStatus($query, \App\Enums\TaskStatus $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include tasks for a specific project.
     */
    public function scopeForProject($query, int $projectId)
    {
        return $query->where('project_id', $projectId);
    }
}
