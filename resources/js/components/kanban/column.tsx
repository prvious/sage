import { Task } from '@/types';
import { KanbanCard } from './card';

interface KanbanColumnProps {
    id: string;
    title: string;
    cards: Task[];
}

export function KanbanColumn({ id, title, cards }: KanbanColumnProps) {
    return (
        <div className='flex flex-col h-full rounded-lg border bg-muted/50'>
            {/* Fixed header */}
            <div className='flex-shrink-0 p-3 pb-2 border-b border-border/50'>
                <h2 className='font-semibold text-sm'>
                    {title} ({cards.length})
                </h2>
            </div>

            {/* Scrollable content */}
            <div className='flex-1 overflow-y-auto p-3 pt-2' data-column={id}>
                <div className='flex flex-col gap-2'>
                    {cards.length > 0 ? (
                        cards.map((task) => <KanbanCard key={task.id} task={task} />)
                    ) : (
                        <p className='text-xs text-muted-foreground text-center py-4'>No tasks</p>
                    )}
                </div>
            </div>
        </div>
    );
}
