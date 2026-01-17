import { Link, usePage } from '@inertiajs/react';
import { FolderOpen, Plus } from 'lucide-react';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { Sidebar, SidebarGroup, SidebarGroupContent, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';

interface Project {
    id: number;
    name: string;
    path: string;
}

interface ProjectSidebarProps {
    projects: Project[];
}

export function ProjectSidebar({ projects }: ProjectSidebarProps) {
    const path = usePage().url;

    return (
        <SidebarGroup>
            <SidebarGroupContent className='px-0'>
                <SidebarMenu>
                    {/* Add Project Button */}
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            tooltip={{
                                children: 'Create New Project',
                                hidden: false,
                            }}
                            className='px-0 rounded-full'
                            render={() => (
                                <Link href='/projects/create'>
                                    <div className='flex aspect-square size-10 items-center justify-center rounded-full border-2 border-dashed border-muted-foreground/50 hover:border-muted-foreground hover:bg-muted/50 transition-colors'>
                                        <Plus className='h-5 w-5 text-muted-foreground' />
                                    </div>
                                </Link>
                            )}
                        />
                    </SidebarMenuItem>

                    {/* Existing project avatars */}
                    {projects.map((item) => (
                        <SidebarMenuItem key={item.id}>
                            <SidebarMenuButton
                                tooltip={{
                                    children: item.name,
                                    hidden: false,
                                }}
                                variant='outline'
                                isActive={item.path === path}
                                className='px-0 rounded-full'
                            >
                                <Avatar size='lg'>
                                    <AvatarFallback>{item.name.charAt(0).toUpperCase()}</AvatarFallback>
                                </Avatar>
                                <span>{item.name}</span>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    ))}
                </SidebarMenu>
            </SidebarGroupContent>
        </SidebarGroup>
    );
}
