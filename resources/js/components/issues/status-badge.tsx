import { cn } from '@/lib/utils';
import type { IssueStatus } from '@/types';

const STATUS_STYLES: Record<IssueStatus, string> = {
    unresolved:
        'border-red-200 bg-red-50 text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-300',
    resolved:
        'border-zinc-200 bg-zinc-50 text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300',
    regressed:
        'border-red-300 bg-red-600 text-white dark:border-red-700 dark:bg-red-700 dark:text-white',
};

export default function StatusBadge({ status }: { status: IssueStatus }) {
    return (
        <span
            data-test="status-badge"
            className={cn(
                'inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-medium tracking-wide uppercase',
                STATUS_STYLES[status],
            )}
        >
            {status}
        </span>
    );
}
