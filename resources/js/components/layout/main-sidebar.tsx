import { Link } from '@inertiajs/react';
import { CheckSquare, ChevronLeft, ChevronRight, FileText, GitBranch, LayoutDashboard, Settings } from 'lucide-react';
import { Button } from '@/components/ui/button';

interface MainSidebarProps {
    isCollapsed: boolean;
    onToggleCollapse: () => void;
}

const navigationItems = [
    { label: 'Dashboard', icon: LayoutDashboard, href: '/dashboard' },
    { label: 'Tasks', icon: CheckSquare, href: '/tasks' },
    { label: 'Worktrees', icon: GitBranch, href: '/worktrees' },
    { label: 'Specs', icon: FileText, href: '/specs' },
    { label: 'Environment', icon: Settings, href: '/environment' },
];

export function MainSidebar({ isCollapsed, onToggleCollapse }: MainSidebarProps) {
    return (
        <aside className={`flex flex-col border-r bg-muted/10 transition-all ${isCollapsed ? 'w-16' : 'w-64'}`}>
            <div className='flex h-14 items-center justify-between border-b px-3'>
                {!isCollapsed && <h1 className='text-lg font-bold'>Sage</h1>}
                <Button variant='ghost' size='icon' onClick={onToggleCollapse} className='shrink-0'>
                    {isCollapsed ? <ChevronRight className='size-4' /> : <ChevronLeft className='size-4' />}
                </Button>
            </div>

            <nav className='flex-1 overflow-auto p-2'>
                <div className='flex flex-col gap-1'>
                    {navigationItems.map((item) => {
                        const Icon = item.icon;
                        return (
                            <Link key={item.href} href={item.href} className='flex items-center gap-3 rounded-md px-3 py-2 text-sm hover:bg-accent'>
                                <Icon className='size-5 shrink-0' />
                                {!isCollapsed && <span className='font-medium'>{item.label}</span>}
                            </Link>
                        );
                    })}
                </div>
            </nav>
        </aside>
    );
}
