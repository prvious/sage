import { store } from '@/actions/App/Http/Controllers/FeatureController';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Form, useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';

interface AddFeatureDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    projectId: number;
}

export function AddFeatureDialog({ open, onOpenChange, projectId }: AddFeatureDialogProps) {
    const { data, setData, post, processing, errors, reset, wasSuccessful } = useForm({
        project_id: projectId,
        description: '',
    });

    const [charCount, setCharCount] = useState(0);

    useEffect(() => {
        setCharCount(data.description.length);
    }, [data.description]);

    useEffect(() => {
        if (wasSuccessful) {
            toast.success('Generating feature in background', {
                description: "You'll be notified when ready!",
            });
            reset();
            onOpenChange(false);
        }
    }, [wasSuccessful]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(store.url(projectId), {
            preserveScroll: true,
        });
    };

    const isDescriptionValid = charCount >= 10 && charCount <= 2000;

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <form onSubmit={handleSubmit}>
                    <DialogHeader>
                        <DialogTitle>Add New Feature</DialogTitle>
                        <DialogDescription>
                            Describe the feature you want to build. AI will generate a detailed spec and create tasks automatically.
                        </DialogDescription>
                    </DialogHeader>
                    <div className='py-4 space-y-4'>
                        <div className='space-y-2'>
                            <div className='flex items-center justify-between'>
                                <Label htmlFor='description'>Feature Description</Label>
                                <span
                                    className={`text-xs ${
                                        charCount < 10 ? 'text-muted-foreground' : charCount > 2000 ? 'text-destructive' : 'text-green-600 dark:text-green-400'
                                    }`}
                                >
                                    {charCount} / 2000
                                </span>
                            </div>
                            <Textarea
                                id='description'
                                placeholder='e.g., Add user authentication with email verification, password reset, and social login options...'
                                value={data.description}
                                onChange={(e) => setData('description', e.target.value)}
                                rows={6}
                                className={errors.description ? 'border-destructive' : ''}
                            />
                            {errors.description && <p className='text-sm text-destructive'>{errors.description}</p>}
                            {charCount < 10 && charCount > 0 && (
                                <p className='text-xs text-muted-foreground'>
                                    Please provide at least {10 - charCount} more character{10 - charCount !== 1 ? 's' : ''}.
                                </p>
                            )}
                        </div>
                    </div>
                    <DialogFooter>
                        <Button type='button' variant='outline' onClick={() => onOpenChange(false)} disabled={processing}>
                            Cancel
                        </Button>
                        <Button type='submit' disabled={processing || !isDescriptionValid}>
                            {processing ? 'Generating...' : 'Generate Feature'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
