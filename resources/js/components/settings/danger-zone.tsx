import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from "@/components/ui/alert-dialog";
import { Project } from "@/types";
import { router } from "@inertiajs/react";
import { Trash2 } from "lucide-react";
import { useState } from "react";

interface DangerZoneProps {
    project: Project;
}

export function DangerZone({ project }: DangerZoneProps) {
    const [isDeleting, setIsDeleting] = useState(false);

    const handleDelete = () => {
        setIsDeleting(true);
        router.delete(`/projects/${project.id}`, {
            onFinish: () => {
                setIsDeleting(false);
            },
        });
    };

    return (
        <Card className="border-destructive">
            <CardHeader>
                <CardTitle className="text-destructive">Danger Zone</CardTitle>
                <CardDescription>
                    Irreversible and destructive actions
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div className="flex items-center justify-between">
                    <div>
                        <p className="font-medium">Delete this project</p>
                        <p className="text-sm text-muted-foreground">
                            Once you delete a project, there is no going back.
                            This will remove the project from Sage, but will not
                            delete files from disk.
                        </p>
                    </div>
                    <AlertDialog>
                        <AlertDialogTrigger
                            render={() => (
                                <Button variant="destructive">
                                    <Trash2 className="mr-2 h-4 w-4" />
                                    Delete Project
                                </Button>
                            )}
                        />
                        <AlertDialogContent>
                            <AlertDialogHeader>
                                <AlertDialogTitle>
                                    Delete {project.name}?
                                </AlertDialogTitle>
                                <AlertDialogDescription>
                                    This action cannot be undone. This will
                                    permanently remove the project from Sage and
                                    delete all associated worktrees, tasks, and
                                    specs. Your project files on disk will NOT
                                    be deleted.
                                </AlertDialogDescription>
                            </AlertDialogHeader>
                            <AlertDialogFooter>
                                <AlertDialogCancel>Cancel</AlertDialogCancel>
                                <AlertDialogAction
                                    onClick={handleDelete}
                                    disabled={isDeleting}
                                    className="bg-destructive text-destructive-foreground"
                                >
                                    {isDeleting
                                        ? "Deleting..."
                                        : "Delete Project"}
                                </AlertDialogAction>
                            </AlertDialogFooter>
                        </AlertDialogContent>
                    </AlertDialog>
                </div>
            </CardContent>
        </Card>
    );
}
