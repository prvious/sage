import { useState } from "react";
import { router } from "@inertiajs/react";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Checkbox } from "@/components/ui/checkbox";
import { Label } from "@/components/ui/label";
import { ScrollArea } from "@/components/ui/scroll-area";
import { Separator } from "@/components/ui/separator";

interface Worktree {
    id: number;
    name: string;
    path: string;
}

interface Props {
    sourceId: number;
    sourceType: "project" | "worktree";
    worktrees: Worktree[];
    variables: string[];
    children: React.ReactNode;
}

export default function SyncEnvDialog({
    sourceId,
    sourceType,
    worktrees,
    variables,
    children,
}: Props) {
    const [open, setOpen] = useState(false);
    const [selectedWorktrees, setSelectedWorktrees] = useState<number[]>([]);
    const [selectedVariables, setSelectedVariables] = useState<string[]>([]);
    const [overwrite, setOverwrite] = useState(false);
    const [processing, setProcessing] = useState(false);

    const handleSync = () => {
        setProcessing(true);

        router.post(
            "/environment/sync",
            {
                source_type: sourceType,
                source_id: sourceId,
                targets: selectedWorktrees,
                variables: selectedVariables,
                overwrite,
            },
            {
                onSuccess: () => {
                    setOpen(false);
                    setProcessing(false);
                    setSelectedWorktrees([]);
                    setSelectedVariables([]);
                    setOverwrite(false);
                },
                onError: () => {
                    setProcessing(false);
                },
            },
        );
    };

    const toggleWorktree = (id: number) => {
        setSelectedWorktrees((prev) =>
            prev.includes(id)
                ? prev.filter((wid) => wid !== id)
                : [...prev, id],
        );
    };

    const toggleVariable = (variable: string) => {
        setSelectedVariables((prev) =>
            prev.includes(variable)
                ? prev.filter((v) => v !== variable)
                : [...prev, variable],
        );
    };

    const selectAllWorktrees = () => {
        setSelectedWorktrees(worktrees.map((w) => w.id));
    };

    const deselectAllWorktrees = () => {
        setSelectedWorktrees([]);
    };

    const selectAllVariables = () => {
        setSelectedVariables(variables);
    };

    const deselectAllVariables = () => {
        setSelectedVariables([]);
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{children}</DialogTrigger>
            <DialogContent className="max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Sync Environment Variables</DialogTitle>
                    <DialogDescription>
                        Select worktrees and variables to sync
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-6">
                    <div className="space-y-4">
                        <div className="flex items-center justify-between">
                            <Label className="text-base font-semibold">
                                Target Worktrees
                            </Label>
                            <div className="flex gap-2">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    onClick={selectAllWorktrees}
                                >
                                    Select All
                                </Button>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    onClick={deselectAllWorktrees}
                                >
                                    Deselect All
                                </Button>
                            </div>
                        </div>
                        <ScrollArea className="h-48 rounded border p-4">
                            <div className="space-y-3">
                                {worktrees.map((worktree) => (
                                    <div
                                        key={worktree.id}
                                        className="flex items-center space-x-2"
                                    >
                                        <Checkbox
                                            id={`worktree-${worktree.id}`}
                                            checked={selectedWorktrees.includes(
                                                worktree.id,
                                            )}
                                            onCheckedChange={() =>
                                                toggleWorktree(worktree.id)
                                            }
                                        />
                                        <Label
                                            htmlFor={`worktree-${worktree.id}`}
                                            className="flex-1 cursor-pointer"
                                        >
                                            {worktree.name}
                                        </Label>
                                    </div>
                                ))}
                            </div>
                        </ScrollArea>
                    </div>

                    <Separator />

                    <div className="space-y-4">
                        <div className="flex items-center justify-between">
                            <Label className="text-base font-semibold">
                                Variables to Sync
                            </Label>
                            <div className="flex gap-2">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    onClick={selectAllVariables}
                                >
                                    Select All
                                </Button>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    onClick={deselectAllVariables}
                                >
                                    Deselect All
                                </Button>
                            </div>
                        </div>
                        <ScrollArea className="h-48 rounded border p-4">
                            <div className="space-y-3">
                                {variables.map((variable) => (
                                    <div
                                        key={variable}
                                        className="flex items-center space-x-2"
                                    >
                                        <Checkbox
                                            id={`variable-${variable}`}
                                            checked={selectedVariables.includes(
                                                variable,
                                            )}
                                            onCheckedChange={() =>
                                                toggleVariable(variable)
                                            }
                                        />
                                        <Label
                                            htmlFor={`variable-${variable}`}
                                            className="flex-1 cursor-pointer font-mono text-sm"
                                        >
                                            {variable}
                                        </Label>
                                    </div>
                                ))}
                            </div>
                        </ScrollArea>
                    </div>

                    <Separator />

                    <div className="flex items-center space-x-2">
                        <Checkbox
                            id="overwrite"
                            checked={overwrite}
                            onCheckedChange={(checked) =>
                                setOverwrite(checked as boolean)
                            }
                        />
                        <Label
                            htmlFor="overwrite"
                            className="cursor-pointer text-sm"
                        >
                            Overwrite existing values
                        </Label>
                    </div>
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => setOpen(false)}
                        disabled={processing}
                    >
                        Cancel
                    </Button>
                    <Button
                        type="button"
                        onClick={handleSync}
                        disabled={
                            processing ||
                            selectedWorktrees.length === 0 ||
                            selectedVariables.length === 0
                        }
                    >
                        {processing ? "Syncing..." : "Sync"}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
