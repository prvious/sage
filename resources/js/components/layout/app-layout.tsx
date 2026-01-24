import { ReactNode } from 'react';
import { SidebarInset, SidebarProvider } from '@/components/ui/sidebar';
import { AppSidebar } from './app-sidebar';

interface AppLayoutProps {
    children: ReactNode;
}

export function AppLayout({ children }: AppLayoutProps) {
    return (
        <div className='flex h-screen bg-background'>
            <SidebarProvider>
                <AppSidebar />
                <SidebarInset>
                    <main className='flex-1 overflow-auto'>{children}</main>
                </SidebarInset>
            </SidebarProvider>
        </div>
    );
}
