---
name: global-agents-page
description: Add a global agents page showing all running agents across all projects with a link in the sidebar footer
depends_on: null
---

## Feature Description

Create a dedicated page to view all running agents across all projects in the system. This provides a central hub for monitoring agent activity, viewing real-time output, and managing running tasks. The page will be accessible via a link in the main sidebar footer, making it easily discoverable without cluttering the main navigation menu.

Currently, agents are managed per-project on the dashboard. This feature adds a global view that aggregates all running agents regardless of which project they belong to, making it easier to:

- Monitor multiple agents running across different projects
- Quickly identify which agents are active
- Access agent output and status from a single location
- Stop running agents from a central interface

## Implementation Plan

### Backend Implementation

**New Route**:

- Add route in `routes/web.php` for global agents listing
- Path: `GET /agents` → `AgentController@index`
- Name: `agents.index`

**Controller Method**:

**File to modify**: `app/Http/Controllers/AgentController.php`

Add an `index()` method to retrieve all running agents:

```php
public function index(): Response
{
    $runningAgents = Task::with(['project', 'worktree'])
        ->where('status', 'in_progress')
        ->orderBy('started_at', 'desc')
        ->get()
        ->map(function (Task $task) {
            return [
                'id' => $task->id,
                'project_id' => $task->project_id,
                'project_name' => $task->project->name,
                'worktree_id' => $task->worktree_id,
                'worktree_name' => $task->worktree?->name,
                'agent_type' => $task->agent_type,
                'model' => $task->model,
                'status' => $task->status,
                'started_at' => $task->started_at,
                'agent_output' => $task->agent_output,
                'description' => $task->description,
            ];
        });

    return Inertia::render('Agents/Index', [
        'runningAgents' => $runningAgents,
    ]);
}
```

**TypeScript Types**:

**File to modify**: `resources/js/types/index.d.ts`

Add interfaces for the agents page:

```typescript
export interface RunningAgent {
    id: number;
    project_id: number;
    project_name: string;
    worktree_id: number | null;
    worktree_name: string | null;
    agent_type: string;
    model: string;
    status: string;
    started_at: string;
    agent_output: string | null;
    description: string | null;
}

export interface AgentsIndexProps extends SharedProps {
    runningAgents: RunningAgent[];
}
```

### Frontend Implementation

**New Page Component**:

**File to create**: `resources/js/pages/agents/index.tsx`

Create the global agents listing page:

```tsx
import { Head, Link } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { AgentsIndexProps } from '@/types';
import AppLayout from '@/components/layout/app-layout';
import { Bot, ExternalLink, Square } from 'lucide-react';
import { formatDistanceToNow } from 'date-fns';
import { router } from '@inertiajs/react';

export default function Index({ runningAgents }: AgentsIndexProps) {
    const handleStop = (agentId: number) => {
        router.post(`/tasks/${agentId}/stop`);
    };

    return (
        <>
            <Head title='Running Agents' />

            <div className='space-y-6'>
                <div>
                    <h1 className='text-3xl font-bold'>Running Agents</h1>
                    <p className='text-muted-foreground'>Monitor all active agents across all projects</p>
                </div>

                {runningAgents.length === 0 ? (
                    <Card>
                        <CardContent className='flex flex-col items-center justify-center py-12'>
                            <Bot className='h-12 w-12 text-muted-foreground mb-4' />
                            <p className='text-lg font-medium mb-2'>No Running Agents</p>
                            <p className='text-sm text-muted-foreground'>All agents are idle. Start an agent from a project dashboard.</p>
                        </CardContent>
                    </Card>
                ) : (
                    <div className='grid gap-4'>
                        {runningAgents.map((agent) => (
                            <Card key={agent.id}>
                                <CardHeader>
                                    <div className='flex items-start justify-between'>
                                        <div className='space-y-1'>
                                            <CardTitle className='flex items-center gap-2'>
                                                <Bot className='h-5 w-5' />
                                                Agent #{agent.id}
                                                <Badge variant='default' className='ml-2'>
                                                    Running
                                                </Badge>
                                            </CardTitle>
                                            <CardDescription>{agent.description || 'No description provided'}</CardDescription>
                                        </div>
                                        <div className='flex gap-2'>
                                            <Button
                                                variant='outline'
                                                size='sm'
                                                render={() => (
                                                    <Link href={`/projects/${agent.project_id}/dashboard`}>
                                                        <ExternalLink className='h-4 w-4 mr-2' />
                                                        View Project
                                                    </Link>
                                                )}
                                            />
                                            <Button variant='destructive' size='sm' onClick={() => handleStop(agent.id)}>
                                                <Square className='h-4 w-4 mr-2' />
                                                Stop
                                            </Button>
                                        </div>
                                    </div>
                                </CardHeader>
                                <CardContent className='space-y-4'>
                                    <div className='grid grid-cols-2 md:grid-cols-4 gap-4 text-sm'>
                                        <div>
                                            <p className='text-muted-foreground'>Project</p>
                                            <p className='font-medium'>{agent.project_name}</p>
                                        </div>
                                        <div>
                                            <p className='text-muted-foreground'>Model</p>
                                            <p className='font-medium'>{agent.model}</p>
                                        </div>
                                        <div>
                                            <p className='text-muted-foreground'>Agent Type</p>
                                            <p className='font-medium capitalize'>{agent.agent_type}</p>
                                        </div>
                                        <div>
                                            <p className='text-muted-foreground'>Started</p>
                                            <p className='font-medium'>
                                                {formatDistanceToNow(new Date(agent.started_at), {
                                                    addSuffix: true,
                                                })}
                                            </p>
                                        </div>
                                    </div>

                                    {agent.agent_output && (
                                        <div className='bg-muted rounded-lg p-4'>
                                            <p className='text-xs font-medium mb-2 text-muted-foreground'>Latest Output</p>
                                            <pre className='text-xs whitespace-pre-wrap font-mono'>{agent.agent_output.split('\n').slice(-10).join('\n')}</pre>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

Index.layout = (page: React.ReactNode) => <AppLayout>{page}</AppLayout>;
```

