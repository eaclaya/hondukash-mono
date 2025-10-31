import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

interface Company {
    name: string;
    registration?: string;
    legal_form?: string;
    address?: string;
    tax_number?: string;
}

interface Tenant {
    id: string;
    name?: string;
}

interface Props {
    company: Company;
    tenant: Tenant;
    legalForms: Record<string, string>;
}

export default function CompanySettings({ company, tenant, legalForms }: Props) {
    const { data, setData, patch, processing, errors } = useForm({
        company_name: company?.name || '',
        company_registration: company?.registration || '',
        legal_form: company?.legal_form || '',
        company_address: company?.address || '',
        tax_number: company?.tax_number || '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        patch('/settings/company');
    };

    return (
        <AppLayout>
            <Head title="Company Settings" />
            
            <div className="space-y-6">
                <div>
                    <h2 className="text-3xl font-bold tracking-tight">Company Settings</h2>
                    <p className="text-muted-foreground">
                        Manage your company information and legal details.
                    </p>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Basic Company Information */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Basic Information</CardTitle>
                            <CardDescription>
                                Essential company details used throughout the system
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="company_name">Company Name *</Label>
                                <Input
                                    id="company_name"
                                    value={data.company_name}
                                    onChange={(e) => setData('company_name', e.target.value)}
                                    placeholder="Your Company Name"
                                    required
                                />
                                {errors.company_name && (
                                    <p className="text-sm text-red-600">{errors.company_name}</p>
                                )}
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="company_registration">Registration Number</Label>
                                    <Input
                                        id="company_registration"
                                        value={data.company_registration}
                                        onChange={(e) => setData('company_registration', e.target.value)}
                                        placeholder="Company registration number"
                                    />
                                    {errors.company_registration && (
                                        <p className="text-sm text-red-600">{errors.company_registration}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="legal_form">Legal Form</Label>
                                    <Select
                                        value={data.legal_form}
                                        onValueChange={(value) => setData('legal_form', value)}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select legal form" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {Object.entries(legalForms).map(([value, label]) => (
                                                <SelectItem key={value} value={value}>
                                                    {label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {errors.legal_form && (
                                        <p className="text-sm text-red-600">{errors.legal_form}</p>
                                    )}
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="company_address">Company Address</Label>
                                <Textarea
                                    id="company_address"
                                    value={data.company_address}
                                    onChange={(e) => setData('company_address', e.target.value)}
                                    placeholder="Complete company address including street, city, state, and postal code"
                                    rows={4}
                                />
                                {errors.company_address && (
                                    <p className="text-sm text-red-600">{errors.company_address}</p>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Tax Information */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Tax Information</CardTitle>
                            <CardDescription>
                                Tax identification and compliance details
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="tax_number">Tax Identification Number</Label>
                                <Input
                                    id="tax_number"
                                    value={data.tax_number}
                                    onChange={(e) => setData('tax_number', e.target.value)}
                                    placeholder="RTN or Tax ID number"
                                />
                                {errors.tax_number && (
                                    <p className="text-sm text-red-600">{errors.tax_number}</p>
                                )}
                                <p className="text-sm text-muted-foreground">
                                    Enter your RTN (Registro Tributario Nacional) or other tax identification number
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Tenant Information */}
                    <Card>
                        <CardHeader>
                            <CardTitle>System Information</CardTitle>
                            <CardDescription>
                                Read-only information about your system configuration
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label>Tenant ID</Label>
                                    <Input
                                        value={tenant.id}
                                        disabled
                                        className="bg-muted"
                                    />
                                    <p className="text-sm text-muted-foreground">
                                        Your unique system identifier
                                    </p>
                                </div>

                                <div className="space-y-2">
                                    <Label>Domain Name</Label>
                                    <Input
                                        value={tenant.name || 'Not set'}
                                        disabled
                                        className="bg-muted"
                                    />
                                    <p className="text-sm text-muted-foreground">
                                        Your system domain configuration
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <div className="flex justify-end">
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Saving...' : 'Save Changes'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}