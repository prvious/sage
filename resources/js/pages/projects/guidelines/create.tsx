import { Head, Link, useForm } from "@inertiajs/react";
import { AppLayout } from "@/components/layout/app-layout";
import { GuidelineCreateProps } from "@/types";
import { Button } from "@/components/ui/button";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Alert, AlertDescription } from "@/components/ui/alert";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { AlertCircle, Save, X } from "lucide-react";
import MDEditor from "@uiw/react-md-editor";
import { useState } from "react";

export default function Create({ project }: GuidelineCreateProps) {
    const { data, setData, post, processing, errors } = useForm({
        filename: "",
        content: "",
        extension: "md",
    });

    const [editorValue, setEditorValue] = useState("");

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(`/projects/${project.id}/guidelines`);
    };

    return (
        <>
            <Head title={`Create Custom Guideline - ${project.name}`} />
            <AppLayout>
                <div className="p-6 space-y-6">
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-3xl font-bold">
                                Create Custom Guideline
                            </h1>
                            <p className="text-muted-foreground mt-2">
                                Create a new guideline file in .ai/guidelines/
                                for custom AI agent instructions
                            </p>
                        </div>
                        <Button
                            variant="outline"
                            render={
                                <Link
                                    href={`/projects/${project.id}/guidelines`}
                                >
                                    Cancel
                                </Link>
                            }
                        >
                            <X className="h-4 w-4 mr-2" />
                            Cancel
                        </Button>
                    </div>

                    <Alert>
                        <AlertCircle className="h-4 w-4" />
                        <AlertDescription>
                            The file extension will be automatically appended
                            based on your selection. Only alphanumeric
                            characters, dashes, and underscores are allowed in
                            filenames.
                        </AlertDescription>
                    </Alert>

                    <form onSubmit={handleSubmit}>
                        <Card>
                            <CardHeader>
                                <CardTitle>Guideline Details</CardTitle>
                                <CardDescription>
                                    Enter the filename and content for your new
                                    custom guideline
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div className="space-y-2 md:col-span-2">
                                        <Label htmlFor="filename">
                                            Filename
                                        </Label>
                                        <Input
                                            id="filename"
                                            type="text"
                                            placeholder="e.g., custom-rules"
                                            value={data.filename}
                                            onChange={(e) =>
                                                setData(
                                                    "filename",
                                                    e.target.value,
                                                )
                                            }
                                            className={
                                                errors.filename
                                                    ? "border-destructive"
                                                    : ""
                                            }
                                        />
                                        {errors.filename && (
                                            <p className="text-sm text-destructive">
                                                {errors.filename}
                                            </p>
                                        )}
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="extension">
                                            File Type
                                        </Label>
                                        <Select
                                            value={data.extension}
                                            onValueChange={(value) =>
                                                setData("extension", value)
                                            }
                                        >
                                            <SelectTrigger id="extension">
                                                <SelectValue placeholder="Select file type" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="md">
                                                    Markdown (.md)
                                                </SelectItem>
                                                <SelectItem value="blade.php">
                                                    Blade Template (.blade.php)
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="content">Content</Label>
                                    <div data-color-mode="light">
                                        <MDEditor
                                            value={editorValue}
                                            onChange={(value) => {
                                                setEditorValue(value || "");
                                                setData("content", value || "");
                                            }}
                                            height={500}
                                            preview="edit"
                                        />
                                    </div>
                                    {errors.content && (
                                        <p className="text-sm text-destructive mt-2">
                                            {errors.content}
                                        </p>
                                    )}
                                </div>

                                {errors.error && (
                                    <Alert variant="destructive">
                                        <AlertCircle className="h-4 w-4" />
                                        <AlertDescription>
                                            {errors.error}
                                        </AlertDescription>
                                    </Alert>
                                )}

                                <div className="flex justify-end gap-2 pt-4">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        render={
                                            <Link
                                                href={`/projects/${project.id}/guidelines`}
                                            >
                                                Cancel
                                            </Link>
                                        }
                                    >
                                        Cancel
                                    </Button>
                                    <Button type="submit" disabled={processing}>
                                        <Save className="h-4 w-4 mr-2" />
                                        {processing
                                            ? "Creating..."
                                            : "Create Guideline"}
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    </form>
                </div>
            </AppLayout>
        </>
    );
}
