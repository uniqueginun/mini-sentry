import { cn } from '@/lib/utils';

export default function Sparkline({
    values,
    className,
    filled = false,
}: {
    values: number[];
    className?: string;
    filled?: boolean;
}) {
    const width = filled ? 640 : 96;
    const height = filled ? 96 : 28;
    const max = Math.max(...values, 1);
    const step = values.length > 1 ? width / (values.length - 1) : width;
    const points = values.map((value, index) => {
        const x = values.length === 1 ? width / 2 : index * step;
        const y = height - (value / max) * (height - 4) - 2;

        return `${x},${y}`;
    });
    const polyline = points.join(' ');
    const area = filled
        ? `0,${height} ${polyline} ${width},${height}`
        : undefined;

    return (
        <svg
            viewBox={`0 0 ${width} ${height}`}
            className={cn('text-sky-600 dark:text-sky-400', className)}
            preserveAspectRatio="none"
            aria-hidden
        >
            {area ? (
                <polygon
                    points={area}
                    className="fill-current/20"
                    stroke="none"
                />
            ) : null}
            <polyline
                fill="none"
                stroke="currentColor"
                strokeWidth={filled ? 2 : 1.5}
                strokeLinejoin="round"
                strokeLinecap="round"
                points={polyline}
            />
        </svg>
    );
}
