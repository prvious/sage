import { AppLayout } from '@/components/layout/app-layout';
import { KanbanBoard } from '@/components/kanban/board';

interface Project {
    id: number;
    name: string;
    path: string;
}

interface DashboardProps {
    projects: {
        data: Project[];
    };
}

export default function Dashboard({ projects }: DashboardProps) {
    return (
        <AppLayout projects={projects.data}>
            <KanbanBoard />
        </AppLayout>
    );
}
