import { Head, router, Deferred, usePoll } from '@inertiajs/react';
import { AppLayout } from '@/components/layout/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Info, CheckCircle2, AlertCircle, XCircle, RefreshCw } from 'lucide-react';
import { useState, useEffect } from 'react';

interface Project {
    id: number;
    name: string;
}

interface AgentInstallationStatus {
    installed: boolean;
    path: string | null;
    error_message: string | null;
}

interface AgentAuthenticationStatus {
    authenticated: boolean;
    auth_type: 'cli' | 'api_key' | 'none';
    error_message: string | null;
}

interface Props {
    project: Project;
    agentInstalled?: AgentInstallationStatus;
    agentAuthenticated?: AgentAuthenticationStatus;
}

export default function Agent({ project, agentInstalled, agentAuthenticated }: Props) {
    const [isRefreshing, setIsRefreshing] = useState(false);

    // Set up polling for authentication status
    const { start: startPolling, stop: stopPolling } = usePoll(60000, { only: ['agentAuthenticated'] }, { autoStart: false });

    // Control polling based on authentication status
    useEffect(() => {
        if (agentInstalled?.installed && !agentAuthenticated?.authenticated) {
            startPolling();
        } else {
            stopPolling();
        }

        return () => stopPolling();
    }, [agentInstalled?.installed, agentAuthenticated?.authenticated, startPolling, stopPolling]);

    const handleRefresh = () => {
        setIsRefreshing(true);
        router.reload({
            only: ['agentInstalled', 'agentAuthenticated'],
            onFinish: () => setIsRefreshing(false),
        });
    };

    return (
        <>
            <Head title={`Agent Settings - ${project.name}`} />

            <AppLayout>
                <div className='p-6 space-y-6'>
                    <div>
                        <h1 className='text-3xl font-bold'>Agent Settings</h1>
                        <p className='text-muted-foreground mt-2'>Information about the AI agent used for this project.</p>
                    </div>

                    <div className='grid gap-6 max-w-4xl'>
                        <Card>
                            <CardHeader>
                                <div className='flex items-start justify-between'>
                                    <div>
                                        <CardTitle>Agent Status</CardTitle>
                                        <CardDescription>Current installation and authentication status of Claude Code.</CardDescription>
                                    </div>
                                    <Button variant='outline' size='sm' onClick={handleRefresh} disabled={isRefreshing}>
                                        <RefreshCw className={`h-4 w-4 ${isRefreshing ? 'animate-spin' : ''}`} />
                                        <span className='ml-2'>Refresh</span>
                                    </Button>
                                </div>
                            </CardHeader>
                            <CardContent>
                                <div className='space-y-4'>
                                    {/* Installation Status Section */}
                                    <Deferred
                                        data='agentInstalled'
                                        fallback={
                                            <Alert variant='default' className='animate-pulse'>
                                                <XCircle className='size-4' />
                                                <AlertTitle>Checking Installation...</AlertTitle>
                                                <AlertDescription>Verifying Claude Code CLI installation status...</AlertDescription>
                                            </Alert>
                                        }
                                    >
                                        {agentInstalled && !agentInstalled.installed && (
                                            <Alert variant='destructive'>
                                                <XCircle className='size-4' />
                                                <AlertTitle>Not Installed</AlertTitle>
                                                <AlertDescription>
                                                    Claude Code CLI is not installed or not in PATH.
                                                    {agentInstalled.error_message && <div className='mt-2 text-sm'>{agentInstalled.error_message}</div>}
                                                </AlertDescription>
                                            </Alert>
                                        )}

                                        {agentInstalled && agentInstalled.installed && (
                                            <Alert className='border-green-500 text-green-900 dark:text-green-100'>
                                                <CheckCircle2 className='size-4 text-green-600 dark:text-green-400' />
                                                <AlertTitle>Installed</AlertTitle>
                                                <AlertDescription>
                                                    Claude Code CLI is installed at <code className='bg-muted px-1 rounded text-xs'>{agentInstalled.path}</code>
                                                </AlertDescription>
                                            </Alert>
                                        )}
                                    </Deferred>

                                    {/* Authentication Status Section (only show if installed) */}
                                    <Deferred
                                        data='agentAuthenticated'
                                        fallback={
                                            <Alert variant='default' className='animate-pulse'>
                                                <Info className='size-4' />
                                                <AlertTitle>Checking Authentication</AlertTitle>
                                                <AlertDescription>Verifying Claude Code authentication status...</AlertDescription>
                                            </Alert>
                                        }
                                    >
                                        {agentAuthenticated && !agentAuthenticated.authenticated && (
                                            <Alert className='border-yellow-500 text-yellow-900 dark:text-yellow-100'>
                                                <AlertCircle className='size-4' />
                                                <AlertTitle>Not Authenticated</AlertTitle>
                                                <AlertDescription>
                                                    Claude Code is installed but not authenticated. Run{' '}
                                                    <code className='bg-muted px-1 rounded'>claude login</code> to authenticate.
                                                    {agentAuthenticated.error_message && <div className='mt-2 text-sm'>{agentAuthenticated.error_message}</div>}
                                                </AlertDescription>
                                            </Alert>
                                        )}

                                        {agentAuthenticated && agentAuthenticated.authenticated && (
                                            <Alert className='border-green-500 text-green-900 dark:text-green-100'>
                                                <CheckCircle2 className='size-4 text-green-600 dark:text-green-400' />
                                                <AlertTitle>Authenticated</AlertTitle>
                                                <AlertDescription>
                                                    <div className='flex items-center gap-2'>
                                                        <span>Claude Code is authenticated and ready to use.</span>
                                                        {agentAuthenticated.auth_type === 'api_key' && (
                                                            <Badge variant='secondary' className='ml-2'>
                                                                API Key
                                                            </Badge>
                                                        )}
                                                        {agentAuthenticated.auth_type === 'cli' && (
                                                            <Badge variant='secondary' className='ml-2'>
                                                                CLI
                                                            </Badge>
                                                        )}
                                                    </div>
                                                </AlertDescription>
                                            </Alert>
                                        )}
                                    </Deferred>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </AppLayout>
        </>
    );
}
