import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { ServerStatus as ServerStatusType } from '@/types';
import { router } from '@inertiajs/react';
import { Check, X, RefreshCw, TestTube } from 'lucide-react';
import { useState } from 'react';

interface ServerStatusProps {
    serverStatus: ServerStatusType;
    projectId: number;
}

export function ServerStatus({ serverStatus, projectId }: ServerStatusProps) {
    const [isTesting, setIsTesting] = useState(false);
    const [isRegenerating, setIsRegenerating] = useState(false);
    const [testResult, setTestResult] = useState<{
        success: boolean;
        message: string;
    } | null>(null);

    const handleTestServer = async () => {
        setIsTesting(true);
        setTestResult(null);
        try {
            const response = await fetch(`/projects/${projectId}/settings/test-server`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });
            const result = await response.json();
            setTestResult(result);
        } catch {
            setTestResult({
                success: false,
                message: 'Failed to test server connection',
            });
        } finally {
            setIsTesting(false);
        }
    };

    const handleRegenerateConfig = () => {
        setIsRegenerating(true);
        router.post(
            `/projects/${projectId}/settings/regenerate-config`,
            {},
            {
                onFinish: () => {
                    setIsRegenerating(false);
                },
            },
        );
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle>Server Status</CardTitle>
                <CardDescription>Current server driver status and configuration</CardDescription>
            </CardHeader>
            <CardContent className='space-y-4'>
                <div className='grid grid-cols-2 gap-4'>
                    <div>
                        <p className='text-sm font-medium text-muted-foreground'>Driver</p>
                        <p className='text-lg font-semibold capitalize'>{serverStatus.driver}</p>
                    </div>
                    <div>
                        <p className='text-sm font-medium text-muted-foreground'>Status</p>
                        <div className='flex items-center gap-2 mt-1'>
                            {serverStatus.installed ? (
                                <>
                                    <Check className='h-4 w-4 text-green-600' />
                                    <span className='text-sm'>Installed</span>
                                </>
                            ) : (
                                <>
                                    <X className='h-4 w-4 text-destructive' />
                                    <span className='text-sm'>Not Installed</span>
                                </>
                            )}
                        </div>
                    </div>
                </div>

                {serverStatus.installed && (
                    <>
                        <Separator />
                        <div className='grid grid-cols-2 gap-4'>
                            <div>
                                <p className='text-sm font-medium text-muted-foreground'>Running</p>
                                <div className='flex items-center gap-2 mt-1'>
                                    {serverStatus.running ? <Badge variant='default'>Active</Badge> : <Badge variant='secondary'>Inactive</Badge>}
                                </div>
                            </div>
                            <div>
                                <p className='text-sm font-medium text-muted-foreground'>Version</p>
                                <p className='text-sm mt-1'>{serverStatus.version || 'Unknown'}</p>
                            </div>
                        </div>
                    </>
                )}

                <Separator />

                <div>
                    <p className='text-sm font-medium text-muted-foreground'>Worktrees</p>
                    <p className='text-lg font-semibold'>{serverStatus.worktrees_count}</p>
                </div>

                {testResult && (
                    <>
                        <Separator />
                        <div
                            className={`p-3 rounded-md ${testResult.success ? 'bg-green-50 text-green-900 border border-green-200' : 'bg-destructive/10 text-destructive border border-destructive/20'}`}
                        >
                            <p className='text-sm font-medium'>{testResult.message}</p>
                        </div>
                    </>
                )}

                <Separator />

                <div className='flex gap-2'>
                    <Button variant='outline' onClick={handleTestServer} disabled={isTesting || !serverStatus.installed}>
                        <TestTube className='mr-2 h-4 w-4' />
                        {isTesting ? 'Testing...' : 'Test Connection'}
                    </Button>
                    <Button variant='outline' onClick={handleRegenerateConfig} disabled={isRegenerating || !serverStatus.installed}>
                        <RefreshCw className='mr-2 h-4 w-4' />
                        {isRegenerating ? 'Regenerating...' : 'Regenerate Config'}
                    </Button>
                </div>
            </CardContent>
        </Card>
    );
}
