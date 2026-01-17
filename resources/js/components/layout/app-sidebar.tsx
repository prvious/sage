import { Bot, FileEdit, FileText, GitBranch, LayoutDashboard, Settings, Sparkles, Terminal } from 'lucide-react';

import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { Link, usePage } from '@inertiajs/react';
import { SharedData } from '@/types';
import BrainstormController from '@/actions/App/Http/Controllers/BrainstormController';
import ContextController from '@/actions/App/Http/Controllers/ContextController';
import DashboardController from '@/actions/App/Http/Controllers/DashboardController';
import EnvironmentController from '@/actions/App/Http/Controllers/EnvironmentController';
import ProjectAgentController from '@/actions/App/Http/Controllers/ProjectAgentController';
import SettingsController from '@/actions/App/Http/Controllers/SettingsController';
import WorktreeController from '@/actions/App/Http/Controllers/WorktreeController';
import { ProjectSidebar } from './project-sidebar';

interface NavigationItem {
    label: string;
    icon: typeof GitBranch;
    href: string | { url: string; method: string };
    badge?: string | number;
}

export function AppSidebar({ ...props }: React.ComponentProps<typeof Sidebar>) {
    const { projects, selectedProject, url } = usePage<SharedData>().props;

    const navigationItems: NavigationItem[] = selectedProject
        ? [
              {
                  label: 'Dashboard',
                  icon: LayoutDashboard,
                  href: DashboardController.show(selectedProject.id),
              },
              {
                  label: 'Brainstorm',
                  icon: Sparkles,
                  href: BrainstormController.index(selectedProject.id),
              },
              {
                  label: 'Worktrees',
                  icon: GitBranch,
                  href: WorktreeController.index(selectedProject.id),
              },
              {
                  label: 'Specs',
                  icon: FileText,
                  href: `/projects/${selectedProject.id}/specs`,
              },
              {
                  label: 'Environment',
                  icon: Settings,
                  href: `/projects/${selectedProject.id}/environment`,
              },
              {
                  label: 'Terminal',
                  icon: Terminal,
                  href: '#',
              },
              {
                  label: 'Context',
                  icon: FileEdit,
                  href: ContextController.index(selectedProject.id),
              },
              {
                  label: 'Agent',
                  icon: Bot,
                  href: ProjectAgentController.index(selectedProject.id),
              },
              {
                  label: 'Settings',
                  icon: Settings,
                  href: SettingsController.index(selectedProject.id),
              },
          ]
        : [];

    const isActiveLink = (href: string | { url: string; method: string }) => {
        const linkUrl = typeof href === 'string' ? href : href.url;
        const currentUrl = typeof url === 'string' ? url : '';
        return currentUrl === linkUrl || (currentUrl.length > 0 && currentUrl.startsWith(linkUrl));
    };

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
                                    <div>
                                        <Link
                                            href='/'
                                            className='bg-sidebar-primary text-sidebar-primary-foreground flex aspect-square size-8 items-center justify-center rounded-lg hover:opacity-80 transition-opacity'
                                            aria-label='Sage - Go to home page'
                                        >
                                            <span className='text-xl font-bold'>S</span>
                                        </Link>
                                        <Link href='/' className='grid flex-1 text-left text-sm leading-tight'>
                                            <span className='truncate font-medium'>Acme Inc</span>
                                            <span className='truncate text-xs'>Enterprise</span>
                                        </Link>
                                    </div>
                                }
                            ></SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarHeader>
                <SidebarContent>
                    <ProjectSidebar projects={projects} />
                </SidebarContent>
            </Sidebar>

            {/* This is the second sidebar */}
            {/* We disable collapsible and let it fill remaining space */}
            <Sidebar collapsible='none' className='hidden flex-1 md:flex'>
                <SidebarHeader className='border-b p-4'>
                    <div className='flex w-full items-center justify-between'>
                        <div className='text-foreground text-base font-medium'>{selectedProject?.name || 'Select a project'}</div>
                    </div>
                </SidebarHeader>

                <SidebarContent>
                    {selectedProject ? (
                        <SidebarGroup>
                            <SidebarGroupLabel>Navigation</SidebarGroupLabel>
                            <SidebarMenu className='gap-2'>
                                {navigationItems.map((item) => {
                                    const href = typeof item.href === 'string' ? item.href : item.href.url;
                                    const isActive = isActiveLink(item.href);

                                    return (
                                        <SidebarMenuItem key={item.label}>
                                            <SidebarMenuButton
                                                render={
                                                    <Link href={href}>
                                                        <item.icon />
                                                        <span>{item.label}</span>
                                                    </Link>
                                                }
                                                isActive={isActive}
                                            />
                                        </SidebarMenuItem>
                                    );
                                })}
                            </SidebarMenu>
                        </SidebarGroup>
                    ) : (
                        <div className='flex h-full items-center justify-center p-4 text-center'>
                            <div className='text-muted-foreground text-sm'>
                                <p className='mb-2 font-medium'>No project selected</p>
                                <p>Select a project from the left sidebar to view navigation</p>
                            </div>
                        </div>
                    )}
                </SidebarContent>
                <SidebarFooter>
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton
                                render={
                                    <Link href='/agents'>
                                        <Bot />
                                        <span>Running Agents</span>
                                    </Link>
                                }
                                isActive={url === '/agents'}
                            />
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarFooter>
            </Sidebar>
        </Sidebar>
    );
}
