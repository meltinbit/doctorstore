import { Transition } from '@headlessui/react';
import { Form, Head } from '@inertiajs/react';
import { useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import StoreSettingsController from '@/actions/App/Http/Controllers/Shopify/StoreSettingsController';
import ShopifyStoreController from '@/actions/App/Http/Controllers/Shopify/ShopifyStoreController';
import { index as storesIndex } from '@/routes/stores';

type StoreSettingsProps = {
    store: {
        id: number;
        shop_domain: string;
        shop_name: string | null;
        auto_scan_enabled: boolean;
        auto_scan_schedule: string | null;
        email_summary_enabled: boolean;
        email_summary_address: string | null;
    };
    status?: string;
};

const SCHEDULES = [
    { value: 'daily', label: 'Daily', description: 'Every day at 8:00 AM.' },
    { value: 'weekly', label: 'Weekly', description: 'Every Monday at 8:00 AM.' },
    { value: 'custom', label: 'Custom cron', description: 'Specify a custom cron expression.' },
] as const;

function resolveScheduleType(schedule: string | null): 'daily' | 'weekly' | 'custom' {
    if (schedule === 'daily') return 'daily';
    if (schedule === 'weekly') return 'weekly';
    if (schedule) return 'custom';
    return 'daily';
}

export default function StoreSettings({ store, status }: StoreSettingsProps) {
    const storeName = store.shop_name ?? store.shop_domain;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Stores', href: storesIndex().url },
        { title: storeName, href: storesIndex().url },
        { title: 'Settings', href: '#' },
    ];

    const [autoScanEnabled, setAutoScanEnabled] = useState(store.auto_scan_enabled);
    const [scheduleType, setScheduleType] = useState<'daily' | 'weekly' | 'custom'>(
        resolveScheduleType(store.auto_scan_schedule),
    );
    const [customCron, setCustomCron] = useState(
        store.auto_scan_schedule && !['daily', 'weekly'].includes(store.auto_scan_schedule)
            ? store.auto_scan_schedule
            : '',
    );
    const [emailEnabled, setEmailEnabled] = useState(store.email_summary_enabled);

    const autoScanScheduleValue =
        scheduleType === 'custom' ? customCron : scheduleType;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Settings — ${storeName}`} />

            <div className="px-4 py-6">
                <Heading title="Store Settings" description={storeName} />

                <Form {...StoreSettingsController.update.form(store.id)}>
                    {({ processing, errors }) => (
                        <div className="max-w-xl space-y-8">
                            {/* Auto-scan */}
                            <div className="space-y-4">
                                <Heading
                                    variant="small"
                                    title="Automated Scanning"
                                    description="Automatically run scans on a recurring schedule."
                                />

                                <label className="flex cursor-pointer items-center gap-3">
                                    <input
                                        type="checkbox"
                                        name="auto_scan_enabled"
                                        value="1"
                                        checked={autoScanEnabled}
                                        onChange={(e) => setAutoScanEnabled(e.target.checked)}
                                        className="rounded border-input"
                                    />
                                    <span className="text-sm font-medium">Enable auto-scan</span>
                                </label>

                                {autoScanEnabled && (
                                    <div className="space-y-3 pl-6">
                                        <Label>Schedule</Label>
                                        <div className="space-y-2">
                                            {SCHEDULES.map((sched) => (
                                                <label
                                                    key={sched.value}
                                                    className="flex cursor-pointer items-start gap-3 rounded-lg border p-4 hover:bg-muted/50 has-[:checked]:border-primary has-[:checked]:bg-primary/5"
                                                >
                                                    <input
                                                        type="radio"
                                                        name="_schedule_type"
                                                        value={sched.value}
                                                        checked={scheduleType === sched.value}
                                                        onChange={() =>
                                                            setScheduleType(
                                                                sched.value as 'daily' | 'weekly' | 'custom',
                                                            )
                                                        }
                                                        className="mt-0.5"
                                                    />
                                                    <div>
                                                        <p className="font-medium">{sched.label}</p>
                                                        <p className="text-sm text-muted-foreground">
                                                            {sched.description}
                                                        </p>
                                                    </div>
                                                </label>
                                            ))}
                                        </div>

                                        {scheduleType === 'custom' && (
                                            <div className="grid gap-1.5 pt-1">
                                                <Label htmlFor="custom_cron">Cron expression</Label>
                                                <Input
                                                    id="custom_cron"
                                                    placeholder="0 8 * * *"
                                                    value={customCron}
                                                    onChange={(e) => setCustomCron(e.target.value)}
                                                />
                                                <p className="text-xs text-muted-foreground">
                                                    Standard 5-part cron expression:{' '}
                                                    <code className="rounded bg-muted px-1 py-0.5 font-mono">
                                                        minute hour day month weekday
                                                    </code>
                                                    <br />
                                                    e.g.{' '}
                                                    <code className="rounded bg-muted px-1 py-0.5 font-mono">
                                                        0 9 * * 1-5
                                                    </code>{' '}
                                                    — every weekday at 9:00 AM
                                                </p>
                                                <InputError message={errors.auto_scan_schedule} />
                                            </div>
                                        )}

                                        <input
                                            type="hidden"
                                            name="auto_scan_schedule"
                                            value={autoScanScheduleValue}
                                        />
                                    </div>
                                )}
                            </div>

                            <Separator />

                            {/* Email summary */}
                            <div className="space-y-4">
                                <Heading
                                    variant="small"
                                    title="Email Summary"
                                    description="Receive an email after each scan completes."
                                />

                                <label className="flex cursor-pointer items-center gap-3">
                                    <input
                                        type="checkbox"
                                        name="email_summary_enabled"
                                        value="1"
                                        checked={emailEnabled}
                                        onChange={(e) => setEmailEnabled(e.target.checked)}
                                        className="rounded border-input"
                                    />
                                    <span className="text-sm font-medium">Enable email summary</span>
                                </label>

                                {emailEnabled && (
                                    <div className="grid gap-1.5 pl-6">
                                        <Label htmlFor="email_summary_address">Email address</Label>
                                        <Input
                                            id="email_summary_address"
                                            name="email_summary_address"
                                            type="email"
                                            defaultValue={store.email_summary_address ?? ''}
                                            placeholder="you@example.com"
                                        />
                                        <InputError message={errors.email_summary_address} />
                                    </div>
                                )}
                            </div>

                            <Separator />

                            <div className="flex items-center gap-4">
                                <Button disabled={processing} asChild>
                                    <button type="submit">Save</button>
                                </Button>

                                <Transition
                                    show={status === 'settings-saved'}
                                    enter="transition ease-in-out"
                                    enterFrom="opacity-0"
                                    leave="transition ease-in-out"
                                    leaveTo="opacity-0"
                                >
                                    <p className="text-sm text-muted-foreground">Saved.</p>
                                </Transition>
                            </div>
                        </div>
                    )}
                </Form>

                <Separator className="my-8 max-w-xl" />

                {/* Danger zone */}
                <div className="max-w-xl space-y-4">
                    <Heading
                        variant="small"
                        title="Danger Zone"
                        description="Permanently disconnect this store from your account."
                    />
                    <Dialog>
                        <DialogTrigger asChild>
                            <Button variant="destructive">Disconnect Store</Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogTitle>Disconnect store?</DialogTitle>
                            <DialogDescription asChild>
                                <div className="space-y-2 text-sm text-muted-foreground">
                                    <p>
                                        You are about to disconnect <strong className="text-foreground">{store.shop_domain}</strong>.
                                    </p>
                                    <p>
                                        This will permanently delete <strong className="text-foreground">all associated data</strong>, including every scan and its issues. This action cannot be undone.
                                    </p>
                                </div>
                            </DialogDescription>
                            <Form {...ShopifyStoreController.destroy.form(store.id)}>
                                {({ processing }) => (
                                    <DialogFooter className="gap-2">
                                        <DialogClose asChild>
                                            <Button variant="secondary" type="button">Cancel</Button>
                                        </DialogClose>
                                        <Button variant="destructive" disabled={processing} asChild>
                                            <button type="submit">Disconnect</button>
                                        </Button>
                                    </DialogFooter>
                                )}
                            </Form>
                        </DialogContent>
                    </Dialog>
                </div>
            </div>
        </AppLayout>
    );
}
