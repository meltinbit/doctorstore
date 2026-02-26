import { Head, Link } from '@inertiajs/react';
import { AlertTriangle, BarChart2, ScanLine, ShoppingBag } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import QualityScoreBadge from '@/components/quality-score-badge';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { dashboard } from '@/routes';
import { index as storesIndex } from '@/routes/stores';
import { index as scanIndex } from '@/actions/App/Http/Controllers/Shopify/ScanController';
import { show as scanShow } from '@/actions/App/Http/Controllers/Shopify/ScanController';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
];

type Stats = {
    total_stores: number;
    total_scans: number;
    total_issues: number;
    last_scan_at: string | null;
};

type StoreSummary = {
    id: number;
    shop_domain: string;
    shop_name: string | null;
    latest_scan: {
        id: number;
        status: string;
        total_issues: number;
        quality_score: number | null;
        scanned_at: string | null;
    } | null;
};

export default function Dashboard({ stats, stores }: { stats: Stats; stores: StoreSummary[] }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
                <div>
                    <h1 className="text-2xl font-semibold">Dashboard</h1>
                    <p className="text-sm text-muted-foreground">
                        Overview of your Shopify metafield health.
                    </p>
                </div>

                {/* Stat cards */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Connected Stores</CardTitle>
                            <ShoppingBag className="size-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <p className="text-3xl font-bold">{stats.total_stores}</p>
                            <Link href={storesIndex().url} className="mt-1 text-xs text-muted-foreground hover:underline">
                                Manage stores →
                            </Link>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Scans</CardTitle>
                            <ScanLine className="size-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <p className="text-3xl font-bold">{stats.total_scans}</p>
                            <p className="mt-1 text-xs text-muted-foreground">Across all stores</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Issues</CardTitle>
                            <AlertTriangle className="size-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <p className="text-3xl font-bold">{stats.total_issues}</p>
                            <p className="mt-1 text-xs text-muted-foreground">From latest scans</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Last Scan</CardTitle>
                            <BarChart2 className="size-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <p className="text-xl font-bold">
                                {stats.last_scan_at ? new Date(stats.last_scan_at).toLocaleDateString() : '—'}
                            </p>
                            {stats.last_scan_at && (
                                <p className="mt-1 text-xs text-muted-foreground">
                                    {new Date(stats.last_scan_at).toLocaleTimeString()}
                                </p>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Multi-store table */}
                {stores.length > 0 ? (
                    <Card>
                        <CardHeader>
                            <CardTitle>Stores Overview</CardTitle>
                        </CardHeader>
                        <CardContent className="p-0">
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead className="border-b bg-muted/50 text-xs text-muted-foreground">
                                        <tr>
                                            <th className="px-4 py-3 text-left font-medium">Store</th>
                                            <th className="px-4 py-3 text-left font-medium">Quality Score</th>
                                            <th className="px-4 py-3 text-right font-medium">Issues</th>
                                            <th className="px-4 py-3 text-left font-medium">Last Scanned</th>
                                            <th className="px-4 py-3 text-right font-medium"></th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y">
                                        {stores.map((store) => (
                                            <tr key={store.id} className="hover:bg-muted/30">
                                                <td className="px-4 py-3">
                                                    <div className="font-medium">
                                                        {store.shop_name ?? store.shop_domain}
                                                    </div>
                                                    {store.shop_name && (
                                                        <div className="text-xs text-muted-foreground">
                                                            {store.shop_domain}
                                                        </div>
                                                    )}
                                                </td>
                                                <td className="px-4 py-3">
                                                    {store.latest_scan?.status === 'complete' ? (
                                                        <QualityScoreBadge score={store.latest_scan.quality_score} size="sm" />
                                                    ) : store.latest_scan ? (
                                                        <span className="text-xs text-muted-foreground capitalize">
                                                            {store.latest_scan.status}…
                                                        </span>
                                                    ) : (
                                                        <span className="text-xs text-muted-foreground">No scan yet</span>
                                                    )}
                                                </td>
                                                <td className="px-4 py-3 text-right font-medium">
                                                    {store.latest_scan?.status === 'complete'
                                                        ? store.latest_scan.total_issues
                                                        : '—'}
                                                </td>
                                                <td className="px-4 py-3 text-muted-foreground">
                                                    {store.latest_scan?.scanned_at
                                                        ? new Date(store.latest_scan.scanned_at).toLocaleString()
                                                        : '—'}
                                                </td>
                                                <td className="px-4 py-3 text-right">
                                                    {store.latest_scan ? (
                                                        <Link
                                                            href={scanShow({ shopifyStore: store.id, scan: store.latest_scan.id }).url}
                                                            className="text-xs font-medium underline underline-offset-2 hover:no-underline"
                                                        >
                                                            View →
                                                        </Link>
                                                    ) : (
                                                        <Link
                                                            href={scanIndex(store.id).url}
                                                            className="text-xs text-muted-foreground hover:underline"
                                                        >
                                                            Run scan →
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
                ) : (
                    <div className="flex flex-1 flex-col items-center justify-center gap-4 rounded-xl border-2 border-dashed border-sidebar-border/70 py-16 text-center">
                        <ShoppingBag className="size-10 text-muted-foreground/40" />
                        <div>
                            <p className="font-medium text-muted-foreground">No stores connected yet</p>
                            <p className="text-sm text-muted-foreground/70">
                                <Link href={storesIndex().url} className="underline">
                                    Connect a Shopify store
                                </Link>{' '}
                                to start scanning metafields.
                            </p>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
