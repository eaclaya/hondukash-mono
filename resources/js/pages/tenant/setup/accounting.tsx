import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Switch } from '@/components/ui/switch';
import { InfoIcon } from 'lucide-react';
import { Alert, AlertDescription } from '@/components/ui/alert';

interface Props {
    tenant: {
        id: string;
        name?: string;
    };
    defaultChartOfAccounts: any;
    currencies: Record<string, string>;
    accountingMethods: Record<string, string>;
    legalForms: Record<string, string>;
}

export default function AccountingSetup({ tenant, currencies, accountingMethods, legalForms }: Props) {
    const [currentStep, setCurrentStep] = useState(1);
    const [data, setData] = useState({
        company_name: tenant.name || '',
        company_registration: '',
        legal_form: '',
        company_address: '',
        fiscal_year_start: '01-01',
        accounting_method: 'accrual',
        base_currency: 'HNL',
        multi_currency_enabled: false,
        enabled_currencies: ['HNL'],
        tax_rate: 0.15,
        tax_number: '',
        tax_inclusive_pricing: true,
        use_departments: false,
        use_cost_centers: false,
        use_projects: false,
        invoice_numbering_pattern: 'INV-{YYYY}-{####}',
        receipt_numbering_pattern: 'REC-{YYYY}-{####}',
    });
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [processing, setProcessing] = useState(false);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setProcessing(true);

        router.post('/setup/accounting', data, {
            onFinish: () => setProcessing(false),
            onError: (errors) => setErrors(errors),
        });
    };

    const steps = [
        { id: 1, name: 'Company Info' },
        { id: 2, name: 'Accounting Settings' },
        { id: 3, name: 'Document Settings' },
    ];

    const nextStep = () => {
        if (currentStep < 3) setCurrentStep(currentStep + 1);
    };

    const prevStep = () => {
        if (currentStep > 1) setCurrentStep(currentStep - 1);
    };

    return (
        <>
            <Head title="Accounting Setup" />
            
            <div className="min-h-screen bg-gray-50 dark:bg-gray-900 py-12">
                <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="text-center mb-8">
                        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
                            Accounting Configuration
                        </h1>
                        <p className="mt-2 text-lg text-gray-600 dark:text-gray-400">
                            Configure your accounting system
                        </p>
                    </div>

                    {/* Step Indicator */}
                    <div className="flex items-center justify-center mb-8">
                        {steps.map((step, index) => (
                            <div key={step.id} className="flex items-center">
                                <div
                                    className={`w-10 h-10 rounded-full flex items-center justify-center ${
                                        step.id <= currentStep
                                            ? 'bg-blue-600 text-white'
                                            : 'bg-gray-300 dark:bg-gray-700 text-gray-600 dark:text-gray-400'
                                    }`}
                                >
                                    {step.id}
                                </div>
                                <div
                                    className={`ml-2 mr-4 text-sm ${
                                        step.id <= currentStep
                                            ? 'text-blue-600 dark:text-blue-400 font-medium'
                                            : 'text-gray-500 dark:text-gray-400'
                                    }`}
                                >
                                    {step.name}
                                </div>
                                {index < steps.length - 1 && (
                                    <div
                                        className={`w-20 h-1 mr-4 ${
                                            step.id < currentStep
                                                ? 'bg-blue-600'
                                                : 'bg-gray-300 dark:bg-gray-700'
                                        }`}
                                    />
                                )}
                            </div>
                        ))}
                    </div>

                    <form onSubmit={handleSubmit}>
                        <Card className="shadow-xl">
                            <CardHeader>
                                <CardTitle>{steps[currentStep - 1].name}</CardTitle>
                                <CardDescription>
                                    {currentStep === 1 && 'Enter your company information'}
                                    {currentStep === 2 && 'Configure accounting preferences'}
                                    {currentStep === 3 && 'Set up document numbering and options'}
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-6">
                                {/* Step 1: Company Info */}
                                {currentStep === 1 && (
                                    <>
                                        <div className="space-y-2">
                                            <Label htmlFor="company_name">Company Name *</Label>
                                            <Input
                                                id="company_name"
                                                value={data.company_name}
                                                onChange={(e) => setData({ ...data, company_name: e.target.value })}
                                                placeholder="Your Company Name"
                                                required
                                            />
                                            {errors.company_name && (
                                                <p className="text-sm text-red-600">{errors.company_name}</p>
                                            )}
                                        </div>

                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div className="space-y-2">
                                                <Label htmlFor="company_registration">Registration Number (RTN)</Label>
                                                <Input
                                                    id="company_registration"
                                                    value={data.company_registration}
                                                    onChange={(e) => setData({ ...data, company_registration: e.target.value })}
                                                    placeholder="0801199012345"
                                                />
                                            </div>

                                            <div className="space-y-2">
                                                <Label htmlFor="legal_form">Legal Form</Label>
                                                <Select
                                                    value={data.legal_form}
                                                    onValueChange={(value) => setData({ ...data, legal_form: value })}
                                                >
                                                    <SelectTrigger>
                                                        <SelectValue placeholder="Select legal form" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {Object.entries(legalForms).map(([code, name]) => (
                                                            <SelectItem key={code} value={code}>
                                                                {name}
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="company_address">Company Address</Label>
                                            <Textarea
                                                id="company_address"
                                                value={data.company_address}
                                                onChange={(e) => setData({ ...data, company_address: e.target.value })}
                                                placeholder="Full company address..."
                                                rows={3}
                                            />
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="tax_number">Tax Number (CAI)</Label>
                                            <Input
                                                id="tax_number"
                                                value={data.tax_number}
                                                onChange={(e) => setData({ ...data, tax_number: e.target.value })}
                                                placeholder="CAI or tax authorization number"
                                            />
                                        </div>
                                    </>
                                )}

                                {/* Step 2: Accounting Settings */}
                                {currentStep === 2 && (
                                    <>
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div className="space-y-2">
                                                <Label htmlFor="fiscal_year_start">Fiscal Year Start *</Label>
                                                <Input
                                                    id="fiscal_year_start"
                                                    value={data.fiscal_year_start}
                                                    onChange={(e) => setData({ ...data, fiscal_year_start: e.target.value })}
                                                    placeholder="MM-DD"
                                                    pattern="\d{2}-\d{2}"
                                                    title="Format: MM-DD (e.g., 01-01)"
                                                    required
                                                />
                                                <p className="text-sm text-gray-500 dark:text-gray-400">
                                                    Format: MM-DD (e.g., 01-01 for January 1st)
                                                </p>
                                            </div>

                                            <div className="space-y-2">
                                                <Label htmlFor="accounting_method">Accounting Method *</Label>
                                                <Select
                                                    value={data.accounting_method}
                                                    onValueChange={(value) => setData({ ...data, accounting_method: value })}
                                                >
                                                    <SelectTrigger>
                                                        <SelectValue />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {Object.entries(accountingMethods).map(([code, name]) => (
                                                            <SelectItem key={code} value={code}>
                                                                {name}
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="base_currency">Base Currency *</Label>
                                            <Select
                                                value={data.base_currency}
                                                onValueChange={(value) => setData({ ...data, base_currency: value })}
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

                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div className="space-y-2">
                                                <Label htmlFor="tax_rate">Default Tax Rate (ISV) *</Label>
                                                <Input
                                                    id="tax_rate"
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    max="100"
                                                    value={(data.tax_rate * 100).toFixed(2)}
                                                    onChange={(e) => setData({ ...data, tax_rate: parseFloat(e.target.value) / 100 })}
                                                    required
                                                />
                                                <p className="text-sm text-gray-500 dark:text-gray-400">
                                                    Default ISV rate (15% standard in Honduras)
                                                </p>
                                            </div>

                                            <div className="flex items-center space-x-3 pt-8">
                                                <Switch
                                                    id="tax_inclusive_pricing"
                                                    checked={data.tax_inclusive_pricing}
                                                    onCheckedChange={(checked) => setData({ ...data, tax_inclusive_pricing: checked })}
                                                />
                                                <Label htmlFor="tax_inclusive_pricing">
                                                    Prices include tax
                                                </Label>
                                            </div>
                                        </div>

                                        <Alert>
                                            <InfoIcon className="h-4 w-4" />
                                            <AlertDescription>
                                                Advanced features like departments, cost centers, and projects can be enabled later in settings.
                                            </AlertDescription>
                                        </Alert>
                                    </>
                                )}

                                {/* Step 3: Document Settings */}
                                {currentStep === 3 && (
                                    <>
                                        <div className="space-y-2">
                                            <Label htmlFor="invoice_numbering_pattern">Invoice Numbering Pattern *</Label>
                                            <Input
                                                id="invoice_numbering_pattern"
                                                value={data.invoice_numbering_pattern}
                                                onChange={(e) => setData({ ...data, invoice_numbering_pattern: e.target.value })}
                                                placeholder="INV-{YYYY}-{####}"
                                                required
                                            />
                                            <p className="text-sm text-gray-500 dark:text-gray-400">
                                                Available placeholders: {'{YYYY}'} = Year, {'{YY}'} = Short year, {'{MM}'} = Month, {'{####}'} = Sequential number
                                            </p>
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="receipt_numbering_pattern">Receipt Numbering Pattern *</Label>
                                            <Input
                                                id="receipt_numbering_pattern"
                                                value={data.receipt_numbering_pattern}
                                                onChange={(e) => setData({ ...data, receipt_numbering_pattern: e.target.value })}
                                                placeholder="REC-{YYYY}-{####}"
                                                required
                                            />
                                            <p className="text-sm text-gray-500 dark:text-gray-400">
                                                Same placeholders as invoice pattern
                                            </p>
                                        </div>

                                        <Alert className="bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800">
                                            <InfoIcon className="h-4 w-4 text-blue-600 dark:text-blue-400" />
                                            <AlertDescription className="text-blue-800 dark:text-blue-300">
                                                A default chart of accounts will be created based on Honduran accounting standards. 
                                                You can customize it later in the accounting settings.
                                            </AlertDescription>
                                        </Alert>

                                        <div className="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                                            <h4 className="font-semibold text-sm mb-2">Preview:</h4>
                                            <p className="text-sm text-gray-600 dark:text-gray-400">
                                                Next invoice: {data.invoice_numbering_pattern.replace('{YYYY}', new Date().getFullYear().toString()).replace('{####}', '0001')}
                                            </p>
                                            <p className="text-sm text-gray-600 dark:text-gray-400">
                                                Next receipt: {data.receipt_numbering_pattern.replace('{YYYY}', new Date().getFullYear().toString()).replace('{####}', '0001')}
                                            </p>
                                        </div>
                                    </>
                                )}
                            </CardContent>
                        </Card>

                        {/* Navigation Buttons */}
                        <div className="mt-6 flex justify-between">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={prevStep}
                                disabled={currentStep === 1}
                            >
                                Previous
                            </Button>

                            {currentStep < 3 ? (
                                <Button type="button" onClick={nextStep}>
                                    Next
                                </Button>
                            ) : (
                                <Button type="submit" disabled={processing}>
                                    {processing ? 'Completing Setup...' : 'Complete Setup'}
                                </Button>
                            )}
                        </div>
                    </form>
                </div>
            </div>
        </>
    );
}