**Sidebar Footer Link**:

**File to modify**: `resources/js/components/layout/app-sidebar.tsx`

Add the agents link in the sidebar footer of the second sidebar:

1. Import SidebarFooter component (already imported)
2. Import Bot icon from lucide-react (already imported)
3. Add SidebarFooter after SidebarContent in the second sidebar:

```tsx
<Sidebar collapsible='none' className='hidden flex-1 md:flex'>
    <SidebarHeader className='gap-3.5 border-b p-4'>{/* Existing header content */}</SidebarHeader>

    <SidebarContent>{/* Existing navigation items */}</SidebarContent>

    {/* Add SidebarFooter */}
    <SidebarFooter>
        <SidebarMenu>
            <SidebarMenuItem>
                <SidebarMenuButton
                    render={() => (
                        <Link href='/agents'>
                            <Bot />
                            <span>Running Agents</span>
                        </Link>
                    )}
                    isActive={url === '/agents'}
                />
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarFooter>
</Sidebar>
```

**Wayfinder Actions**:

If using Wayfinder, generate TypeScript action for the new route:

```bash
php artisan wayfinder:generate
```

This will create:

- `resources/js/actions/App/Http/Controllers/AgentController.ts` with `index()` method

Update the sidebar link to use Wayfinder:

```tsx
import AgentController from '@/actions/App/Http/Controllers/AgentController';

// In SidebarFooter:
<Link href={AgentController.index().url}>
```

### Real-time Updates (Optional Enhancement)

**Add WebSocket listener** to automatically update the agents list when agents start/stop:

```tsx
import { useEffect, useState } from 'react';
import Echo from 'laravel-echo';

export default function Index({ runningAgents: initialAgents }: AgentsIndexProps) {
    const [runningAgents, setRunningAgents] = useState(initialAgents);

    useEffect(() => {
        // Listen for agent status changes
        const echo = new Echo({
            broadcaster: 'pusher',
            // ... echo config
        });

        // Subscribe to each agent's channel
        runningAgents.forEach((agent) => {
            echo.private(`task.${agent.id}`)
                .listen('AgentStatusChanged', (event) => {
                    if (event.status === 'done' || event.status === 'failed') {
                        // Remove agent from list
                        setRunningAgents((prev) => prev.filter((a) => a.id !== event.taskId));
                    }
                })
                .listen('AgentOutputReceived', (event) => {
                    // Update agent output
                    setRunningAgents((prev) => prev.map((a) => (a.id === event.taskId ? { ...a, agent_output: a.agent_output + event.line } : a)));
                });
        });

        return () => {
            echo.disconnect();
        };
    }, []);

    // Rest of component...
}
```

## Acceptance Criteria

- [ ] New route `/agents` is accessible and loads the agents page
- [ ] AgentController has `index()` method that retrieves all running agents
- [ ] Page displays all agents with status "in_progress" across all projects
- [ ] Each agent card shows: project name, model, agent type, start time, description
- [ ] Agent output displays last 10 lines of output in monospace font
- [ ] "Stop" button successfully stops the agent
- [ ] "View Project" link navigates to the correct project dashboard
- [ ] Empty state displays when no agents are running
- [ ] Sidebar footer shows "Running Agents" link with Bot icon
- [ ] Sidebar footer link is highlighted when on /agents page
- [ ] Page uses AppLayout for consistent styling
- [ ] All TypeScript types are properly defined
- [ ] Badge shows "Running" status for active agents
- [ ] Started time shows relative time (e.g., "2 minutes ago")
- [ ] Agent cards are responsive on mobile and desktop
- [ ] All tests pass
- [ ] Code is formatted according to project standards

