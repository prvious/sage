import { ArchiveX, CheckSquare, Command, File, FileText, GitBranch, Inbox, LayoutDashboard, Send, Settings, Trash2 } from 'lucide-react';

import { Label } from '@/components/ui/label';
import {
    Sidebar,
    SidebarContent,
    SidebarGroup,
    SidebarGroupContent,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarInput,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { Switch } from '@/components/ui/switch';
import { Link } from '@inertiajs/react';
import { useState } from 'react';
import EnvironmentController from '@/actions/App/Http/Controllers/EnvironmentController';
import SpecController from '@/actions/App/Http/Controllers/SpecController';
import WorktreeController from '@/actions/App/Http/Controllers/WorktreeController';
import TaskController from '@/actions/App/Http/Controllers/TaskController';
import { ProjectSidebar } from './project-sidebar';
import { SageLogo } from '../branding/sage-logo';

// This is sample data
const navMain = [
    {
        title: 'Inbox',
        url: '#',
        icon: Inbox,
        isActive: true,
    },
];

const navigationItems = [
    { label: 'Dashboard', icon: LayoutDashboard, href: '/dashboard' },
    { label: 'Tasks', icon: CheckSquare, href: TaskController.index() },
    { label: 'Worktrees', icon: GitBranch, href: '' },
    { label: 'Specs', icon: FileText, href: SpecController.index() },
    { label: 'Environment', icon: Settings, href: EnvironmentController.index() },
];

export function AppSidebar({ ...props }: React.ComponentProps<typeof Sidebar>) {
    const [activeItem, setActiveItem] = useState(navMain[0]);
    const { setOpen } = useSidebar();

    return (
        <Sidebar collapsible='icon' className='overflow-hidden *:data-[sidebar=sidebar]:flex-row' {...props}>
            {/* This is the first sidebar */}
            {/* We disable collapsible and adjust width to icon. */}
            {/* This will make the sidebar appear as icons. */}
            <Sidebar collapsible='none' className='w-[calc(var(--sidebar-width-icon)+1px)]! border-r'>
                <SidebarHeader>
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton
                                size='lg'
                                className='md:h-8 md:p-0'
                                render={
                                    <Link href='#'>
                                        <div className='bg-sidebar-primary text-sidebar-primary-foreground flex aspect-square size-8 items-center justify-center rounded-lg'>
                                            <SageLogo />
                                        </div>
                                        <div className='grid flex-1 text-left text-sm leading-tight'>
                                            <span className='truncate font-medium'>Acme Inc</span>
                                            <span className='truncate text-xs'>Enterprise</span>
                                        </div>
                                    </Link>
                                }
                            ></SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarHeader>
                <SidebarContent>
                    <ProjectSidebar projects={[]} />
                </SidebarContent>
            </Sidebar>

            {/* This is the second sidebar */}
            {/* We disable collapsible and let it fill remaining space */}
            <Sidebar collapsible='none' className='hidden flex-1 md:flex'>
                <SidebarHeader className='gap-3.5 border-b p-4'>
                    <div className='flex w-full items-center justify-between'>
                        <div className='text-foreground text-base font-medium'>{activeItem?.title}</div>
                    </div>
                    <SidebarInput placeholder='Type to search...' />
                </SidebarHeader>

                <SidebarContent>
                    <SidebarGroup>
                        <SidebarGroupLabel>Favorites</SidebarGroupLabel>
                        <SidebarMenu>
                            {navigationItems.map((link, key) => (
                                <SidebarMenuItem>{link.label}</SidebarMenuItem>
                            ))}
                        </SidebarMenu>
                    </SidebarGroup>
                </SidebarContent>
            </Sidebar>
        </Sidebar>
    );
}
