import { ReactNode, useState } from 'react';
import { ProjectSidebar } from './project-sidebar';
import { Sidebar, SidebarHeader, SidebarProvider } from '@/components/ui/sidebar';
import { AppSidebar } from './app-sidebar';

interface AppLayoutProps {
    children: ReactNode;
    projects: Array<{
        id: number;
        name: string;
        path: string;
    }>;
}

export function AppLayout({ children, projects }: AppLayoutProps) {
    const [isMainSidebarCollapsed, setIsMainSidebarCollapsed] = useState(false);

    return (
        <div className='flex h-screen bg-background'>
            <SidebarProvider>
                <AppSidebar />
            </SidebarProvider>

            <main className='flex-1 overflow-auto'>{children}</main>
        </div>
    );
}
