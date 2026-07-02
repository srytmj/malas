import * as React from 'react';
import { cn } from '@/lib/utils';

interface TabsContextValue {
    value: string;
    onValueChange: (value: string) => void;
}

const TabsContext = React.createContext<TabsContextValue>({
    value: '',
    onValueChange: () => {},
});

interface TabsProps {
    defaultValue?: string;
    value?: string;
    onValueChange?: (value: string) => void;
    children: React.ReactNode;
    className?: string;
}

function Tabs({ defaultValue = '', value, onValueChange, children, className }: TabsProps) {
    const [internalValue, setInternalValue] = React.useState(defaultValue);
    const current = value ?? internalValue;
    const handleChange = onValueChange ?? setInternalValue;

    return (
        <TabsContext.Provider value={{ value: current, onValueChange: handleChange }}>
            <div className={cn('w-full', className)}>
                {children}
            </div>
        </TabsContext.Provider>
    );
}

function TabsList({ children, className }: { children: React.ReactNode; className?: string }) {
    return (
        <div
            role="tablist"
            className={cn(
                'inline-flex h-9 items-center justify-center rounded-lg bg-muted p-1 text-muted-foreground',
                className,
            )}
        >
            {children}
        </div>
    );
}

interface TabsTriggerProps {
    value: string;
    children: React.ReactNode;
    className?: string;
}

function TabsTrigger({ value, children, className }: TabsTriggerProps) {
    const { value: current, onValueChange } = React.useContext(TabsContext);
    const isActive = current === value;

    return (
        <button
            type="button"
            role="tab"
            aria-selected={isActive}
            data-state={isActive ? 'active' : 'inactive'}
            onClick={() => onValueChange(value)}
            className={cn(
                'inline-flex items-center justify-center whitespace-nowrap rounded-md px-3 py-1 text-sm font-medium ring-offset-background transition-all',
                'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2',
                'disabled:pointer-events-none disabled:opacity-50',
                isActive
                    ? 'bg-background text-foreground shadow'
                    : 'hover:bg-background/50 hover:text-foreground',
                className,
            )}
        >
            {children}
        </button>
    );
}

interface TabsContentProps {
    value: string;
    children: React.ReactNode;
    className?: string;
}

function TabsContent({ value, children, className }: TabsContentProps) {
    const { value: current } = React.useContext(TabsContext);
    if (current !== value) return null;

    return (
        <div
            role="tabpanel"
            data-state={current === value ? 'active' : 'inactive'}
            className={cn(
                'ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2',
                className,
            )}
        >
            {children}
        </div>
    );
}

export { Tabs, TabsList, TabsTrigger, TabsContent };
