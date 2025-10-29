import { Button } from '@/components/ui/button';
import { Head, Link } from '@inertiajs/react';

interface Props {
    tenant: {
        id: string;
        name?: string;
    };
    canLogin?: boolean;
    canRegister?: boolean;
}

export default function TenantWelcome({ 
    tenant, 
    canLogin = true, 
    canRegister = true 
}: Props) {
    return (
        <>
            <Head title={`Welcome to ${tenant.name || 'HonduKash'}`}>
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link
                    href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600"
                    rel="stylesheet"
                />
            </Head>
            <div className="flex min-h-screen flex-col items-center bg-[#FDFDFC] p-6 text-[#1b1b18] lg:justify-center lg:p-8 dark:bg-[#0a0a0a]">
                <header className="mb-6 w-full max-w-[335px] text-sm not-has-[nav]:hidden lg:max-w-4xl">
                    <nav className="flex items-center justify-end gap-4">
                        {canLogin && (
                            <Link
                                href="/login"
                                className="inline-block rounded-sm border border-transparent px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#19140035] dark:text-[#EDEDEC] dark:hover:border-[#3E3E3A]"
                            >
                                Log in
                            </Link>
                        )}
                        {canRegister && (
                            <Link
                                href="/register"
                                className="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                            >
                                Register
                            </Link>
                        )}
                    </nav>
                </header>

                <main className="w-full max-w-[335px] lg:max-w-4xl">
                    <div className="text-center">
                        <div className="mb-8">
                            <h1 className="text-4xl font-bold tracking-tight lg:text-6xl">
                                Welcome to{' '}
                                <span className="text-blue-600 dark:text-blue-400">
                                    {tenant.name || 'HonduKash'}
                                </span>
                            </h1>
                            <p className="mt-4 text-lg text-gray-600 dark:text-gray-400">
                                Your business management platform is ready to use.
                            </p>
                        </div>

                        <div className="flex flex-col items-center gap-4 sm:flex-row sm:justify-center">
                            {canLogin && (
                                <Button asChild size="lg">
                                    <Link href="/login">
                                        Sign In to Your Account
                                    </Link>
                                </Button>
                            )}
                            {canRegister && (
                                <Button asChild variant="outline" size="lg">
                                    <Link href="/register">
                                        Create New Account
                                    </Link>
                                </Button>
                            )}
                        </div>

                        <div className="mt-12 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                            <div className="rounded-lg border border-gray-200 p-6 dark:border-gray-800">
                                <div className="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900">
                                    <svg className="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                </div>
                                <h3 className="text-lg font-semibold">Dashboard</h3>
                                <p className="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                    Monitor your business metrics and performance at a glance.
                                </p>
                            </div>

                            <div className="rounded-lg border border-gray-200 p-6 dark:border-gray-800">
                                <div className="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900">
                                    <svg className="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                                    </svg>
                                </div>
                                <h3 className="text-lg font-semibold">Financial Management</h3>
                                <p className="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                    Track expenses, revenue, and financial health of your business.
                                </p>
                            </div>

                            <div className="rounded-lg border border-gray-200 p-6 dark:border-gray-800">
                                <div className="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900">
                                    <svg className="h-6 w-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                </div>
                                <h3 className="text-lg font-semibold">Team Management</h3>
                                <p className="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                    Manage your team members, roles, and permissions effectively.
                                </p>
                            </div>
                        </div>

                        <div className="mt-12 text-center">
                            <p className="text-sm text-gray-500 dark:text-gray-400">
                                Tenant ID: <code className="rounded bg-gray-100 px-2 py-1 dark:bg-gray-800">{tenant.id}</code>
                            </p>
                        </div>
                    </div>
                </main>
            </div>
        </>
    );
}