import { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';

interface AccountingConfig {
    id: string;
    company_name: string;
    company_registration?: string;
    legal_form?: string;
    company_address?: string;
    fiscal_year_start: string;
    accounting_method: string;
    base_currency: string;
    multi_currency_enabled: boolean;
    enabled_currencies?: string[];
    tax_rate: number;
    tax_number?: string;
    tax_inclusive_pricing: boolean;
    use_departments: boolean;
    use_cost_centers: boolean;
    use_projects: boolean;
    invoice_numbering_pattern: string;
    receipt_numbering_pattern: string;
}

interface Props {
    accountingConfig: AccountingConfig;
    currencies: Record<string, string>;
    accountingMethods: Record<string, string>;
    legalForms: Record<string, string>;
}

export default function AccountingSettings({ 
    accountingConfig, 
    currencies, 
    accountingMethods, 
    legalForms 
}: Props) {
    const { data, setData, patch, processing, errors } = useForm({
        company_name: accountingConfig?.company_name || '',
        company_registration: accountingConfig?.company_registration || '',
        legal_form: accountingConfig?.legal_form || '',
        company_address: accountingConfig?.company_address || '',
        fiscal_year_start: accountingConfig?.fiscal_year_start || '01-01',
        accounting_method: accountingConfig?.accounting_method || 'accrual',
        base_currency: accountingConfig?.base_currency || 'HNL',
        multi_currency_enabled: accountingConfig?.multi_currency_enabled || false,
        enabled_currencies: accountingConfig?.enabled_currencies || [],
        tax_rate: accountingConfig?.tax_rate || 0.15,
        tax_number: accountingConfig?.tax_number || '',
        tax_inclusive_pricing: accountingConfig?.tax_inclusive_pricing || false,
        use_departments: accountingConfig?.use_departments || false,
        use_cost_centers: accountingConfig?.use_cost_centers || false,
        use_projects: accountingConfig?.use_projects || false,
        invoice_numbering_pattern: accountingConfig?.invoice_numbering_pattern || 'INV-{YYYY}-{MM}-{####}',
        receipt_numbering_pattern: accountingConfig?.receipt_numbering_pattern || 'REC-{YYYY}-{MM}-{####}',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        patch('/settings/accounting');
    };

    const handleCurrencyToggle = (currencyCode: string, enabled: boolean) => {
        const currentCurrencies = data.enabled_currencies || [];
        if (enabled) {
            setData('enabled_currencies', [...currentCurrencies, currencyCode]);
        } else {
            setData('enabled_currencies', currentCurrencies.filter(c => c !== currencyCode));
        }
    };

    return (
        <AppLayout>
            <Head title="Accounting Configuration" />
            
            <div className="space-y-6">
                <div>
                    <h2 className="text-3xl font-bold tracking-tight">Accounting Configuration</h2>
                    <p className="text-muted-foreground">
                        Manage your accounting methods, currencies, and financial settings.
                    </p>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Company Information */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Company Information</CardTitle>
                            <CardDescription>
                                Basic company details for accounting purposes
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
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

                                <div className="space-y-2">
                                    <Label htmlFor="company_registration">Registration Number</Label>
                                    <Input
                                        id="company_registration"
                                        value={data.company_registration}
                                        onChange={(e) => setData('company_registration', e.target.value)}
                                        placeholder="Company registration number"
                                    />
                                </div>
                            </div>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="tax_number">Tax Number</Label>
                                    <Input
                                        id="tax_number"
                                        value={data.tax_number}
                                        onChange={(e) => setData('tax_number', e.target.value)}
                                        placeholder="Tax identification number"
                                    />
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="company_address">Company Address</Label>
                                <Textarea
                                    id="company_address"
                                    value={data.company_address}
                                    onChange={(e) => setData('company_address', e.target.value)}
                                    placeholder="Complete company address"
                                    rows={3}
                                />
                            </div>
                        </CardContent>
                    </Card>

                    {/* Accounting Methods */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Accounting Methods</CardTitle>
                            <CardDescription>
                                Configure your accounting method and fiscal year
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="accounting_method">Accounting Method *</Label>
                                    <Select
                                        value={data.accounting_method}
                                        onValueChange={(value) => setData('accounting_method', value)}
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {Object.entries(accountingMethods).map(([value, label]) => (
                                                <SelectItem key={value} value={value}>
                                                    {label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="fiscal_year_start">Fiscal Year Start *</Label>
                                    <Input
                                        id="fiscal_year_start"
                                        value={data.fiscal_year_start}
                                        onChange={(e) => setData('fiscal_year_start', e.target.value)}
                                        placeholder="MM-DD"
                                        pattern="\d{2}-\d{2}"
                                        required
                                    />
                                    <p className="text-sm text-muted-foreground">
                                        Format: MM-DD (e.g., 01-01 for January 1st)
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Currency Settings */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Currency Settings</CardTitle>
                            <CardDescription>
                                Configure base currency and multi-currency support
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="base_currency">Base Currency *</Label>
                                    <Select
                                        value={data.base_currency}
                                        onValueChange={(value) => setData('base_currency', value)}
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {Object.entries(currencies).map(([code, name]) => (
                                                <SelectItem key={code} value={code}>
                                                    {name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="tax_rate">Default Tax Rate *</Label>
                                    <Input
                                        id="tax_rate"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        max="100"
                                        value={(data.tax_rate * 100).toFixed(2)}
                                        onChange={(e) => setData('tax_rate', parseFloat(e.target.value) / 100)}
                                    />
                                    <p className="text-sm text-muted-foreground">
                                        Enter as percentage (e.g., 15 for 15%)
                                    </p>
                                </div>
                            </div>

                            <div className="flex items-center space-x-2">
                                <Switch
                                    id="multi_currency_enabled"
                                    checked={data.multi_currency_enabled}
                                    onCheckedChange={(checked) => setData('multi_currency_enabled', checked)}
                                />
                                <Label htmlFor="multi_currency_enabled">Enable Multi-Currency Support</Label>
                            </div>

                            {data.multi_currency_enabled && (
                                <div className="space-y-2">
                                    <Label>Additional Currencies</Label>
                                    <div className="grid grid-cols-2 md:grid-cols-3 gap-2">
                                        {Object.entries(currencies).map(([code, name]) => (
                                            <div key={code} className="flex items-center space-x-2">
                                                <Switch
                                                    id={`currency_${code}`}
                                                    checked={data.enabled_currencies?.includes(code) || false}
                                                    onCheckedChange={(checked) => handleCurrencyToggle(code, checked)}
                                                    disabled={code === data.base_currency}
                                                />
                                                <Label htmlFor={`currency_${code}`} className="text-sm">
                                                    {code}
                                                </Label>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Tax Settings */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Tax Settings</CardTitle>
                            <CardDescription>
                                Configure tax calculation preferences
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex items-center space-x-2">
                                <Switch
                                    id="tax_inclusive_pricing"
                                    checked={data.tax_inclusive_pricing}
                                    onCheckedChange={(checked) => setData('tax_inclusive_pricing', checked)}
                                />
                                <Label htmlFor="tax_inclusive_pricing">Tax-Inclusive Pricing</Label>
                            </div>
                            <p className="text-sm text-muted-foreground">
                                When enabled, product prices include tax. When disabled, tax is added to the price.
                            </p>
                        </CardContent>
                    </Card>

                    {/* Features */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Additional Features</CardTitle>
                            <CardDescription>
                                Enable optional accounting features
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div className="flex items-center space-x-2">
                                    <Switch
                                        id="use_departments"
                                        checked={data.use_departments}
                                        onCheckedChange={(checked) => setData('use_departments', checked)}
                                    />
                                    <Label htmlFor="use_departments">Use Departments</Label>
                                </div>

                                <div className="flex items-center space-x-2">
                                    <Switch
                                        id="use_cost_centers"
                                        checked={data.use_cost_centers}
                                        onCheckedChange={(checked) => setData('use_cost_centers', checked)}
                                    />
                                    <Label htmlFor="use_cost_centers">Use Cost Centers</Label>
                                </div>

                                <div className="flex items-center space-x-2">
                                    <Switch
                                        id="use_projects"
                                        checked={data.use_projects}
                                        onCheckedChange={(checked) => setData('use_projects', checked)}
                                    />
                                    <Label htmlFor="use_projects">Use Projects</Label>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Numbering Patterns */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Document Numbering</CardTitle>
                            <CardDescription>
                                Configure automatic numbering patterns for documents
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="invoice_numbering_pattern">Invoice Numbering Pattern *</Label>
                                    <Input
                                        id="invoice_numbering_pattern"
                                        value={data.invoice_numbering_pattern}
                                        onChange={(e) => setData('invoice_numbering_pattern', e.target.value)}
                                        placeholder="INV-{YYYY}-{MM}-{####}"
                                        required
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="receipt_numbering_pattern">Receipt Numbering Pattern *</Label>
                                    <Input
                                        id="receipt_numbering_pattern"
                                        value={data.receipt_numbering_pattern}
                                        onChange={(e) => setData('receipt_numbering_pattern', e.target.value)}
                                        placeholder="REC-{YYYY}-{MM}-{####}"
                                        required
                                    />
                                </div>
                            </div>
                            <p className="text-sm text-muted-foreground">
                                Available placeholders: {'{YYYY}'} (year), {'{MM}'} (month), {'{DD}'} (day), {'{####}'} (sequential number)
                            </p>
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