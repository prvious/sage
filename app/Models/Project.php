<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'path',
        'server_driver',
        'base_url',
    ];

    /**
     * Get the worktrees for this project.
     */
    public function worktrees(): HasMany
    {
        return $this->hasMany(Worktree::class);
    }

    /**
     * Get the tasks for this project.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Get the specs for this project.
     */
    public function specs(): HasMany
    {
        return $this->hasMany(Spec::class);
    }
}
