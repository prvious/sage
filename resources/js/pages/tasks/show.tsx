import { Head, Link, router, usePoll } from '@inertiajs/react';
import { AppLayout } from '@/components/layout/app-layout';
import { AgentOutputViewer, OutputLine } from '@/components/agent/agent-output-viewer';
import { AgentProgressIndicator } from '@/components/agent/agent-progress-indicator';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft, Bot, ExternalLink, GitCommit, Play, Square, RefreshCw } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';

interface Commit {
    sha: string;
    message: string;
    author: string;
    created_at: string;
}

interface TaskData {
    id: number;
    title: string;
    description: string | null;
    status: 'queued' | 'in_progress' | 'waiting_review' | 'done' | 'failed';
    agent_type: string | null;
    model: string | null;
    agent_output: string | null;
    started_at: string | null;
    completed_at: string | null;
    created_at: string;
    updated_at: string;
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

interface TaskShowProps {
    task: TaskData;
}

export default function TaskShow({ task }: TaskShowProps) {
    const [output, setOutput] = useState<OutputLine[]>([]);
    const [isRefreshing, setIsRefreshing] = useState(false);

    // Parse the agent_output into OutputLine format
    const parseOutput = useCallback((rawOutput: string | null): OutputLine[] => {
        if (!rawOutput) return [];
        return rawOutput.split('\n').filter(Boolean).map((line) => ({
            content: line,
            type: 'stdout' as const,
        }));
    }, []);

    useEffect(() => {
        setOutput(parseOutput(task.agent_output));
    }, [task.agent_output, parseOutput]);

    // Poll for updates when task is in progress
    const { start: startPolling, stop: stopPolling } = usePoll(
        3000,
        {
            only: ['task'],
            onFinish: () => setIsRefreshing(false),
        },
        {
            autoStart: task.status === 'in_progress',
        }
    );

    useEffect(() => {
        if (task.status === 'in_progress') {
            startPolling();
        } else {
            stopPolling();
        }
    }, [task.status, startPolling, stopPolling]);

    const handleRefresh = () => {
        setIsRefreshing(true);
        router.reload({ only: ['task'] });
    };

    const handleStop = () => {
        if (confirm('Are you sure you want to stop this agent?')) {
            router.post(
                `/tasks/${task.id}/stop`,
                {},
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        router.reload({ only: ['task'] });
                    },
                }
            );
        }
    };

    const handleStart = () => {
        // This would open a dialog to get the prompt
        // For now, just show an alert
        alert('Start functionality would open a prompt dialog');
    };

    const isRunning = task.status === 'in_progress';

    return (
        <>
            <Head title={`${task.title} - Task`} />
            <AppLayout>
                <div className='flex flex-col h-full'>
                    {/* Header */}
                    <div className='shrink-0 border-b bg-card'>
                        <div className='p-6'>
                            <div className='flex items-start justify-between'>
                                <div className='flex items-start gap-4'>
                                    <Button
                                        variant='ghost'
                                        size='icon-sm'
                                        render={
                                            <Link
                                                href={
                                                    task.project
                                                        ? `/projects/${task.project.id}/dashboard`
                                                        : '/agents'
                                                }
                                            />
                                        }
                                    >
                                        <ArrowLeft className='h-4 w-4' />
                                    </Button>
                                    <div>
                                        <div className='flex items-center gap-3'>
                                            <h1 className='text-2xl font-bold'>{task.title}</h1>
                                        </div>
                                        {task.description && (
                                            <p className='text-muted-foreground mt-1 max-w-2xl'>
                                                {task.description}
                                            </p>
                                        )}
                                        <div className='flex items-center gap-4 mt-3'>
                                            {task.project && (
                                                <Link
                                                    href={`/projects/${task.project.id}/dashboard`}
                                                    className='text-sm text-muted-foreground hover:text-foreground flex items-center gap-1'
                                                >
                                                    <ExternalLink className='h-3.5 w-3.5' />
                                                    {task.project.name}
                                                </Link>
                                            )}
                                            {task.worktree && (
                                                <Badge variant='outline'>
                                                    {task.worktree.branch_name}
                                                </Badge>
                                            )}
                                            {task.model && (
                                                <span className='text-xs text-muted-foreground'>
                                                    Model: {task.model}
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                </div>
                                <div className='flex items-center gap-2'>
                                    <Button
                                        variant='ghost'
                                        size='icon-sm'
                                        onClick={handleRefresh}
                                        disabled={isRefreshing}
                                    >
                                        <RefreshCw
                                            className={`h-4 w-4 ${isRefreshing ? 'animate-spin' : ''}`}
                                        />
                                    </Button>
                                    {isRunning ? (
                                        <Button variant='destructive' size='sm' onClick={handleStop}>
                                            <Square className='h-4 w-4 mr-2' />
                                            Stop Agent
                                        </Button>
                                    ) : task.status === 'queued' ? (
                                        <Button size='sm' onClick={handleStart}>
                                            <Play className='h-4 w-4 mr-2' />
                                            Start Agent
                                        </Button>
                                    ) : null}
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Main content */}
                    <div className='flex-1 overflow-auto p-6'>
                        <div className='grid grid-cols-1 lg:grid-cols-3 gap-6'>
                            {/* Output viewer - takes 2 columns */}
                            <div className='lg:col-span-2'>
                                <AgentOutputViewer
                                    output={output}
                                    isStreaming={isRunning}
                                    taskStatus={task.status}
                                    taskTitle={task.title}
                                    maxHeight='calc(100vh - 300px)'
                                />
                            </div>

                            {/* Sidebar with task info */}
                            <div className='space-y-6'>
                                {/* Status Card */}
                                <Card>
                                    <CardHeader>
                                        <CardTitle className='flex items-center gap-2 text-base'>
                                            <Bot className='h-4 w-4' />
                                            Agent Status
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <AgentProgressIndicator
                                            status={task.status}
                                            startedAt={task.started_at}
                                            completedAt={task.completed_at}
                                        />
                                    </CardContent>
                                </Card>

                                {/* Commits Card */}
                                {task.commits.length > 0 && (
                                    <Card>
                                        <CardHeader>
                                            <CardTitle className='flex items-center gap-2 text-base'>
                                                <GitCommit className='h-4 w-4' />
                                                Commits ({task.commits.length})
                                            </CardTitle>
                                            <CardDescription>
                                                Changes made by the agent
                                            </CardDescription>
                                        </CardHeader>
                                        <CardContent>
                                            <div className='space-y-3'>
                                                {task.commits.map((commit) => (
                                                    <div
                                                        key={commit.sha}
                                                        className='border-l-2 border-muted pl-3 py-1'
                                                    >
                                                        <p className='text-sm font-medium line-clamp-2'>
                                                            {commit.message}
                                                        </p>
                                                        <div className='flex items-center gap-2 mt-1'>
                                                            <code className='text-xs text-muted-foreground font-mono'>
                                                                {commit.sha.substring(0, 7)}
                                                            </code>
                                                            <span className='text-xs text-muted-foreground'>
                                                                by {commit.author}
                                                            </span>
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        </CardContent>
                                    </Card>
                                )}

                                {/* Task Details Card */}
                                <Card>
                                    <CardHeader>
                                        <CardTitle className='text-base'>Task Details</CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <dl className='space-y-3 text-sm'>
                                            <div>
                                                <dt className='text-muted-foreground'>Task ID</dt>
                                                <dd className='font-mono'>#{task.id}</dd>
                                            </div>
                                            {task.agent_type && (
                                                <div>
                                                    <dt className='text-muted-foreground'>
                                                        Agent Type
                                                    </dt>
                                                    <dd className='capitalize'>{task.agent_type}</dd>
                                                </div>
                                            )}
                                            <div>
                                                <dt className='text-muted-foreground'>Created</dt>
                                                <dd>
                                                    {new Date(task.created_at).toLocaleString()}
                                                </dd>
                                            </div>
                                            {task.started_at && (
                                                <div>
                                                    <dt className='text-muted-foreground'>
                                                        Started
                                                    </dt>
                                                    <dd>
                                                        {new Date(task.started_at).toLocaleString()}
                                                    </dd>
                                                </div>
                                            )}
                                            {task.completed_at && (
                                                <div>
                                                    <dt className='text-muted-foreground'>
                                                        Completed
                                                    </dt>
                                                    <dd>
                                                        {new Date(
                                                            task.completed_at
                                                        ).toLocaleString()}
                                                    </dd>
                                                </div>
                                            )}
                                        </dl>
                                    </CardContent>
                                </Card>
                            </div>
                        </div>
                    </div>
                </div>
            </AppLayout>
        </>
    );
}
