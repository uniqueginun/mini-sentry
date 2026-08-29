import { cn } from '@/lib/utils';

const LEVEL_STYLES: Record<string, string> = {
    fatal: 'border-red-200 bg-red-50 text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-300',
    emergency:
        'border-red-200 bg-red-50 text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-300',
    alert: 'border-red-200 bg-red-50 text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-300',
    critical:
        'border-red-200 bg-red-50 text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-300',
    error: 'border-red-200 bg-red-50 text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-300',
    warning:
        'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-300',
    notice: 'border-sky-200 bg-sky-50 text-sky-800 dark:border-sky-900 dark:bg-sky-950 dark:text-sky-300',
    info: 'border-sky-200 bg-sky-50 text-sky-800 dark:border-sky-900 dark:bg-sky-950 dark:text-sky-300',
    debug: 'border-zinc-200 bg-zinc-50 text-zinc-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300',
};

export default function SeverityBadge({ level }: { level: string }) {
    const normalized = level.toLowerCase();

    return (
        <span
            data-test="severity-badge"
            className={cn(
                'inline-flex shrink-0 items-center rounded-full border px-2 py-0.5 text-[11px] font-medium tracking-wide uppercase',
                LEVEL_STYLES[normalized] ?? LEVEL_STYLES.error,
            )}
        >
            {normalized}
        </span>
    );
}
