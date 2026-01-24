import { Task } from '@/types';
import { formatDistanceToNow } from 'date-fns';
import { Link } from '@inertiajs/react';
import { AgentProgressIndicatorCompact } from '@/components/agent/agent-progress-indicator';

interface KanbanCardProps {
    task: Task;
}

export function KanbanCard({ task }: KanbanCardProps) {
    const truncateText = (text: string | null, maxLength: number) => {
        if (!text) return '';
        if (text.length <= maxLength) return text;
        return text.substring(0, maxLength) + '...';
    };

    return (
        <Link href={`/tasks/${task.id}`} className='block'>
            <div className='rounded-lg border bg-card p-3 shadow-sm hover:shadow-md transition-shadow cursor-pointer'>
                <div className='flex items-start justify-between gap-2'>
                    <h3 className='font-medium text-sm flex-1'>{task.title}</h3>
                    <AgentProgressIndicatorCompact status={task.status} />
                </div>
                {task.description && <p className='mt-1 text-xs text-muted-foreground line-clamp-2'>{truncateText(task.description, 100)}</p>}
                <div className='mt-2 flex items-center justify-between text-xs text-muted-foreground'>
                    <span>
                        {formatDistanceToNow(new Date(task.created_at), {
                            addSuffix: true,
                        })}
                    </span>
                </div>
            </div>
        </Link>
    );
}
