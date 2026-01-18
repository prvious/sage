import { Form, Head, router } from "@inertiajs/react";
import { AppLayout } from "@/components/layout/app-layout";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { RadioGroup, RadioGroupItem } from "@/components/ui/radio-group";
import {
    Field,
    FieldContent,
    FieldDescription,
    FieldGroup,
    FieldLabel,
    FieldSet,
    FieldTitle,
} from "@/components/ui/field";
import { Badge } from "@/components/ui/badge";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { CheckCircle2, Circle, Loader2 } from "lucide-react";
import { useState } from "react";
import {
    updateDefault,
    storeApiKey,
    testConnection,
} from "@/actions/App/Http/Controllers/ProjectAgentController";

interface Project {
    id: number;
    name: string;
}

interface AgentSetting {
    default_agent: string;
    has_claude_code_api_key: boolean;
    has_opencode_api_key: boolean;
    claude_code_last_tested_at: string | null;
    opencode_last_tested_at: string | null;
}

interface Props {
    project: Project;
    agentSetting: AgentSetting;
}

export default function Agent({ project, agentSetting }: Props) {
    const [testingClaudeCode, setTestingClaudeCode] = useState(false);
    const [testingOpenCode, setTestingOpenCode] = useState(false);
    const [claudeCodeTestResult, setClaudeCodeTestResult] = useState<{
        success: boolean;
        message: string;
    } | null>(null);
    const [openCodeTestResult, setOpenCodeTestResult] = useState<{
        success: boolean;
        message: string;
    } | null>(null);

    const handleTestConnection = async (
        agentType: "claude-code" | "opencode",
    ) => {
        const setTesting =
            agentType === "claude-code"
                ? setTestingClaudeCode
                : setTestingOpenCode;
        const setResult =
            agentType === "claude-code"
                ? setClaudeCodeTestResult
                : setOpenCodeTestResult;

        setTesting(true);
        setResult(null);

        try {
            const response = await fetch(
                testConnection(project.id, agentType).url,
                {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN":
                            document.querySelector<HTMLMetaElement>(
                                'meta[name="csrf-token"]',
                            )?.content || "",
                    },
                },
            );

            const data = await response.json();
            setResult(data);

            if (data.success) {
                router.reload({ only: ["agentSetting"] });
            }
        } catch (error) {
            setResult({
                success: false,
                message: "Failed to test connection. Please try again.",
            });
        } finally {
            setTesting(false);
        }
    };

    return (
        <>
            <Head title={`Agent Settings - ${project.name}`} />

            <AppLayout>
                <div className="p-6 space-y-6">
                    <div>
                        <h1 className="text-3xl font-bold">Agent Settings</h1>
                        <p className="text-muted-foreground mt-2">
                            Configure which AI agent to use for this project and
                            manage API keys.
                        </p>
                    </div>

                    <div className="grid gap-6 max-w-4xl">
                        <Card>
                            <CardHeader>
                                <CardTitle>Default Agent</CardTitle>
                                <CardDescription>
                                    Choose which AI agent should be used for
                                    this project by default.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Form {...updateDefault.form(project.id)}>
                                    {({ errors, processing }) => (
                                        <FieldSet className="space-y-6">
                                            <FieldGroup>
                                                <Field>
                                                    <RadioGroup
                                                        defaultValue={
                                                            agentSetting.default_agent
                                                        }
                                                        name="default_agent"
                                                        className="grid grid-rows-2 gap-4"
                                                    >
                                                        <FieldLabel htmlFor="claude-code">
                                                            <Field orientation="horizontal">
                                                                <FieldContent>
                                                                    <FieldTitle>
                                                                        Claude
                                                                        Code
                                                                    </FieldTitle>
                                                                    <FieldDescription>
                                                                        Anthropic's
                                                                        Claude
                                                                        AI
                                                                        optimized
                                                                        for
                                                                        coding
                                                                        tasks
                                                                        <Badge
                                                                            variant="secondary"
                                                                            className="ml-2 text-xs"
                                                                        >
                                                                            Recommended
                                                                        </Badge>
                                                                    </FieldDescription>
                                                                </FieldContent>
                                                                <RadioGroupItem
                                                                    value="claude-code"
                                                                    id="claude-code"
                                                                />
                                                            </Field>
                                                        </FieldLabel>

                                                        <FieldLabel htmlFor="opencode">
                                                            <Field orientation="horizontal">
                                                                <FieldContent>
                                                                    <FieldTitle>
                                                                        OpenCode
                                                                    </FieldTitle>
                                                                    <FieldDescription>
                                                                        OpenAI-powered
                                                                        coding
                                                                        assistant
                                                                    </FieldDescription>
                                                                </FieldContent>
                                                                <RadioGroupItem
                                                                    value="opencode"
                                                                    id="opencode"
                                                                />
                                                            </Field>
                                                        </FieldLabel>
                                                    </RadioGroup>
                                                    {errors.default_agent && (
                                                        <p className="text-sm text-destructive">
                                                            {
                                                                errors.default_agent
                                                            }
                                                        </p>
                                                    )}
                                                </Field>

                                                <div className="flex justify-end pt-4">
                                                    <Button
                                                        type="submit"
                                                        disabled={processing}
                                                    >
                                                        {processing
                                                            ? "Saving..."
                                                            : "Save Default Agent"}
                                                    </Button>
                                                </div>
                                            </FieldGroup>
                                        </FieldSet>
                                    )}
                                </Form>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>API Keys</CardTitle>
                                <CardDescription>
                                    Manage API keys for each agent. Keys are
                                    stored encrypted.
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <Tabs defaultValue="claude-code">
                                    <TabsList className="grid w-full grid-cols-2">
                                        <TabsTrigger value="claude-code">
                                            Claude Code
                                        </TabsTrigger>
                                        <TabsTrigger value="opencode">
                                            OpenCode
                                        </TabsTrigger>
                                    </TabsList>

                                    <TabsContent
                                        value="claude-code"
                                        className="space-y-4"
                                    >
                                        <div className="flex items-center justify-between">
                                            <div className="flex items-center gap-2">
                                                {agentSetting.has_claude_code_api_key ? (
                                                    <CheckCircle2 className="h-5 w-5 text-green-600" />
                                                ) : (
                                                    <Circle className="h-5 w-5 text-muted-foreground" />
                                                )}
                                                <span className="text-sm font-medium">
                                                    {agentSetting.has_claude_code_api_key
                                                        ? "API Key Configured"
                                                        : "No API Key"}
                                                </span>
                                            </div>
                                            {agentSetting.claude_code_last_tested_at && (
                                                <span className="text-xs text-muted-foreground">
                                                    Last tested:{" "}
                                                    {
                                                        agentSetting.claude_code_last_tested_at
                                                    }
                                                </span>
                                            )}
                                        </div>

                                        <Form {...storeApiKey.form(project.id)}>
                                            {({ errors, processing }) => (
                                                <FieldSet className="space-y-4">
                                                    <Input
                                                        type="hidden"
                                                        name="agent_type"
                                                        value="claude-code"
                                                    />
                                                    <FieldGroup>
                                                        <Field>
                                                            <FieldLabel htmlFor="claude_api_key">
                                                                API Key
                                                            </FieldLabel>
                                                            <Input
                                                                id="claude_api_key"
                                                                name="api_key"
                                                                type="password"
                                                                placeholder={
                                                                    agentSetting.has_claude_code_api_key
                                                                        ? "••••••••••••••••••••••••••••••••"
                                                                        : "sk-ant-..."
                                                                }
                                                            />
                                                            <FieldDescription>
                                                                Your Anthropic
                                                                API key for
                                                                Claude Code
                                                            </FieldDescription>
                                                            {errors.api_key && (
                                                                <p className="text-sm text-destructive">
                                                                    {
                                                                        errors.api_key
                                                                    }
                                                                </p>
                                                            )}
                                                        </Field>

                                                        <div className="flex gap-3">
                                                            <Button
                                                                type="submit"
                                                                disabled={
                                                                    processing
                                                                }
                                                            >
                                                                {processing
                                                                    ? "Saving..."
                                                                    : "Save API Key"}
                                                            </Button>
                                                            <Button
                                                                type="button"
                                                                variant="outline"
                                                                disabled={
                                                                    !agentSetting.has_claude_code_api_key ||
                                                                    testingClaudeCode
                                                                }
                                                                onClick={() =>
                                                                    handleTestConnection(
                                                                        "claude-code",
                                                                    )
                                                                }
                                                            >
                                                                {testingClaudeCode ? (
                                                                    <>
                                                                        <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                                                                        Testing...
                                                                    </>
                                                                ) : (
                                                                    "Test Connection"
                                                                )}
                                                            </Button>
                                                        </div>
                                                    </FieldGroup>
                                                </FieldSet>
                                            )}
                                        </Form>

                                        {claudeCodeTestResult && (
                                            <Alert
                                                variant={
                                                    claudeCodeTestResult.success
                                                        ? "default"
                                                        : "destructive"
                                                }
                                            >
                                                <AlertTitle>
                                                    {claudeCodeTestResult.success
                                                        ? "Success"
                                                        : "Error"}
                                                </AlertTitle>
                                                <AlertDescription>
                                                    {
                                                        claudeCodeTestResult.message
                                                    }
                                                </AlertDescription>
                                            </Alert>
                                        )}
                                    </TabsContent>

                                    <TabsContent
                                        value="opencode"
                                        className="space-y-4"
                                    >
                                        <div className="flex items-center justify-between">
                                            <div className="flex items-center gap-2">
                                                {agentSetting.has_opencode_api_key ? (
                                                    <CheckCircle2 className="h-5 w-5 text-green-600" />
                                                ) : (
                                                    <Circle className="h-5 w-5 text-muted-foreground" />
                                                )}
                                                <span className="text-sm font-medium">
                                                    {agentSetting.has_opencode_api_key
                                                        ? "API Key Configured"
                                                        : "No API Key"}
                                                </span>
                                            </div>
                                            {agentSetting.opencode_last_tested_at && (
                                                <span className="text-xs text-muted-foreground">
                                                    Last tested:{" "}
                                                    {
                                                        agentSetting.opencode_last_tested_at
                                                    }
                                                </span>
                                            )}
                                        </div>

                                        <Form {...storeApiKey.form(project.id)}>
                                            {({ errors, processing }) => (
                                                <FieldSet className="space-y-4">
                                                    <Input
                                                        type="hidden"
                                                        name="agent_type"
                                                        value="opencode"
                                                    />
                                                    <FieldGroup>
                                                        <Field>
                                                            <FieldLabel htmlFor="opencode_api_key">
                                                                API Key
                                                            </FieldLabel>
                                                            <Input
                                                                id="opencode_api_key"
                                                                name="api_key"
                                                                type="password"
                                                                placeholder={
                                                                    agentSetting.has_opencode_api_key
                                                                        ? "••••••••••••••••••••••••••••••••"
                                                                        : "sk-..."
                                                                }
                                                            />
                                                            <FieldDescription>
                                                                Your OpenAI API
                                                                key for OpenCode
                                                            </FieldDescription>
                                                            {errors.api_key && (
                                                                <p className="text-sm text-destructive">
                                                                    {
                                                                        errors.api_key
                                                                    }
                                                                </p>
                                                            )}
                                                        </Field>

                                                        <div className="flex gap-3">
                                                            <Button
                                                                type="submit"
                                                                disabled={
                                                                    processing
                                                                }
                                                            >
                                                                {processing
                                                                    ? "Saving..."
                                                                    : "Save API Key"}
                                                            </Button>
                                                            <Button
                                                                type="button"
                                                                variant="outline"
                                                                disabled={
                                                                    !agentSetting.has_opencode_api_key ||
                                                                    testingOpenCode
                                                                }
                                                                onClick={() =>
                                                                    handleTestConnection(
                                                                        "opencode",
                                                                    )
                                                                }
                                                            >
                                                                {testingOpenCode ? (
                                                                    <>
                                                                        <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                                                                        Testing...
                                                                    </>
                                                                ) : (
                                                                    "Test Connection"
                                                                )}
                                                            </Button>
                                                        </div>
                                                    </FieldGroup>
                                                </FieldSet>
                                            )}
                                        </Form>

                                        {openCodeTestResult && (
                                            <Alert
                                                variant={
                                                    openCodeTestResult.success
                                                        ? "default"
                                                        : "destructive"
                                                }
                                            >
                                                <AlertTitle>
                                                    {openCodeTestResult.success
                                                        ? "Success"
                                                        : "Error"}
                                                </AlertTitle>
                                                <AlertDescription>
                                                    {openCodeTestResult.message}
                                                </AlertDescription>
                                            </Alert>
                                        )}
                                    </TabsContent>
                                </Tabs>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </AppLayout>
        </>
    );
}
