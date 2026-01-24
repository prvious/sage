import { ReactNode } from 'react';
import { SidebarInset, SidebarProvider } from '@/components/ui/sidebar';
import { AppSidebar } from './app-sidebar';
import { QuickTaskProvider } from '@/contexts/quick-task-context';
import { GlobalQuickTaskDialog } from '@/components/global-quick-task-dialog';
import { Toaster } from 'sonner';

interface AppLayoutProps {
    children: ReactNode;
}

export function AppLayout({ children }: AppLayoutProps) {
    return (
        <QuickTaskProvider>
            <div className='flex h-screen bg-background'>
                <SidebarProvider>
                    <AppSidebar />
                    <SidebarInset>
                        <main className='flex-1 overflow-auto'>{children}</main>
                    </SidebarInset>
                </SidebarProvider>
                <GlobalQuickTaskDialog />
                <Toaster position='top-right' />
            </div>
        </QuickTaskProvider>
    );
}
