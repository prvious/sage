import { Task } from '@/types';
import { formatDistanceToNow } from 'date-fns';

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
        <div className='rounded-lg border bg-card p-3 shadow-sm hover:shadow-md transition-shadow cursor-pointer'>
            <h3 className='font-medium text-sm'>{task.title}</h3>
            {task.description && <p className='mt-1 text-xs text-muted-foreground line-clamp-2'>{truncateText(task.description, 100)}</p>}
            <div className='mt-2 flex items-center justify-between text-xs text-muted-foreground'>
                <span>{formatDistanceToNow(new Date(task.created_at), { addSuffix: true })}</span>
            </div>
        </div>
    );
}
