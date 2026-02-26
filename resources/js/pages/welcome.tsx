import { Head, Link, usePage } from '@inertiajs/react';
import { dashboard, login } from '@/routes';

export default function Welcome({
    canRegister = true,
}: {
    canRegister?: boolean;
}) {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Welcome" />
            <div className="flex min-h-screen flex-col items-center justify-center bg-background px-6 text-foreground">
                <div className="w-full max-w-md text-center">
                    <h1 className="mb-3 text-3xl font-semibold tracking-tight">
                        Doctor Store
                    </h1>
                    <p className="mb-8 text-muted-foreground">
                        Self-hosted Shopify metafield inspector. Audit, explore,
                        and manage your store's metafields with ease.
                    </p>

                    <div className="flex items-center justify-center gap-3">
                        {auth.user ? (
                            <Link
                                href={dashboard()}
                                className="inline-flex items-center rounded-md bg-primary px-5 py-2 text-sm font-medium text-primary-foreground shadow-sm hover:bg-primary/90"
                            >
                                Go to Dashboard
                            </Link>
                        ) : (
                            <>
                                <Link
                                    href={login()}
                                    className="inline-flex items-center rounded-md border border-input bg-background px-5 py-2 text-sm font-medium shadow-sm hover:bg-accent"
                                >
                                    Log in
                                </Link>
                                {canRegister && (
                                    <Link
                                        href="/register"
                                        className="inline-flex items-center rounded-md bg-primary px-5 py-2 text-sm font-medium text-primary-foreground shadow-sm hover:bg-primary/90"
                                    >
                                        Register
                                    </Link>
                                )}
                            </>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}
