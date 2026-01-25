import { InertiaLinkProps } from '@inertiajs/react';
import { LucideIcon } from 'lucide-react';
import Echo from 'laravel-echo';

// Pusher type definition
export interface PusherStatic {
    logToConsole?: boolean;
    [key: string]: unknown;
}

declare global {
    interface Window {
        Echo: Echo;
        Pusher: PusherStatic;
    }
}

export interface Auth {
    user: User;
}

export interface Project {
    id: number;
    name: string;
    path: string;
    server_driver: string;
    base_url: string;
    server_port?: number | null;
    tls_enabled: boolean;
    custom_domain?: string | null;
    custom_directives?: string | null;
}

export interface ServerStatus {
    driver: string;
    installed: boolean;
    running: boolean;
    version: string | null;
    worktrees_count: number;
}

export interface WorktreeOption {
    id: number;
    branch_name: string;
}

export interface FlashToast {
    type: 'success' | 'error' | 'info' | 'warning' | 'default';
    message: string;
    description?: string;
    duration?: number;
}

export interface SharedData {
    name: string;
    auth: Auth;
    sidebarOpen: boolean;
    projects: Project[];
    selectedProject: Project | null;
    selectedProjectWorktrees: WorktreeOption[];
    flash?: {
        toasts?: FlashToast[];
    };
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}

export interface RunningAgent {
    id: number;
    project_id: number;
    project_name: string;
    worktree_id: number | null;
    worktree_name: string | null;
    agent_type: string;
    model: string;
    status: string;
    started_at: string;
    agent_output: string | null;
    description: string | null;
}

export interface AgentsIndexProps extends SharedData {
    runningAgents: RunningAgent[];
}

export interface GuidelineFile {
    name: string;
    path: string;
    size: number;
    modified_at: number;
}

export interface GuidelineIndexProps extends SharedData {
    files: GuidelineFile[];
    project: Project;
}

export interface GuidelineCreateProps extends SharedData {
    project: Project;
}

export interface GuidelineEditProps extends SharedData {
    project: Project;
    filename: string;
    content: string;
}

export interface GuidelineShowProps extends SharedData {
    project: Project;
    filename: string;
    content: string;
}

export interface Task {
    id: number;
    project_id: number;
    worktree_id: number | null;
    spec_id: number | null;
    title: string;
    description: string | null;
    status: TaskStatus;
    agent_type: string | null;
    model: string | null;
    agent_output: string | null;
    started_at: string | null;
    completed_at: string | null;
    created_at: string;
    updated_at: string;
}

export interface Spec {
    id: number;
    project_id: number;
    title: string;
    content: string;
    generated_from_idea: string | null;
    created_at: string;
    updated_at: string;
}

export type TaskStatus = 'queued' | 'in_progress' | 'waiting_review' | 'done' | 'failed';

export interface Commit {
    sha: string;
    message: string;
    author: string;
    created_at: string;
}

export type WorktreeStatus = 'creating' | 'active' | 'error' | 'cleaning_up';
export type DatabaseIsolation = 'separate' | 'prefix' | 'shared';

export interface Worktree {
    id: number;
    project_id: number;
    branch_name: string;
    path: string;
    preview_url: string;
    status: WorktreeStatus;
    database_isolation: DatabaseIsolation;
    error_message?: string | null;
    created_at: string;
    updated_at: string;
}

export interface TaskWithDetails extends Task {
    project: {
        id: number;
        name: string;
    } | null;
    worktree: {
        id: number;
        branch_name: string;
    } | null;
    commits: Commit[];
}

export interface TaskShowProps extends SharedData {
    task: TaskWithDetails;
}

export interface OutputLine {
    content: string;
    type: 'stdout' | 'stderr';
    timestamp?: string;
}

// Brainstorm Idea structure
export interface BrainstormIdea {
    title: string;
    description: string;
    category: string;
    priority: string;
    [key: string]: unknown;
}

// Brainstorm event types
export interface BrainstormCompletedEvent {
    message: string;
    brainstorm_id: number;
    ideas_count?: number;
}

export interface BrainstormFailedEvent {
    message: string;
    brainstorm_id: number;
    error?: string;
}
