import { Head, router } from '@inertiajs/react';
import { AlertCircle, CheckCircle2, Download, Loader2 } from 'lucide-react';
import ScanController, { index as scanIndex } from '@/actions/App/Http/Controllers/Shopify/ScanController';
import { Button } from '@/components/ui/button';
import { useEffect } from 'react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import QualityScoreBadge from '@/components/quality-score-badge';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, Scan, ScanIssue } from '@/types';
import { index } from '@/routes/stores';

type StoreProps = {
    id: number;
    shop_domain: string;
    shop_name: string | null;
};

type ResourceTypeSummary = {
    total_occurrences: number;
    issue_count: number;
};

type Props = {
    store: StoreProps;
    scan: Scan;
    issues: ScanIssue[];
    issuesByType: Record<string, number>;
    issuesByResourceType: Record<string, ResourceTypeSummary>;
};

const ISSUE_TYPE_LABELS: Record<string, string> = {
    duplicate_namespace: 'Duplicate Namespace',
    definition_without_values: 'Definition Without Values',
    value_without_definition: 'Value Without Definition',
    empty_metafield: 'Empty Metafield',
    unused_metafield: 'Unused Metafield',
    long_text_value: 'Long Text Value',
    seo_duplicate: 'SEO Duplicate',
};

const ISSUE_TYPE_COLORS: Record<string, string> = {
    duplicate_namespace: 'bg-orange-100 text-orange-800 dark:bg-orange-900/20 dark:text-orange-400',
    definition_without_values: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400',
    value_without_definition: 'bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-400',
    empty_metafield: 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400',
    unused_metafield: 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400',
    long_text_value: 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400',
    seo_duplicate: 'bg-pink-100 text-pink-800 dark:bg-pink-900/20 dark:text-pink-400',
};

const RESOURCE_TYPE_LABELS: Record<string, string> = {
    product: 'Products',
    variant: 'Variants',
    collection: 'Collections',
    global: 'Global / Shop',
};

