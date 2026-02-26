import { Form, Head, Link } from '@inertiajs/react';
import { AlertCircle, CheckCircle2, Clock, Loader2, ScanLine } from 'lucide-react';
import QualityScoreBadge from '@/components/quality-score-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, Scan } from '@/types';
import { index as storesIndex } from '@/routes/stores';
import { show as scanShow } from '@/actions/App/Http/Controllers/Shopify/ScanController';
import { store as scanStore } from '@/actions/App/Http/Controllers/Shopify/ScanController';

type StoreProps = {
    id: number;
    shop_domain: string;
    shop_name: string | null;
};

type ScanRow = Scan & {
    created_at: string;
    delta_issues: number | null;
    delta_score: number | null;
};

function DeltaBadge({ value, positiveIsGood }: { value: number | null; positiveIsGood: boolean }) {
    if (value === null) return <span className="text-muted-foreground">—</span>;
    if (value === 0) return <span className="text-muted-foreground">±0</span>;

    const isGood = positiveIsGood ? value > 0 : value < 0;
    const color = isGood
        ? 'text-green-600 dark:text-green-400'
        : 'text-red-600 dark:text-red-400';
    const prefix = value > 0 ? '+' : '';

    return <span className={`font-medium ${color}`}>{prefix}{value}</span>;
}

const STATUS_ICON: Record<string, React.ReactNode> = {
    complete: <CheckCircle2 className="size-4 text-green-500" />,
    failed: <AlertCircle className="size-4 text-red-500" />,
    running: <Loader2 className="size-4 animate-spin text-blue-500" />,
    pending: <Clock className="size-4 text-muted-foreground" />,
};

const STATUS_LABEL: Record<string, string> = {
    complete: 'Complete',
    failed: 'Failed',
    running: 'Running',
    pending: 'Pending',
};

export default function ScansIndex({ store, scans }: { store: StoreProps; scans: ScanRow[] }) {
    const storeName = store.shop_name ?? store.shop_domain;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Stores', href: storesIndex().url },
        { title: storeName },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Scans — ${storeName}`} />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">Scan History</h1>
                        <p className="text-sm text-muted-foreground">{storeName}</p>
                    </div>

                    <Form {...scanStore.form(store.id)}>
                        {({ processing }) => (
                            <Button disabled={processing} asChild>
                                <button type="submit">Run New Scan</button>
                            </Button>
                        )}
                    </Form>
                </div>

                {scans.length === 0 ? (
                    <div className="flex flex-1 flex-col items-center justify-center gap-4 rounded-xl border-2 border-dashed border-sidebar-border/70 py-16">
                        <ScanLine className="size-12 text-muted-foreground/40" />
                        <div className="text-center">
                            <p className="font-medium text-muted-foreground">No scans yet</p>
                            <p className="text-sm text-muted-foreground/70">
                                Run a scan to analyse your metafields.
                            </p>
                        </div>
                    </div>
                ) : (
                    <Card>
                        <CardHeader>
                            <CardTitle>Scans</CardTitle>
                        </CardHeader>
                        <CardContent className="p-0">
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead className="border-b bg-muted/50 text-xs text-muted-foreground">
                                        <tr>
                                            <th className="px-4 py-3 text-left font-medium">Status</th>
                                            <th className="px-4 py-3 text-left font-medium">Date</th>
                                            <th className="px-4 py-3 text-right font-medium">Score</th>
                                            <th className="px-4 py-3 text-right font-medium">Δ Score</th>
                                            <th className="px-4 py-3 text-right font-medium">Metafields</th>
                                            <th className="px-4 py-3 text-right font-medium">Definitions</th>
                                            <th className="px-4 py-3 text-right font-medium">Issues</th>
                                            <th className="px-4 py-3 text-right font-medium">Δ Issues</th>
                                            <th className="px-4 py-3 text-right font-medium"></th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y">
                                        {scans.map((scan) => (
                                            <tr key={scan.id} className="hover:bg-muted/30">
                                                <td className="px-4 py-3">
                                                    <span className="flex items-center gap-2">
                                                        {STATUS_ICON[scan.status]}
                                                        {STATUS_LABEL[scan.status]}
                                                    </span>
                                                </td>
                                                <td className="px-4 py-3 text-muted-foreground">
                                                    {scan.scanned_at
                                                        ? new Date(scan.scanned_at).toLocaleString()
                                                        : new Date(scan.created_at).toLocaleString()}
                                                </td>
                                                <td className="px-4 py-3 text-right">
                                                    {scan.status === 'complete'
                                                        ? <QualityScoreBadge score={scan.quality_score} size="sm" />
                                                        : <span className="text-muted-foreground">—</span>}
                                                </td>
                                                <td className="px-4 py-3 text-right">
                                                    <DeltaBadge value={scan.delta_score} positiveIsGood />
                                                </td>
                                                <td className="px-4 py-3 text-right">
                                                    {scan.total_metafields || '—'}
                                                </td>
                                                <td className="px-4 py-3 text-right">
                                                    {scan.total_definitions || '—'}
                                                </td>
                                                <td className="px-4 py-3 text-right font-medium">
                                                    {scan.status === 'complete' ? scan.total_issues : '—'}
                                                </td>
                                                <td className="px-4 py-3 text-right">
                                                    <DeltaBadge value={scan.delta_issues} positiveIsGood={false} />
                                                </td>
                                                <td className="px-4 py-3 text-right">
                                                    {(scan.status === 'complete' || scan.status === 'failed' || scan.status === 'running' || scan.status === 'pending') && (
                                                        <Link
                                                            href={scanShow({ shopifyStore: store.id, scan: scan.id }).url}
                                                            className="text-xs font-medium underline underline-offset-2 hover:no-underline"
                                                        >
                                                            View →
                                                        </Link>
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
