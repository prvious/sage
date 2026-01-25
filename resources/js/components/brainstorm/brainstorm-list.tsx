import { BrainstormCard } from './brainstorm-card';
import { BrainstormIdea } from '@/types';

interface Brainstorm {
    id: number;
    project_id: number;
    user_context: string | null;
    ideas: BrainstormIdea[] | null;
    status: 'pending' | 'processing' | 'completed' | 'failed';
    error_message: string | null;
    created_at: string;
    completed_at: string | null;
}

interface BrainstormListProps {
    brainstorms: Brainstorm[];
}

export function BrainstormList({ brainstorms }: BrainstormListProps) {
    if (brainstorms.length === 0) {
        return (
            <div className='text-center py-12'>
                <p className='text-muted-foreground'>No brainstorm sessions yet. Create your first one above!</p>
            </div>
        );
    }

    return (
        <div className='grid gap-4 md:grid-cols-2 lg:grid-cols-3'>
            {brainstorms.map((brainstorm) => (
                <BrainstormCard key={brainstorm.id} brainstorm={brainstorm} />
            ))}
        </div>
    );
}
