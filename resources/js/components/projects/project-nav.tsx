import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import { index as issuesIndex } from '@/routes/projects/issues';
import type { ProjectSummary } from '@/types';

export default function ProjectNav({
    currentTeam,
    project,
    current,
}: {
    currentTeam: { slug: string };
    project: ProjectSummary;
    current: 'issues' | 'releases' | 'alerts' | 'settings';
}) {
    const items = [
        {
            key: 'issues' as const,
            label: 'Issues',
            href: issuesIndex({
                current_team: currentTeam,
                project: project.id,
            }),
        },
        { key: 'releases' as const, label: 'Releases' },
        { key: 'alerts' as const, label: 'Alerts' },
        { key: 'settings' as const, label: 'Settings' },
    ];

    return (
        <nav
            data-test="project-nav"
            className="flex flex-wrap items-center gap-1 border-b"
        >
            {items.map((item) => {
                const isActive = item.key === current;
                const className = cn(
                    '-mb-px border-b-2 px-3 py-2 text-sm font-medium transition-colors',
                    isActive
                        ? 'border-foreground text-foreground'
                        : 'border-transparent text-muted-foreground hover:text-foreground',
                );

                if (!('href' in item) || item.href === undefined) {
                    return (
                        <span
                            key={item.key}
                            className={cn(
                                className,
                                'cursor-not-allowed opacity-50',
                            )}
                            title="Coming soon"
                        >
                            {item.label}
                        </span>
                    );
                }

                return (
                    <Link
                        key={item.key}
                        href={item.href}
                        className={className}
                        prefetch
                    >
                        {item.label}
                    </Link>
                );
            })}
        </nav>
    );
}
