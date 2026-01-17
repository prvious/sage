import { router } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Field, FieldContent, FieldDescription, FieldLabel, FieldTitle } from '@/components/ui/field';
import { Badge } from '@/components/ui/badge';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Project } from '@/types';
import { useState } from 'react';

interface ServerDriverSelectorProps {
    project: Project;
}

export function ServerDriverSelector({ project }: ServerDriverSelectorProps) {
    const [selectedDriver, setSelectedDriver] = useState<string>(project.server_driver);
    const [showConfirmDialog, setShowConfirmDialog] = useState(false);
    const [isSwitching, setIsSwitching] = useState(false);

    const handleDriverChange = (value: unknown) => {
        const driverValue = value as string;
        setSelectedDriver(driverValue);
        if (driverValue !== project.server_driver) {
            setShowConfirmDialog(true);
        }
    };

    const confirmDriverSwitch = () => {
        setIsSwitching(true);
        router.post(
            `/projects/${project.id}/settings/server-driver`,
            {
                server_driver: selectedDriver,
            },
            {
                onFinish: () => {
                    setIsSwitching(false);
                    setShowConfirmDialog(false);
                },
            },
        );
    };

    const cancelDriverSwitch = () => {
        setSelectedDriver(project.server_driver);
        setShowConfirmDialog(false);
    };

    return (
        <>
            <Card>
                <CardHeader>
                    <CardTitle>Server Driver</CardTitle>
                    <CardDescription>Choose the web server for your project</CardDescription>
                </CardHeader>
                <CardContent>
                    <RadioGroup value={selectedDriver} onValueChange={handleDriverChange}>
                        <Field orientation='horizontal'>
                            <FieldLabel htmlFor='caddy' />
                            <FieldContent>
                                <FieldTitle>Caddy</FieldTitle>
                                <FieldDescription>
                                    Modern web server with automatic HTTPS
                                    <Badge variant='secondary' className='ml-2 text-xs'>
                                        Recommended
                                    </Badge>
                                </FieldDescription>
                            </FieldContent>
                            <RadioGroupItem value='caddy' id='caddy' />
                        </Field>

                        <Field orientation='horizontal'>
                            <FieldLabel htmlFor='nginx' />
                            <FieldContent>
                                <FieldTitle>Nginx</FieldTitle>
                                <FieldDescription>High-performance production web server</FieldDescription>
                            </FieldContent>
                            <RadioGroupItem value='nginx' id='nginx' />
                        </Field>

                        <Field orientation='horizontal'>
                            <FieldLabel htmlFor='artisan' />
                            <FieldContent>
                                <FieldTitle>Artisan Server</FieldTitle>
                                <FieldDescription>
                                    Lightweight PHP development server
                                    <Badge variant='outline' className='ml-2 text-xs'>
                                        Development Only
                                    </Badge>
                                </FieldDescription>
                            </FieldContent>
                            <RadioGroupItem value='artisan' id='artisan' />
                        </Field>
                    </RadioGroup>
                </CardContent>
            </Card>

            <AlertDialog open={showConfirmDialog} onOpenChange={setShowConfirmDialog}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Switch Server Driver?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Switching from {project.server_driver} to {selectedDriver} will regenerate your server configuration. This action may require the
                            server to be reloaded. Are you sure you want to proceed?
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel onClick={cancelDriverSwitch}>Cancel</AlertDialogCancel>
                        <AlertDialogAction onClick={confirmDriverSwitch} disabled={isSwitching}>
                            {isSwitching ? 'Switching...' : 'Switch Driver'}
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </>
    );
}
