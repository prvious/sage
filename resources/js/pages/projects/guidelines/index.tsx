import { Head, Link, router } from '@inertiajs/react';
import { AppLayout } from '@/components/layout/app-layout';
import { GuidelineIndexProps } from '@/types';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { FileText, Plus, Edit, Trash2, RefreshCw, AlertCircle } from 'lucide-react';
import { useState } from 'react';

function formatFileSize(bytes: number): string {
    if (bytes < 1024) {
        return bytes + ' B';
    }
    if (bytes < 1024 * 1024) {
        return (bytes / 1024).toFixed(2) + ' KB';
    }
    return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
}

function formatRelativeTime(timestamp: number): string {
    const date = new Date(timestamp * 1000);
    const now = new Date();
    const diffInSeconds = Math.floor((now.getTime() - date.getTime()) / 1000);

    if (diffInSeconds < 60) {
        return `${diffInSeconds} seconds ago`;
    }

    const diffInMinutes = Math.floor(diffInSeconds / 60);
    if (diffInMinutes < 60) {
        return `${diffInMinutes} minute${diffInMinutes !== 1 ? 's' : ''} ago`;
    }

    const diffInHours = Math.floor(diffInMinutes / 60);
    if (diffInHours < 24) {
        return `${diffInHours} hour${diffInHours !== 1 ? 's' : ''} ago`;
    }

    const diffInDays = Math.floor(diffInHours / 24);
    if (diffInDays < 7) {
        return `${diffInDays} day${diffInDays !== 1 ? 's' : ''} ago`;
    }

    return date.toLocaleDateString();
}

export default function Index({ files, project }: GuidelineIndexProps) {
    const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
    const [fileToDelete, setFileToDelete] = useState<string | null>(null);
    const [aggregating, setAggregating] = useState(false);

    const handleDelete = () => {
        if (!fileToDelete) {
            return;
        }

        router.delete(`/projects/${project.id}/guidelines/${fileToDelete}`, {
            preserveScroll: true,
            onSuccess: () => {
                setDeleteDialogOpen(false);
                setFileToDelete(null);
            },
        });
    };

    const handleAggregate = () => {
        setAggregating(true);
        router.post(
            `/projects/${project.id}/guidelines/aggregate`,
            {},
            {
                preserveScroll: true,
                onFinish: () => {
                    setAggregating(false);
                },
            },
        );
    };

    return (
        <>
            <Head title={`Custom Guidelines - ${project.name}`} />
            <AppLayout>
                <div className='p-6 space-y-6'>
                    <div className='flex items-center justify-between'>
                        <div>
                            <h1 className='text-3xl font-bold'>Custom Guidelines</h1>
                            <p className='text-muted-foreground mt-2'>Manage .ai/guidelines/ directory files for custom AI agent instructions</p>
                        </div>
                        <div className='flex gap-2'>
                            <Button variant='outline' onClick={handleAggregate} disabled={aggregating}>
                                <RefreshCw className={`h-4 w-4 mr-2 ${aggregating ? 'animate-spin' : ''}`} />
                                {aggregating ? 'Aggregating...' : 'Aggregate Guidelines'}
                            </Button>
                            <Button render={<Link href={`/projects/${project.id}/guidelines/create`}>Create New Guideline</Link>}>
                                <Plus className='h-4 w-4 mr-2' />
                                Create New Guideline
                            </Button>
                        </div>
                    </div>

                    <Alert>
                        <AlertCircle className='h-4 w-4' />
                        <AlertDescription>
                            Guidelines in the .ai/guidelines/ directory are aggregated into CLAUDE.md when you click "Aggregate Guidelines". These files contain
                            custom AI agent instructions organized by topic.
                        </AlertDescription>
                    </Alert>

                    {files.length === 0 ? (
                        <Card>
                            <CardContent className='flex flex-col items-center justify-center py-12'>
                                <FileText className='h-12 w-12 text-muted-foreground mb-4' />
                                <p className='text-lg font-medium mb-2'>No Custom Guidelines</p>
                                <p className='text-sm text-muted-foreground mb-4'>Create your first guideline to start adding custom AI agent instructions</p>
                                <Button render={<Link href={`/projects/${project.id}/guidelines/create`}>Create New Guideline</Link>}>
                                    <Plus className='h-4 w-4 mr-2' />
                                    Create New Guideline
                                </Button>
                            </CardContent>
                        </Card>
                    ) : (
                        <Card>
                            <CardHeader>
                                <CardTitle>Custom Guidelines ({files.length})</CardTitle>
                                <CardDescription>Markdown and Blade files defining custom AI agent behaviors and instructions</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Filename</TableHead>
                                            <TableHead>Size</TableHead>
                                            <TableHead>Last Modified</TableHead>
                                            <TableHead className='text-right'>Actions</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {files.map((file) => (
                                            <TableRow key={file.name}>
                                                <TableCell className='font-medium'>{file.name}</TableCell>
                                                <TableCell>{formatFileSize(file.size)}</TableCell>
                                                <TableCell>{formatRelativeTime(file.modified_at)}</TableCell>
                                                <TableCell className='text-right'>
                                                    <div className='flex justify-end gap-2'>
                                                        <Button
                                                            variant='outline'
                                                            size='sm'
                                                            render={<Link href={`/projects/${project.id}/guidelines/${file.name}/edit`}>Edit</Link>}
                                                        >
                                                            <Edit className='h-4 w-4 mr-2' />
                                                            Edit
                                                        </Button>
                                                        <Button
                                                            variant='destructive'
                                                            size='sm'
                                                            onClick={() => {
                                                                setFileToDelete(file.name);
                                                                setDeleteDialogOpen(true);
                                                            }}
                                                        >
                                                            <Trash2 className='h-4 w-4 mr-2' />
                                                            Delete
                                                        </Button>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </CardContent>
                        </Card>
                    )}
                </div>

                <Dialog open={deleteDialogOpen} onOpenChange={setDeleteDialogOpen}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Delete Guideline</DialogTitle>
                            <DialogDescription>
                                Are you sure you want to delete <strong>{fileToDelete}</strong>? This action cannot be undone.
                            </DialogDescription>
                        </DialogHeader>
                        <DialogFooter>
                            <Button variant='outline' onClick={() => setDeleteDialogOpen(false)}>
                                Cancel
                            </Button>
                            <Button variant='destructive' onClick={handleDelete}>
                                Delete
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </AppLayout>
        </>
    );
}
