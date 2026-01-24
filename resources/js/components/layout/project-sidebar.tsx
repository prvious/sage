import { Link, usePage } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { SidebarGroup, SidebarGroupContent, SidebarMenu, SidebarMenuAction, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';

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
                <SidebarMenu className='gap-2'>
                    {/* Add Project Button */}
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            className='px-0 rounded-full'
                            render={() => (
                                <Link href='/projects/create'>
                                    <div className='flex aspect-square size-full items-center justify-center rounded-full border border-dashed border-muted-foreground/50 hover:border-muted-foreground hover:bg-muted/50 transition-colors'>
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
                                isActive={path === item.path}
                                className='px-0 rounded-full'
                                render={() => (
                                    <div className='flex aspect-square size-full items-center justify-center rounded-full border border-background bg-background hover:bg-muted/50 transition-colors'>
                                        <span className='size-5 flex items-center justify-center'>{item.name.charAt(0).toUpperCase()}</span>
                                    </div>
                                )}
                            />
                        </SidebarMenuItem>
                    ))}
                </SidebarMenu>
            </SidebarGroupContent>
        </SidebarGroup>
    );
}
