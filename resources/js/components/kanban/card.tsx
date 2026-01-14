interface KanbanCardProps {
    id: string;
    title: string;
    description?: string;
}

export function KanbanCard({ title, description }: KanbanCardProps) {
    return (
        <div className='rounded-lg border bg-card p-3 shadow-sm hover:shadow-md transition-shadow'>
            <h3 className='font-medium text-sm'>{title}</h3>
            {description && <p className='mt-1 text-xs text-muted-foreground'>{description}</p>}
        </div>
    );
}