## Testing Strategy

### Feature Tests

**Test file location**: `tests/Feature/Agents/AgentsControllerTest.php`

**Key test cases**:

- Test index page loads successfully
- Test page displays all running agents from multiple projects
- Test page filters out non-running agents (status != 'in_progress')
- Test page shows empty state when no agents are running
- Test agents are ordered by started_at descending
- Test agent data includes project and worktree relationships
- Test authenticated user can access the page
- Test unauthenticated user is redirected to login

### Browser Tests

**Test file location**: `tests/Browser/Agents/GlobalAgentsPageTest.php`

**Key test cases**:

- Test page displays running agents from multiple projects
- Test agent cards show correct information (project, model, status, time)
- Test "Stop" button stops the agent
- Test "View Project" link navigates to correct project dashboard
- Test empty state appears when no agents running
- Test sidebar footer link navigates to /agents page
- Test sidebar footer link is highlighted on /agents page
- Test agent output displays in monospace font
- Test page is responsive on different viewport sizes
- Test real-time updates when agent status changes (if implemented)

## Code Formatting

Format all code using:

- **Backend (PHP)**: Laravel Pint
    - Command: `vendor/bin/pint --dirty`
- **Frontend (TypeScript/React)**: Prettier/oxfmt
    - Command: `pnpm run format`

## Additional Notes

### Architecture Benefits

1. **Centralized Monitoring**: Single page to view all agent activity across projects
2. **Consistent Layout**: Uses existing AppLayout for familiar user experience
3. **Real-time Updates**: Optional WebSocket integration keeps data fresh
4. **Easy Navigation**: Sidebar footer placement doesn't clutter main navigation
5. **Scalable Design**: Card-based layout works well with 1 or 100+ agents

### Design Considerations

- **Placement**: Footer location keeps agents accessible but not prominent (they're advanced feature)
- **Badge Status**: Visual indicator makes it easy to scan for active agents
- **Output Preview**: Last 10 lines gives context without overwhelming the UI
- **Relative Time**: "2 minutes ago" is more useful than absolute timestamp for active monitoring
- **Empty State**: Guides users on how to start agents when list is empty

### Current Agent System Context

- **Agent Drivers**: Claude Code (default), OpenCode, Fake
- **Task Statuses**: 'idea', 'in_progress', 'review', 'done', 'failed'
- **WebSocket Events**: AgentStatusChanged, AgentOutputReceived
- **Job**: RunAgent handles agent lifecycle
- **Supported Models**: claude-sonnet-4, claude-opus-4, claude-3-5-sonnet

### Future Enhancements

- Add filtering by project, agent type, or model
- Add search functionality for agent descriptions
- Add pagination for large numbers of running agents
- Add "Start Agent" button with quick task creation modal
- Add agent execution time graph/chart
- Add agent success/failure statistics
- Add ability to restart failed agents
- Add agent logs download feature
- Add multi-agent stop (select and stop multiple agents)
- Add notifications when agent completes

### Implementation Notes

- Use existing Task model and relationships (no schema changes needed)
- Leverage existing AgentController for stop functionality
- Reuse existing WebSocket events for real-time updates
- Follow existing sidebar patterns for footer link
- Use Shadcn UI components (Card, Badge, Button) for consistency
- Use date-fns for relative time formatting
- Ensure proper TypeScript types for all props
- Test with multiple projects and multiple running agents
- Verify stop button works correctly
- Check that empty state is helpful and not discouraging

### Error Handling

- Handle case where agent stops between page load and user interaction
- Handle WebSocket connection failures gracefully
- Show user-friendly error messages when stop action fails
- Handle missing project/worktree relationships safely

### Performance Considerations

- Limit agent output preview to last 10 lines to avoid large payloads
- Use eager loading (`with(['project', 'worktree'])`) to prevent N+1 queries
- Consider pagination if expecting >50 concurrent agents
- Optimize WebSocket subscriptions for large numbers of agents

### Accessibility

- Use semantic HTML structure (heading hierarchy, lists, etc.)
- Ensure keyboard navigation works for all interactive elements
- Provide appropriate ARIA labels for icons and buttons
- Maintain sufficient color contrast for badges and text
- Ensure screen readers can access all information

### Security Considerations

- Verify user has permission to view agents across all projects
- Ensure user can only stop agents they have permission to manage
- Sanitize agent output before displaying (prevent XSS)
- Use CSRF protection on stop action
- Consider rate limiting on stop action to prevent abuse
