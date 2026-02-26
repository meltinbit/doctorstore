type Props = {
    score: number | null;
    size?: 'sm' | 'md' | 'lg';
};

function scoreColor(score: number): string {
    if (score >= 80) return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400';
    if (score >= 50) return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400';
    return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400';
}

function scoreLabel(score: number): string {
    if (score >= 80) return 'Good';
    if (score >= 50) return 'Fair';
    return 'Poor';
}

const SIZE_CLASSES = {
    sm: 'px-2 py-0.5 text-xs',
    md: 'px-3 py-1 text-sm',
    lg: 'px-4 py-2 text-base',
};

export default function QualityScoreBadge({ score, size = 'md' }: Props) {
    if (score === null || score === undefined) {
        return (
            <span className={`inline-flex items-center gap-1 rounded-full bg-muted font-medium text-muted-foreground ${SIZE_CLASSES[size]}`}>
                —
            </span>
        );
    }

    return (
        <span className={`inline-flex items-center gap-1.5 rounded-full font-semibold ${scoreColor(score)} ${SIZE_CLASSES[size]}`}>
            <span>{score}</span>
            <span className="font-normal opacity-75">{scoreLabel(score)}</span>
        </span>
    );
}
