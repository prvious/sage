import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useForm } from '@inertiajs/react';

interface QuickAddTaskDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    projectId: number;
}

export function QuickAddTaskDialog({ open, onOpenChange, projectId }: QuickAddTaskDialogProps) {
    const { data, setData, post, processing, errors, reset } = useForm({
        project_id: projectId,
        title: '',
        description: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/tasks', {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onOpenChange(false);
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>Add New Task</DialogTitle>
                        <DialogDescription>Describe the task you want to create. You can refine details later.</DialogDescription>
                    </DialogHeader>
                    <div className='py-4 space-y-4'>
                        <div className='space-y-2'>
                            <Label htmlFor='description'>Task Description</Label>
                            <Textarea
                                id='description'
                                placeholder='e.g., Add user authentication with email verification...'
                                value={data.description}
                                onChange={(e) => {
                                    setData('description', e.target.value);
                                    // Auto-generate title from first line if title is empty
                                    if (!data.title && e.target.value) {
                                        const firstLine = e.target.value.split('\n')[0];
                                        setData('title', firstLine.substring(0, 100));
                                    }
                                }}
                                rows={5}
                                className={errors.description ? 'border-destructive' : ''}
                            />
                            {errors.description && <p className='text-sm text-destructive'>{errors.description}</p>}
                        </div>
                    </div>
                    <DialogFooter>
                        <Button type='button' variant='outline' onClick={() => onOpenChange(false)}>
                            Cancel
                        </Button>
                        <Button type='submit' disabled={processing || !data.description}>
                            {processing ? 'Creating...' : 'Create Task'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
