import { Moon, Sun, Monitor } from "lucide-react";
import { Button } from "@/components/ui/button";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { useAppearance, type Appearance } from "@/hooks/use-appearance";

export function ThemeToggler() {
    const { appearance, updateAppearance } = useAppearance();

    const themes = [
        { value: "system" as const, label: "System", icon: Monitor },
        { value: "light" as const, label: "Light", icon: Sun },
        { value: "dark" as const, label: "Dark", icon: Moon },
    ];

    const currentTheme =
        themes.find((t) => t.value === appearance) ?? themes[0];
    const CurrentIcon = currentTheme.icon;

    return (
        <div className="fixed left-4 bottom-4 z-50">
            <DropdownMenu>
                <DropdownMenuTrigger
                    render={
                        <Button variant="link" size="sm" className="gap-2">
                            <CurrentIcon className="h-4 w-4" />
                            <span className="text-xs">
                                {currentTheme.label}
                            </span>
                        </Button>
                    }
                />
                <DropdownMenuContent align="start" side="top" sideOffset={8}>
                    {themes.map((t) => {
                        const Icon = t.icon;
                        const isSelected = appearance === t.value;

                        return (
                            <DropdownMenuItem
                                key={t.value}
                                className="gap-2"
                                onClick={() => updateAppearance(t.value)}
                                data-selected={isSelected}
                            >
                                <Icon className="h-4 w-4" />
                                <span>{t.label}</span>
                                {isSelected && (
                                    <span className="ml-auto text-primary">
                                        ✓
                                    </span>
                                )}
                            </DropdownMenuItem>
                        );
                    })}
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    );
}