export default function ScanShow({ store, scan, issues, issuesByType, issuesByResourceType }: Props) {
    const storeName = store.shop_name ?? store.shop_domain;
    const isInProgress = scan.status === 'pending' || scan.status === 'running';

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Stores', href: index().url },
        { title: storeName, href: scanIndex(store.id).url },
        { title: 'Scan' },
    ];

    useEffect(() => {
        if (!isInProgress) return;

        const interval = setInterval(() => {
            router.reload({ only: ['scan', 'issues', 'issuesByType', 'issuesByResourceType'] });
        }, 3000);

        return () => clearInterval(interval);
    }, [isInProgress]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Scan — ${storeName}`} />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
                <div className="flex items-start justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold">Scan Report</h1>
                        <p className="text-sm text-muted-foreground">{storeName}</p>
                    </div>
                    <div className="flex items-center gap-3">
                        {scan.status === 'complete' && (
                            <>
                                <QualityScoreBadge score={scan.quality_score} size="lg" />
                                <Button variant="outline" size="sm" asChild>
                                    <a href={ScanController.export({ shopifyStore: store.id, scan: scan.id }).url}>
                                        <Download className="mr-1.5 size-3.5" />
                                        Export CSV
                                    </a>
                                </Button>
                            </>
                        )}
                    </div>
                </div>

                {/* Stat Cards */}
                <div className="grid gap-4 sm:grid-cols-3">
                    {[
                        { label: 'Total Metafields', value: scan.total_metafields },
                        { label: 'Total Definitions', value: scan.total_definitions },
                        { label: 'Issues Found', value: scan.total_issues },
                    ].map(({ label, value }) => (
                        <Card key={label} className={isInProgress ? 'border-primary/20 bg-primary/5' : ''}>
                            <CardHeader className="pb-2">
                                <CardTitle className="text-sm font-medium text-muted-foreground">{label}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                {isInProgress ? (
                                    <div className="h-9 w-16 animate-pulse rounded-md bg-primary/20" />
                                ) : (
                                    <p className="text-3xl font-bold">{value}</p>
                                )}
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {/* Status */}
                {isInProgress && (
                    <div className="flex flex-col items-center gap-6 rounded-xl border border-primary/20 bg-primary/5 py-16">
                        <div className="relative flex items-center justify-center">
                            <span className="absolute size-28 animate-ping rounded-full bg-primary opacity-10" />
                            <span className="absolute size-20 animate-ping rounded-full bg-primary opacity-20 [animation-delay:300ms]" />
                            <div className="relative flex size-14 items-center justify-center rounded-full bg-primary shadow-lg shadow-primary/40">
                                <Loader2 className="size-7 animate-spin text-white" />
                            </div>
                        </div>
                        <div className="flex flex-col items-center gap-1 text-center">
                            <p className="text-xl font-semibold text-primary">
                                {scan.status === 'pending' ? 'Scan queued' : 'Scan in progress'}
                            </p>
                            <p className="text-sm text-muted-foreground">
                                {scan.status === 'pending'
                                    ? 'Starting soon…'
                                    : 'Analysing your metafields — page refreshes every 3 seconds'}
                            </p>
                        </div>
                    </div>
                )}

                {scan.status === 'failed' && (
                    <Alert variant="destructive">
                        <AlertCircle className="size-4" />
                        <AlertTitle>Scan failed</AlertTitle>
                        <AlertDescription>{scan.error_message}</AlertDescription>
                    </Alert>
                )}

                {scan.status === 'complete' && (
                    <>
                        <Alert>
                            <CheckCircle2 className="size-4 text-green-600" />
                            <AlertTitle>Scan complete</AlertTitle>
                            <AlertDescription>
                                Completed{' '}
                                {scan.scanned_at ? new Date(scan.scanned_at).toLocaleString() : ''}
                            </AlertDescription>
                        </Alert>

                        {/* Classification by resource type */}
                        {Object.keys(issuesByResourceType).length > 0 && (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-base">Issues by Area</CardTitle>
                                </CardHeader>
                                <CardContent className="p-0">
                                    <div className="divide-y">
                                        {Object.entries(issuesByResourceType)
                                            .sort(([, a], [, b]) => b.total_occurrences - a.total_occurrences)
                                            .map(([resourceType, summary]) => {
                                                const pct = scan.total_issues > 0
                                                    ? Math.round((summary.issue_count / scan.total_issues) * 100)
                                                    : 0;
                                                return (
                                                    <div key={resourceType} className="flex items-center gap-4 px-4 py-3">
                                                        <div className="w-28 shrink-0 text-sm font-medium">
                                                            {RESOURCE_TYPE_LABELS[resourceType] ?? resourceType}
                                                        </div>
                                                        <div className="flex-1">
                                                            <div className="h-2 overflow-hidden rounded-full bg-muted">
                                                                <div
                                                                    className="h-full rounded-full bg-primary transition-all"
                                                                    style={{ width: `${pct}%` }}
                                                                />
                                                            </div>
                                                        </div>
                                                        <div className="w-32 shrink-0 text-right text-sm text-muted-foreground">
                                                            {summary.issue_count} issues · {summary.total_occurrences} occ.
                                                        </div>
                                                    </div>
                                                );
                                            })}
                                    </div>
                                </CardContent>
                            </Card>
                        )}

                        {/* Issue type badges */}
                        {Object.keys(issuesByType).length > 0 && (
                            <div className="flex flex-wrap gap-2">
                                {Object.entries(issuesByType).map(([type, count]) => (
                                    <span
                                        key={type}
                                        className={`inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-medium ${ISSUE_TYPE_COLORS[type] ?? 'bg-gray-100 text-gray-800'}`}
                                    >
                                        {ISSUE_TYPE_LABELS[type] ?? type}
                                        <span className="font-bold">{count}</span>
                                    </span>
                                ))}
                            </div>
                        )}

                        {/* Issues table */}
                        {issues.length > 0 ? (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Issues</CardTitle>
                                </CardHeader>
                                <CardContent className="p-0">
                                    <div className="overflow-x-auto">
                                        <table className="w-full text-sm">
                                            <thead className="border-b bg-muted/50 text-xs text-muted-foreground">
                                                <tr>
                                                    <th className="px-4 py-3 text-left font-medium">Namespace</th>
                                                    <th className="px-4 py-3 text-left font-medium">Key</th>
                                                    <th className="px-4 py-3 text-left font-medium">Resource Type</th>
                                                    <th className="px-4 py-3 text-left font-medium">Issue Type</th>
                                                    <th className="px-4 py-3 text-right font-medium">Occurrences</th>
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y">
                                                {issues.map((issue) => (
                                                    <tr key={issue.id} className="hover:bg-muted/30">
                                                        <td className="px-4 py-3 font-mono text-xs">{issue.namespace}</td>
                                                        <td className="px-4 py-3 font-mono text-xs">{issue.key}</td>
                                                        <td className="px-4 py-3">
                                                            <Badge variant="outline">{issue.resource_type}</Badge>
                                                        </td>
                                                        <td className="px-4 py-3">
                                                            <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${ISSUE_TYPE_COLORS[issue.issue_type] ?? 'bg-gray-100 text-gray-800'}`}>
                                                                {ISSUE_TYPE_LABELS[issue.issue_type] ?? issue.issue_type}
                                                            </span>
                                                        </td>
                                                        <td className="px-4 py-3 text-right">{issue.occurrences}</td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                </CardContent>
                            </Card>
                        ) : (
                            <div className="flex flex-col items-center gap-2 py-12 text-center">
                                <CheckCircle2 className="size-10 text-green-500" />
                                <p className="font-medium">No issues found!</p>
                                <p className="text-sm text-muted-foreground">Your metafields look clean.</p>
                            </div>
                        )}
                    </>
                )}

            </div>
        </AppLayout>
    );
}
