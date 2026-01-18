import { AppLayout } from "@/components/layout/app-layout";
import { Head } from "@inertiajs/react";
import { Badge } from "@/components/ui/badge";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { GeneralSettings } from "@/components/settings/general-settings";
import { ServerSettings } from "@/components/settings/server-settings";
import { DangerZone } from "@/components/settings/danger-zone";
import { Project, ServerStatus } from "@/types";

interface SettingsProps {
    project: Project;
    serverStatus: ServerStatus;
}

export default function Settings({ project, serverStatus }: SettingsProps) {
    return (
        <>
            <Head title={`${project.name} - Settings`} />
            <AppLayout>
                <div className="p-6 space-y-6">
                    <div className="flex items-center gap-3">
                        <h1 className="text-3xl font-bold">{project.name}</h1>
                        <Badge variant="secondary">Settings</Badge>
                    </div>

                    <Tabs defaultValue="general" className="w-full">
                        <TabsList>
                            <TabsTrigger value="general">General</TabsTrigger>
                            <TabsTrigger value="server">Server</TabsTrigger>
                        </TabsList>

                        <TabsContent value="general" className="space-y-6 mt-6">
                            <GeneralSettings project={project} />
                            <DangerZone project={project} />
                        </TabsContent>

                        <TabsContent value="server" className="space-y-6 mt-6">
                            <ServerSettings
                                project={project}
                                serverStatus={serverStatus}
                            />
                        </TabsContent>
                    </Tabs>
                </div>
            </AppLayout>
        </>
    );
}
