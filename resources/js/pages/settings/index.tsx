import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { 
    Calculator, 
    Building2, 
    Store, 
    PieChart, 
    User, 
    Shield, 
    Palette,
    CreditCard
} from 'lucide-react';

export default function SettingsIndex() {
    const settingsItems = [
        {
            title: 'Accounting Configuration',
            description: 'Manage accounting methods, currencies, and financial settings',
            href: '/settings/accounting',
            icon: Calculator,
            category: 'Financial',
        },
        {
            title: 'Chart of Accounts',
            description: 'Configure your chart of accounts and account structure',
            href: '/settings/chart-of-accounts',
            icon: PieChart,
            category: 'Financial',
        },
        {
            title: 'Company Information',
            description: 'Update company details, registration, and legal information',
            href: '/settings/company',
            icon: Building2,
            category: 'Business',
        },
        {
            title: 'Store Management',
            description: 'Manage your stores, warehouses, and locations',
            href: '/settings/stores',
            icon: Store,
            category: 'Business',
        },
        {
            title: 'Profile Settings',
            description: 'Update your personal account information',
            href: '/settings/profile',
            icon: User,
            category: 'Account',
        },
        {
            title: 'Password & Security',
            description: 'Change password and security settings',
            href: '/settings/password',
            icon: Shield,
            category: 'Account',
        },
        {
            title: 'Two-Factor Authentication',
            description: 'Enable additional security for your account',
            href: '/settings/two-factor',
            icon: CreditCard,
            category: 'Account',
        },
        {
            title: 'Appearance',
            description: 'Customize the look and feel of your application',
            href: '/settings/appearance',
            icon: Palette,
            category: 'Preferences',
        },
    ];

    const categories = [
        { name: 'Financial', items: settingsItems.filter(item => item.category === 'Financial') },
        { name: 'Business', items: settingsItems.filter(item => item.category === 'Business') },
        { name: 'Account', items: settingsItems.filter(item => item.category === 'Account') },
        { name: 'Preferences', items: settingsItems.filter(item => item.category === 'Preferences') },
    ];

    return (
        <AppLayout>
            <Head title="Settings" />
            
            <div className="space-y-6">
                <div>
                    <h2 className="text-3xl font-bold tracking-tight">Settings</h2>
                    <p className="text-muted-foreground">
                        Manage your account, business, and application preferences.
                    </p>
                </div>

                <div className="space-y-8">
                    {categories.map((category) => (
                        <div key={category.name}>
                            <h3 className="text-lg font-semibold mb-4 text-foreground/80">
                                {category.name}
                            </h3>
                            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                                {category.items.map((item) => {
                                    const IconComponent = item.icon;
                                    return (
                                        <Link
                                            key={item.title}
                                            href={item.href}
                                            className="group block"
                                        >
                                            <Card className="h-full transition-all duration-200 hover:shadow-md group-hover:border-primary/20">
                                                <CardHeader className="pb-3">
                                                    <div className="flex items-center space-x-3">
                                                        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 group-hover:bg-primary/20 transition-colors">
                                                            <IconComponent className="h-5 w-5 text-primary" />
                                                        </div>
                                                        <div>
                                                            <CardTitle className="text-base group-hover:text-primary transition-colors">
                                                                {item.title}
                                                            </CardTitle>
                                                        </div>
                                                    </div>
                                                </CardHeader>
                                                <CardContent className="pt-0">
                                                    <CardDescription className="text-sm">
                                                        {item.description}
                                                    </CardDescription>
                                                </CardContent>
                                            </Card>
                                        </Link>
                                    );
                                })}
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </AppLayout>
    );
}