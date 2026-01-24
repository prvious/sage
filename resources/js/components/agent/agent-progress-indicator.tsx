import { cn } from '@/lib/utils';
import { Badge } from '@/components/ui/badge';
import { Bot, CheckCircle2, Clock, Loader2, XCircle, AlertCircle } from 'lucide-react';

type TaskStatus = 'queued' | 'in_progress' | 'waiting_review' | 'done' | 'failed';

interface AgentProgressIndicatorProps {
    status: TaskStatus;
    currentTask?: string;
    startedAt?: string | null;
    completedAt?: string | null;
    className?: string;
    showDuration?: boolean;
}

export function AgentProgressIndicator({ status, currentTask, startedAt, completedAt, className, showDuration = true }: AgentProgressIndicatorProps) {
    const getStatusConfig = (status: TaskStatus) => {
        switch (status) {
            case 'queued':
                return {
                    icon: Clock,
                    iconClassName: 'text-muted-foreground',
                    label: 'Queued',
                    badgeVariant: 'outline' as const,
                    description: 'Waiting to start...',
                    animate: false,
                };
            case 'in_progress':
                return {
                    icon: Loader2,
                    iconClassName: 'text-blue-500',
                    label: 'Running',
                    badgeVariant: 'default' as const,
                    description: currentTask || 'Agent is working...',
                    animate: true,
                };
            case 'waiting_review':
                return {
                    icon: AlertCircle,
                    iconClassName: 'text-amber-500',
                    label: 'Review',
                    badgeVariant: 'secondary' as const,
                    description: 'Waiting for review',
                    animate: false,
                };
            case 'done':
                return {
                    icon: CheckCircle2,
                    iconClassName: 'text-green-500',
                    label: 'Done',
                    badgeVariant: 'secondary' as const,
                    description: 'Task completed successfully',
                    animate: false,
                };
            case 'failed':
                return {
                    icon: XCircle,
                    iconClassName: 'text-red-500',
                    label: 'Failed',
                    badgeVariant: 'destructive' as const,
                    description: 'Task failed',
                    animate: false,
                };
            default:
                return {
                    icon: Bot,
                    iconClassName: 'text-muted-foreground',
                    label: 'Unknown',
                    badgeVariant: 'outline' as const,
                    description: 'Unknown status',
                    animate: false,
                };
        }
    };

    const calculateDuration = () => {
        if (!startedAt) return null;

        const start = new Date(startedAt);
        const end = completedAt ? new Date(completedAt) : new Date();
        const diffMs = end.getTime() - start.getTime();

        const seconds = Math.floor(diffMs / 1000);
        const minutes = Math.floor(seconds / 60);
        const hours = Math.floor(minutes / 60);

        if (hours > 0) {
            return `${hours}h ${minutes % 60}m`;
        } else if (minutes > 0) {
            return `${minutes}m ${seconds % 60}s`;
        } else {
            return `${seconds}s`;
        }
    };

    const config = getStatusConfig(status);
    const Icon = config.icon;
    const duration = showDuration ? calculateDuration() : null;

    return (
        <div className={cn('flex items-center gap-3', className)}>
            <div className={cn('relative flex items-center justify-center', config.animate && 'animate-pulse')}>
                <Icon className={cn('h-5 w-5', config.iconClassName, config.animate && 'animate-spin')} />
            </div>
            <div className='flex flex-col min-w-0'>
                <div className='flex items-center gap-2'>
                    <Badge variant={config.badgeVariant}>{config.label}</Badge>
                    {duration && status !== 'queued' && <span className='text-xs text-muted-foreground'>{duration}</span>}
                </div>
                <p className='text-xs text-muted-foreground mt-0.5 truncate'>{config.description}</p>
            </div>
        </div>
    );
}

// Compact version for use in cards/lists
export function AgentProgressIndicatorCompact({ status, className }: { status: TaskStatus; className?: string }) {
    const getStatusConfig = (status: TaskStatus) => {
        switch (status) {
            case 'queued':
                return {
                    icon: Clock,
                    className: 'text-muted-foreground',
                    animate: false,
                };
            case 'in_progress':
                return {
                    icon: Loader2,
                    className: 'text-blue-500',
                    animate: true,
                };
            case 'waiting_review':
                return {
                    icon: AlertCircle,
                    className: 'text-amber-500',
                    animate: false,
                };
            case 'done':
                return {
                    icon: CheckCircle2,
                    className: 'text-green-500',
                    animate: false,
                };
            case 'failed':
                return {
                    icon: XCircle,
                    className: 'text-red-500',
                    animate: false,
                };
            default:
                return {
                    icon: Bot,
                    className: 'text-muted-foreground',
                    animate: false,
                };
        }
    };

    const config = getStatusConfig(status);
    const Icon = config.icon;

    return <Icon className={cn('h-4 w-4', config.className, config.animate && 'animate-spin', className)} />;
}
