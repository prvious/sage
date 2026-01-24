import { createContext, useCallback, useContext, useState, ReactNode } from 'react';

interface QuickTaskContextValue {
    isOpen: boolean;
    open: () => void;
    close: () => void;
    toggle: () => void;
}

const QuickTaskContext = createContext<QuickTaskContextValue | null>(null);

interface QuickTaskProviderProps {
    children: ReactNode;
}

export function QuickTaskProvider({ children }: QuickTaskProviderProps) {
    const [isOpen, setIsOpen] = useState(false);

    const open = useCallback(() => setIsOpen(true), []);
    const close = useCallback(() => setIsOpen(false), []);
    const toggle = useCallback(() => setIsOpen((prev) => !prev), []);

    return <QuickTaskContext.Provider value={{ isOpen, open, close, toggle }}>{children}</QuickTaskContext.Provider>;
}

export function useQuickTask() {
    const context = useContext(QuickTaskContext);
    if (!context) {
        throw new Error('useQuickTask must be used within a QuickTaskProvider');
    }
    return context;
}
