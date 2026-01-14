import { Link, usePage } from '@inertiajs/react';
import { FolderOpen } from 'lucide-react';
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
            <SidebarGroupContent className='px-1.5 md:px-0'>
                <SidebarMenu>
                    {projects.map((item) => (
                        <SidebarMenuItem key={item.id}>
                            <SidebarMenuButton
                                tooltip={{
                                    children: item.name,
                                    hidden: false,
                                }}
                                isActive={item.path === path}
                                className='px-2.5 md:px-2'
                            >
                                <Avatar className=''>
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
