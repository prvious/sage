import { FolderIcon } from 'lucide-react';

interface Directory {
    name: string;
    path: string;
    type: string;
}

interface DirectoryEntryProps {
    directory: Directory;
    onClick: () => void;
}

export function DirectoryEntry({ directory, onClick }: DirectoryEntryProps) {
    return (
        <button type='button' onClick={onClick} className='w-full flex items-center gap-3 px-3 py-2 rounded-md hover:bg-muted transition-colors text-left'>
            <FolderIcon className='h-4 w-4 text-muted-foreground shrink-0' />
            <span className='text-sm truncate'>{directory.name}</span>
        </button>
    );
}
