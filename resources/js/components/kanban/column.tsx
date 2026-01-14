import { KanbanCard } from './card';

interface Card {
    id: string;
    title: string;
    description?: string;
}

interface KanbanColumnProps {
    id: string;
    title: string;
    cards: Card[];
}

export function KanbanColumn({ title, cards }: KanbanColumnProps) {
    return (
        <div className='flex flex-col rounded-lg border bg-muted/50 p-3'>
            <div className='mb-3 flex items-center justify-between'>
                <h2 className='font-semibold text-sm'>{title}</h2>
                <span className='text-xs text-muted-foreground'>{cards.length}</span>
            </div>
            <div className='flex flex-col gap-2'>
                {cards.map((card) => (
                    <KanbanCard key={card.id} id={card.id} title={card.title} description={card.description} />
                ))}
            </div>
        </div>
    );
}
