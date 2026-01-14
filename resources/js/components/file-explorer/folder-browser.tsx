import { useState, useEffect } from 'react';
import { router } from '@inertiajs/react';
import { ScrollArea } from '@/components/ui/scroll-area';
import { BreadcrumbNav } from './breadcrumb-nav';
import { DirectoryEntry } from './directory-entry';

interface Directory {
    name: string;
    path: string;
    type: string;
}

interface Breadcrumb {
    name: string;
    path: string;
}

export interface FolderBrowserHeaderProps {
    inputPath: string;
    setInputPath: (path: string) => void;
    onInputKeyDown: (e: React.KeyboardEvent<HTMLInputElement>) => void;
    onHomeClick: () => void;
    homePath: string;
}

interface FolderBrowserProps {
    directories: Directory[];
    breadcrumbs: Breadcrumb[];
    currentPath: string;
    homePath: string;
    onPathSelect: (path: string) => void;
    onHeaderPropsChange?: (props: FolderBrowserHeaderProps) => void;
}

export function FolderBrowser({ directories, breadcrumbs, currentPath, homePath, onPathSelect, onHeaderPropsChange }: FolderBrowserProps) {
    const [inputPath, setInputPath] = useState<string>(currentPath);

    // Update inputPath when currentPath changes from server
    useEffect(() => {
        setInputPath(currentPath);
    }, [currentPath]);

    const navigateToPath = (path: string) => {
        router.visit(`/projects/create?path=${encodeURIComponent(path)}`, {
            preserveState: true,
            preserveScroll: true,
            only: ['directories', 'breadcrumbs', 'currentPath'],
        });
    };

    const handleDirectoryClick = (path: string) => {
        navigateToPath(path);
    };

    const handleBreadcrumbClick = (path: string) => {
        navigateToPath(path);
    };

    const handleSelectPath = () => {
        onPathSelect(currentPath);
    };

    const handleHomeClick = () => {
        navigateToPath(homePath);
    };

    const handleInputKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
        if (e.key === 'Enter' && inputPath.trim()) {
            navigateToPath(inputPath.trim());
        }
    };

    // Notify parent of header props for external header control
    useEffect(() => {
        if (onHeaderPropsChange) {
            onHeaderPropsChange({
                inputPath,
                setInputPath,
                onInputKeyDown: handleInputKeyDown,
                onHomeClick: handleHomeClick,
                homePath,
            });
        }
    }, [inputPath, homePath, onHeaderPropsChange]);

    return (
        <div className='space-y-4'>
            <BreadcrumbNav breadcrumbs={breadcrumbs} onBreadcrumbClick={handleBreadcrumbClick} />

            <ScrollArea className='h-64'>
                <div className='space-y-1'>
                    {directories.length === 0 ? (
                        <div className='text-center py-8 text-muted-foreground text-sm'>No subdirectories found</div>
                    ) : (
                        directories.map((dir) => <DirectoryEntry key={dir.path} directory={dir} onClick={() => handleDirectoryClick(dir.path)} />)
                    )}
                </div>
            </ScrollArea>

            <div className='flex items-center gap-2 pt-2 border-t'>
                <div className='flex-1 text-sm font-mono text-muted-foreground truncate'>{currentPath || 'No path selected'}</div>
                <button
                    type='button'
                    onClick={handleSelectPath}
                    disabled={!currentPath}
                    className='px-3 py-1.5 text-sm font-medium rounded-md bg-primary text-primary-foreground hover:bg-primary/90 disabled:opacity-50 disabled:cursor-not-allowed'
                >
                    Select
                </button>
            </div>
        </div>
    );
}
