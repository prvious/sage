import { ReactNode } from "react";
import { Card } from "@/components/ui/card";
import { SageLogo } from "@/components/branding/sage-logo";
import { ThemeToggler } from "@/components/theme-toggler";
import { cn } from "@/lib/utils";

interface CenteredCardLayoutProps {
    children: ReactNode;
    cardClassName?: string;
}

export function CenteredCardLayout({
    children,
    cardClassName,
}: CenteredCardLayoutProps) {
    return (
        <div className="min-h-screen bg-muted flex flex-col items-center justify-center p-4 gap-5">
            <div>
                <SageLogo />
            </div>

            <Card className={cn("w-full max-w-xl", cardClassName)}>
                {children}
            </Card>

            <div className="justify-center flex items-center">
                <p className="text-xs text-muted-foreground font-semibold">
                    AI Agent Orchestrator for Laravel Application Development
                </p>
            </div>

            {/* Theme Toggler - Fixed Bottom Left */}
            <ThemeToggler />
        </div>
    );
}
