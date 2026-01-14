import { KanbanColumn } from './column';

interface Card {
    id: string;
    title: string;
    description?: string;
}

interface Column {
    id: string;
    title: string;
    cards: Card[];
}

const dummyColumns: Column[] = [
    {
        id: 'todo',
        title: 'To Do',
        cards: [
            {
                id: '1',
                title: 'Add user authentication',
                description: 'Implement login/logout',
            },
            {
                id: '2',
                title: 'Create API endpoints',
                description: 'RESTful API for tasks',
            },
        ],
    },
    {
        id: 'in-progress',
        title: 'In Progress',
        cards: [
            {
                id: '3',
                title: 'Setup database migrations',
                description: 'Create initial schema',
            },
        ],
    },
    {
        id: 'done',
        title: 'Done',
        cards: [
            {
                id: '4',
                title: 'Initialize project',
                description: 'Laravel + React setup',
            },
        ],
    },
];

export function KanbanBoard() {
    return (
        <div className='h-full p-6'>
            <h1 className='mb-6 text-2xl font-bold'>Tasks</h1>
            <div className='grid grid-cols-1 gap-4 md:grid-cols-3'>
                {dummyColumns.map((column) => (
                    <KanbanColumn key={column.id} id={column.id} title={column.title} cards={column.cards} />
                ))}
            </div>
        </div>
    );
}
