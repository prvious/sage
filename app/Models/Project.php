<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
        'server_port',
        'tls_enabled',
        'custom_domain',
        'custom_directives',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tls_enabled' => 'boolean',
        ];
    }

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

    /**
     * Get the agent settings for this project.
     */
    public function agentSetting(): HasOne
    {
        return $this->hasOne(AgentSetting::class);
    }
}
