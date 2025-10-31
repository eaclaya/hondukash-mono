import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

interface Props {
    tenant: {
        id: string;
        name?: string;
    };
    currencies: Record<string, string>;
    timezones: Record<string, string>;
    countries: Record<string, string>;
}

export default function StoreSetup({ tenant, currencies, timezones, countries }: Props) {
    const [currentStep, setCurrentStep] = useState(1);
    const [data, setData] = useState({
        name: '',
        code: '',
        type: 'store',
        email: '',
        phone: '',
        address: '',
        city: '',
        state: '',
        country: 'HN',
        postal_code: '',
        tax_rate: 0.15,
        currency: 'HNL',
        timezone: 'America/Tegucigalpa',
    });
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [processing, setProcessing] = useState(false);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setProcessing(true);

        router.post('/setup/store', data, {
            onFinish: () => setProcessing(false),
            onError: (errors) => {
                console.error('Store setup errors:', errors);
                setErrors(errors);
            },
            onSuccess: (page) => {
                console.log('Store setup success:', page);
            },
            onBefore: () => {
                console.log('Store setup starting...');
            },
        });
    };

    const steps = [
        { id: 1, name: 'Basic Info' },
        { id: 2, name: 'Contact & Location' },
        { id: 3, name: 'Settings' },
    ];

    const nextStep = () => {
        if (currentStep < 3) setCurrentStep(currentStep + 1);
    };

    const prevStep = () => {
        if (currentStep > 1) setCurrentStep(currentStep - 1);
    };

    return (
        <>
            <Head title="Store Setup" />
            
            <div className="min-h-screen bg-gray-50 dark:bg-gray-900 py-12">
                <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="text-center mb-8">
                        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
                            Welcome to {tenant.name || 'HonduKash'}
                        </h1>
                        <p className="mt-2 text-lg text-gray-600 dark:text-gray-400">
                            Let's set up your first store
                        </p>
                    </div>

                    {/* Simple Step Indicator */}
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
                                    {currentStep === 1 && 'Enter basic information about your store'}
                                    {currentStep === 2 && 'Add contact information and location details'}
                                    {currentStep === 3 && 'Configure tax, currency, and timezone settings'}
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-6">
                                {/* Step 1: Basic Info */}
                                {currentStep === 1 && (
                                    <>
                                        <div className="space-y-2">
                                            <Label htmlFor="name">Store Name *</Label>
                                            <Input
                                                id="name"
                                                value={data.name}
                                                onChange={(e) => setData({ ...data, name: e.target.value })}
                                                placeholder="Main Store"
                                                required
                                            />
                                            {errors.name && (
                                                <p className="text-sm text-red-600">{errors.name}</p>
                                            )}
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="code">Store Code *</Label>
                                            <Input
                                                id="code"
                                                value={data.code}
                                                onChange={(e) => setData({ ...data, code: e.target.value.toUpperCase() })}
                                                placeholder="MAIN"
                                                maxLength={10}
                                                required
                                            />
                                            <p className="text-sm text-gray-500 dark:text-gray-400">
                                                Short code to identify this store
                                            </p>
                                            {errors.code && (
                                                <p className="text-sm text-red-600">{errors.code}</p>
                                            )}
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="type">Store Type *</Label>
                                            <Select
                                                value={data.type}
                                                onValueChange={(value) => setData({ ...data, type: value })}
                                            >
                                                <SelectTrigger>
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="store">Retail Store</SelectItem>
                                                    <SelectItem value="warehouse">Warehouse</SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <p className="text-sm text-gray-500 dark:text-gray-400">
                                                Choose whether this is a customer-facing store or a warehouse
                                            </p>
                                        </div>
                                    </>
                                )}

                                {/* Step 2: Contact & Location */}
                                {currentStep === 2 && (
                                    <>
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div className="space-y-2">
                                                <Label htmlFor="email">Email</Label>
                                                <Input
                                                    id="email"
                                                    type="email"
                                                    value={data.email}
                                                    onChange={(e) => setData({ ...data, email: e.target.value })}
                                                    placeholder="store@example.com"
                                                />
                                            </div>

                                            <div className="space-y-2">
                                                <Label htmlFor="phone">Phone</Label>
                                                <Input
                                                    id="phone"
                                                    value={data.phone}
                                                    onChange={(e) => setData({ ...data, phone: e.target.value })}
                                                    placeholder="+504 2222-2222"
                                                />
                                            </div>
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="address">Address</Label>
                                            <Textarea
                                                id="address"
                                                value={data.address}
                                                onChange={(e) => setData({ ...data, address: e.target.value })}
                                                placeholder="Street address..."
                                                rows={2}
                                            />
                                        </div>

                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div className="space-y-2">
                                                <Label htmlFor="city">City</Label>
                                                <Input
                                                    id="city"
                                                    value={data.city}
                                                    onChange={(e) => setData({ ...data, city: e.target.value })}
                                                    placeholder="Tegucigalpa"
                                                />
                                            </div>

                                            <div className="space-y-2">
                                                <Label htmlFor="state">State/Department</Label>
                                                <Input
                                                    id="state"
                                                    value={data.state}
                                                    onChange={(e) => setData({ ...data, state: e.target.value })}
                                                    placeholder="Francisco Morazán"
                                                />
                                            </div>
                                        </div>

                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div className="space-y-2">
                                                <Label htmlFor="country">Country</Label>
                                                <Select
                                                    value={data.country}
                                                    onValueChange={(value) => setData({ ...data, country: value })}
                                                >
                                                    <SelectTrigger>
                                                        <SelectValue />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {Object.entries(countries).map(([code, name]) => (
                                                            <SelectItem key={code} value={code}>
                                                                {name}
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                            </div>

                                            <div className="space-y-2">
                                                <Label htmlFor="postal_code">Postal Code</Label>
                                                <Input
                                                    id="postal_code"
                                                    value={data.postal_code}
                                                    onChange={(e) => setData({ ...data, postal_code: e.target.value })}
                                                    placeholder="11101"
                                                />
                                            </div>
                                        </div>
                                    </>
                                )}

                                {/* Step 3: Settings */}
                                {currentStep === 3 && (
                                    <>
                                        <div className="space-y-2">
                                            <Label htmlFor="currency">Currency *</Label>
                                            <Select
                                                value={data.currency}
                                                onValueChange={(value) => setData({ ...data, currency: value })}
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
                                            <Label htmlFor="timezone">Timezone *</Label>
                                            <Select
                                                value={data.timezone}
                                                onValueChange={(value) => setData({ ...data, timezone: value })}
                                            >
                                                <SelectTrigger>
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {Object.entries(timezones).map(([tz, name]) => (
                                                        <SelectItem key={tz} value={tz}>
                                                            {name}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="tax_rate">Default Tax Rate (%)</Label>
                                            <Input
                                                id="tax_rate"
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                max="100"
                                                value={(data.tax_rate * 100).toFixed(2)}
                                                onChange={(e) => setData({ ...data, tax_rate: parseFloat(e.target.value) / 100 })}
                                            />
                                            <p className="text-sm text-gray-500 dark:text-gray-400">
                                                Default tax rate for this store (ISV)
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
                                    {processing ? 'Creating Store...' : 'Complete Setup'}
                                </Button>
                            )}
                        </div>
                    </form>
                </div>
            </div>
        </>
    );
}