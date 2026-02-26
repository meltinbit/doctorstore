import { Form, Head, Link, router } from '@inertiajs/react';
import { useEffect } from 'react';
import cronstrue from 'cronstrue';
import { Clock, ExternalLink, ScanLine, Settings, ShoppingBag } from 'lucide-react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, ShopifyStore } from '@/types';
import ShopifyOAuthController from '@/actions/App/Http/Controllers/Shopify/ShopifyOAuthController';
import { store as scanStore, index as scanIndex } from '@/actions/App/Http/Controllers/Shopify/ScanController';
import QualityScoreBadge from '@/components/quality-score-badge';
import { index } from '@/routes/stores';
import { show as settingsShow } from '@/routes/stores/settings';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Stores',
        href: index().url,
    },
];

export default function StoresIndex({
    stores,
    status,
    error,
}: {
    stores: ShopifyStore[];
    status?: string;
    error?: string;
}) {
    const hasActiveScans = stores.some(
        (s) => s.latest_scan?.status === 'pending' || s.latest_scan?.status === 'running',
    );

    useEffect(() => {
        // Fast poll when a scan is in progress, slow poll otherwise to catch auto-scans starting.
        const interval = setInterval(
            () => router.reload({ only: ['stores'] }),
            hasActiveScans ? 4000 : 20000,
        );
        return () => clearInterval(interval);
    }, [hasActiveScans]);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Stores" />

            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">Connected Stores</h1>
                        <p className="text-sm text-muted-foreground">
                            Manage your Shopify store connections.
                        </p>
                    </div>

                    <Dialog>
                        <DialogTrigger asChild>
                            <Button>Connect Store</Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogTitle>Connect a Shopify Store</DialogTitle>
                            <DialogDescription>
                                Enter your Shopify store domain to begin the OAuth connection flow.
                            </DialogDescription>

                            <Form {...ShopifyOAuthController.redirect.form()}>
                                {({ processing, errors }) => (
                                    <div className="space-y-4">
                                        <div className="grid gap-2">
                                            <Label htmlFor="shop">Store Name</Label>
                                            <div className="flex rounded-md border border-input shadow-sm focus-within:ring-1 focus-within:ring-ring">
                                                <input
                                                    id="shop"
                                                    name="shop"
                                                    autoComplete="off"
                                                    placeholder="your-store"
                                                    className="min-w-0 flex-1 rounded-l-md bg-background px-3 py-2 text-sm outline-none placeholder:text-muted-foreground"
                                                />
                                                <span className="flex items-center rounded-r-md border-l border-input bg-muted px-3 text-sm text-muted-foreground select-none">
                                                    .myshopify.com
                                                </span>
                                            </div>
                                            <InputError message={errors.shop} />
                                        </div>

                                        <DialogFooter>
                                            <DialogClose asChild>
                                                <Button variant="secondary" type="button">
                                                    Cancel
                                                </Button>
                                            </DialogClose>
                                            <Button disabled={processing} asChild>
                                                <button type="submit">Connect</button>
                                            </Button>
                                        </DialogFooter>
                                    </div>
                                )}
                            </Form>
                        </DialogContent>
                    </Dialog>
                </div>

                {status && (
                    <div className="rounded-md bg-green-50 px-4 py-3 text-sm text-green-700 dark:bg-green-900/20 dark:text-green-400">
                        {status}
                    </div>
                )}

                {error && (
                    <div className="rounded-md bg-red-50 px-4 py-3 text-sm text-red-700 dark:bg-red-900/20 dark:text-red-400">
                        {error}
                    </div>
                )}

                {stores.length === 0 ? (
                    <div className="flex flex-1 flex-col items-center justify-center gap-4 rounded-xl border-2 border-dashed border-sidebar-border/70 py-16">
                        <ShoppingBag className="size-12 text-muted-foreground/40" />
                        <div className="text-center">
                            <p className="font-medium text-muted-foreground">No stores connected</p>
                            <p className="text-sm text-muted-foreground/70">
                                Connect a Shopify store to get started.
                            </p>
                        </div>
                    </div>
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {stores.map((store) => (
                            <Card key={store.id} className="flex flex-col">
                                <CardHeader className="pb-3">
                                    <div className="flex items-start justify-between gap-2">
                                        <CardTitle className="text-base">
                                            {store.shop_name ?? store.shop_domain}
                                        </CardTitle>
                                        <Button variant="ghost" size="icon" className="-mr-2 -mt-1 size-7 shrink-0 text-muted-foreground" asChild>
                                            <Link href={settingsShow(store.id).url}>
                                                <Settings className="size-4" />
                                            </Link>
                                        </Button>
                                    </div>
                                    <CardDescription className="flex items-center gap-1">
                                        <a
                                            href={`https://${store.shop_domain}`}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="flex items-center gap-1 hover:underline"
                                        >
                                            {store.shop_domain}
                                            <ExternalLink className="size-3" />
                                        </a>
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    {/* Score + issue count */}
                                    <div className="flex items-center gap-2">
                                        {store.latest_scan?.status === 'complete' && (
                                            <>
                                                <QualityScoreBadge score={store.latest_scan.quality_score} size="sm" />
                                                <span className="text-sm font-medium">
                                                    {store.latest_scan.total_issues} issues
                                                </span>
                                            </>
                                        )}
                                        {store.latest_scan?.status === 'failed' && (
                                            <span className="text-sm text-destructive">Scan failed</span>
                                        )}
                                        {(store.latest_scan?.status === 'pending' || store.latest_scan?.status === 'running') && (
                                            <div className="flex items-center gap-2">
                                                <span className="relative flex size-2.5">
                                                    <span className="absolute inline-flex size-full animate-ping rounded-full bg-primary opacity-75" />
                                                    <span className="relative inline-flex size-2.5 rounded-full bg-primary" />
                                                </span>
                                                <span className="text-sm font-medium text-primary">
                                                    {store.latest_scan.status === 'pending' ? 'Queued…' : 'Scanning…'}
                                                </span>
                                            </div>
                                        )}
                                        {!store.latest_scan && (
                                            <span className="text-sm text-muted-foreground">No scans yet</span>
                                        )}
                                    </div>

                                    {/* Auto-scan */}
                                    <div className="flex items-center gap-1.5 pt-2 text-xs text-muted-foreground">
                                        <Clock className="size-3 shrink-0" />
                                        {store.auto_scan_enabled && store.auto_scan_schedule ? (
                                            <span>
                                                {store.auto_scan_schedule === 'daily'
                                                    ? 'Every day at 8:00 AM'
                                                    : store.auto_scan_schedule === 'weekly'
                                                      ? 'Every Monday at 8:00 AM'
                                                      : (() => {
                                                            try {
                                                                return cronstrue.toString(store.auto_scan_schedule, { verbose: false });
                                                            } catch {
                                                                return store.auto_scan_schedule;
                                                            }
                                                        })()}
                                            </span>
                                        ) : (
                                            <span className="italic">No auto-scan configured</span>
                                        )}
                                    </div>

                                </CardContent>

                                <CardFooter className="mt-auto flex items-center justify-between gap-2 border-t pt-4">
                                    <Link
                                        href={scanIndex(store.id).url}
                                        className="text-sm text-muted-foreground hover:text-foreground hover:underline"
                                    >
                                        View scan history →
                                    </Link>
                                    <Form {...scanStore.form(store.id)}>
                                        {({ processing }) => {
                                            const scanInProgress =
                                                store.latest_scan?.status === 'pending' ||
                                                store.latest_scan?.status === 'running';
                                            return (
                                                <Button
                                                    size="sm"
                                                    variant="secondary"
                                                    disabled={processing || scanInProgress}
                                                    asChild
                                                >
                                                    <button type="submit">
                                                        <ScanLine className="size-3.5" />
                                                        Run Scan
                                                    </button>
                                                </Button>
                                            );
                                        }}
                                    </Form>
                                </CardFooter>
                            </Card>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
