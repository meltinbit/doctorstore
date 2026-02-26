import { Transition } from '@headlessui/react';
import { Form, Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import type { BreadcrumbItem } from '@/types';
import AlertSettingsController from '@/actions/App/Http/Controllers/Settings/AlertSettingsController';
import { edit } from '@/routes/alerts';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Alert settings', href: edit().url },
];

const FREQUENCIES = [
    { value: 'none', label: 'Disabled', description: 'No email alerts.' },
    { value: 'daily', label: 'Daily', description: 'Every morning at 8:00 AM.' },
    { value: 'weekly', label: 'Weekly', description: 'Every Monday at 8:00 AM.' },
] as const;

export default function AlertSettings({
    alert_frequency,
    status,
}: {
    alert_frequency: string;
    status?: string;
}) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Alert settings" />

            <SettingsLayout>
                <div className="space-y-6">
                    <Heading
                        title="Email Alerts"
                        description="Receive a metafield health summary for all your connected stores."
                    />

                    <Form {...AlertSettingsController.update.form()}>
                        {({ processing }) => (
                            <div className="space-y-6">
                                <div className="space-y-3">
                                    <Label>Frequency</Label>

                                    <div className="space-y-2">
                                        {FREQUENCIES.map((freq) => (
                                            <label
                                                key={freq.value}
                                                className="flex cursor-pointer items-start gap-3 rounded-lg border p-4 hover:bg-muted/50 has-[:checked]:border-primary has-[:checked]:bg-primary/5"
                                            >
                                                <input
                                                    type="radio"
                                                    name="alert_frequency"
                                                    value={freq.value}
                                                    defaultChecked={alert_frequency === freq.value}
                                                    className="mt-0.5"
                                                />
                                                <div>
                                                    <p className="font-medium">{freq.label}</p>
                                                    <p className="text-sm text-muted-foreground">
                                                        {freq.description}
                                                    </p>
                                                </div>
                                            </label>
                                        ))}
                                    </div>
                                </div>

                                <div className="flex items-center gap-4">
                                    <Button disabled={processing} asChild>
                                        <button type="submit">Save</button>
                                    </Button>

                                    <Transition
                                        show={status === 'alert-settings-updated'}
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
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
