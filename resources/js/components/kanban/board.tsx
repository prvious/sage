import { Task } from '@/types';
import { KanbanColumn } from './column';

interface KanbanBoardProps {
    tasks: {
        queued: Task[];
        in_progress: Task[];
        waiting_review: Task[];
        done: Task[];
    };
    projectId: number;
}

const COLUMNS = [
    { id: 'queued', title: 'Queued' },
    { id: 'in_progress', title: 'In Progress' },
    { id: 'waiting_review', title: 'Waiting Review' },
    { id: 'done', title: 'Done' },
] as const;

export function KanbanBoard({ tasks, projectId }: KanbanBoardProps) {
    return (
        <div className='h-full overflow-hidden'>
            <div className='grid grid-cols-1 gap-4 md:grid-cols-4 h-full auto-rows-fr'>
                {COLUMNS.map((column) => (
                    <KanbanColumn key={column.id} id={column.id} title={column.title} cards={tasks[column.id as keyof typeof tasks]} />
                ))}
            </div>
        </div>
    );
}
