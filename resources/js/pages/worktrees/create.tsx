import { Form, Head } from '@inertiajs/react';
import { AppLayout } from '@/components/layout/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Checkbox } from '@/components/ui/checkbox';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Field, FieldContent, FieldDescription, FieldGroup, FieldLabel, FieldSet, FieldTitle } from '@/components/ui/field';
import { Badge } from '@/components/ui/badge';
import { store } from '@/actions/App/Http/Controllers/WorktreeController';

interface Project {
    id: number;
    name: string;
}

interface Props {
    project: Project;
}

export default function Create({ project }: Props) {
    return (
        <>
            <Head title={`Create Worktree - ${project.name}`} />

            <AppLayout>
                <div className='p-6 space-y-6'>
                    <div>
                        <h1 className='text-3xl font-bold'>Create Worktree</h1>
                        <p className='text-muted-foreground mt-2'>Set up a new Git worktree with an isolated environment for your feature branch.</p>
                    </div>

                    <div className='max-w-2xl'>
                        <Form {...store.form(project.id)}>
                            {({ errors, processing }) => (
                                <FieldSet className='space-y-6'>
                                    <FieldGroup>
                                        <Field>
                                            <FieldLabel htmlFor='branch_name'>Branch Name</FieldLabel>
                                            <Input id='branch_name' name='branch_name' placeholder='feature/new-feature' required />
                                            <FieldDescription>Enter the branch name for this worktree</FieldDescription>
                                            {errors.branch_name && <p className='text-sm text-destructive'>{errors.branch_name}</p>}
                                        </Field>

                                        <Field>
                                            <div className='flex items-center gap-2'>
                                                <Checkbox id='create_branch' name='create_branch' value='true' />
                                                <FieldLabel htmlFor='create_branch' className='!mt-0'>
                                                    Create branch if it doesn't exist
                                                </FieldLabel>
                                            </div>
                                            <FieldDescription>
                                                Check this to automatically create the branch if it doesn't already exist in your repository
                                            </FieldDescription>
                                        </Field>

                                        <Field>
                                            <FieldLabel>Database Isolation</FieldLabel>
                                            <RadioGroup defaultValue='separate' name='database_isolation' className='grid grid-rows-3 gap-4'>
                                                <FieldLabel htmlFor='separate'>
                                                    <Field orientation='horizontal'>
                                                        <FieldContent>
                                                            <FieldTitle>Separate Database (SQLite)</FieldTitle>
                                                            <FieldDescription>
                                                                Each worktree gets its own SQLite database file
                                                                <Badge variant='secondary' className='ml-2 text-xs'>
                                                                    Recommended
                                                                </Badge>
                                                            </FieldDescription>
                                                        </FieldContent>
                                                        <RadioGroupItem value='separate' id='separate' />
                                                    </Field>
                                                </FieldLabel>

                                                <FieldLabel htmlFor='prefix'>
                                                    <Field orientation='horizontal'>
                                                        <FieldContent>
                                                            <FieldTitle>Table Prefix</FieldTitle>
                                                            <FieldDescription>Share database but prefix all tables with branch name</FieldDescription>
                                                        </FieldContent>
                                                        <RadioGroupItem value='prefix' id='prefix' />
                                                    </Field>
                                                </FieldLabel>

                                                <FieldLabel htmlFor='shared'>
                                                    <Field orientation='horizontal'>
                                                        <FieldContent>
                                                            <FieldTitle>Shared Database</FieldTitle>
                                                            <FieldDescription>Use the same database as main project (not recommended)</FieldDescription>
                                                        </FieldContent>
                                                        <RadioGroupItem value='shared' id='shared' />
                                                    </Field>
                                                </FieldLabel>
                                            </RadioGroup>
                                            {errors.database_isolation && <p className='text-sm text-destructive'>{errors.database_isolation}</p>}
                                        </Field>

                                        <div className='flex justify-end gap-3 pt-4'>
                                            <Button type='button' variant='ghost' onClick={() => window.history.back()}>
                                                Cancel
                                            </Button>
                                            <Button type='submit' disabled={processing}>
                                                {processing ? 'Creating...' : 'Create Worktree'}
                                            </Button>
                                        </div>
                                    </FieldGroup>
                                </FieldSet>
                            )}
                        </Form>
                    </div>
                </div>
            </AppLayout>
        </>
    );
}
