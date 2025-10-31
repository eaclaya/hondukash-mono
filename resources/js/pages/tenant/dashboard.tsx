import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Store, Building2, Calculator, FileText, TrendingUp, Users } from 'lucide-react';

interface Props {
    user: {
        id: string;
        name: string;
        email: string;
        role: string;
    };
    tenant: {
        id: string;
        name?: string;
    };
}

export default function TenantDashboard({ user, tenant }: Props) {
    return (
        <AppLayout>
            <Head title="Dashboard" />
            
            <div className="space-y-6">
                <div>
                    <h2 className="text-3xl font-bold tracking-tight">Welcome back, {user.name}</h2>
                    <p className="text-muted-foreground">
                        Here's what's happening with your business today.
                    </p>
                </div>

                {/* Quick Stats */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Revenue</CardTitle>
                            <TrendingUp className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">L 0.00</div>
                            <p className="text-xs text-muted-foreground">
                                +0% from last month
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Pending Invoices</CardTitle>
                            <FileText className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">0</div>
                            <p className="text-xs text-muted-foreground">
                                0 overdue
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Active Customers</CardTitle>
                            <Users className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">0</div>
                            <p className="text-xs text-muted-foreground">
                                0 new this month
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Store Status</CardTitle>
                            <Store className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">Active</div>
                            <p className="text-xs text-muted-foreground">
                                1 store configured
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* Quick Actions */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <Card className="cursor-pointer hover:shadow-lg transition-shadow">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <FileText className="h-5 w-5" />
                                Create Invoice
                            </CardTitle>
                            <CardDescription>
                                Generate a new invoice for your customers
                            </CardDescription>
                        </CardHeader>
                    </Card>

                    <Card className="cursor-pointer hover:shadow-lg transition-shadow">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Users className="h-5 w-5" />
                                Add Customer
                            </CardTitle>
                            <CardDescription>
                                Register a new customer in your system
                            </CardDescription>
                        </CardHeader>
                    </Card>

                    <Card className="cursor-pointer hover:shadow-lg transition-shadow">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Calculator className="h-5 w-5" />
                                Accounting Reports
                            </CardTitle>
                            <CardDescription>
                                View financial reports and statements
                            </CardDescription>
                        </CardHeader>
                    </Card>
                </div>

                {/* Recent Activity */}
                <Card>
                    <CardHeader>
                        <CardTitle>Recent Activity</CardTitle>
                        <CardDescription>
                            Your latest business transactions and activities
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="text-center py-8 text-muted-foreground">
                            <Building2 className="h-12 w-12 mx-auto mb-4 opacity-50" />
                            <p>No activity yet</p>
                            <p className="text-sm">Start by creating your first invoice or adding a customer</p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}