import { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Plus, Edit, Trash2, Store as StoreIcon, Warehouse } from 'lucide-react';

interface Store {
    id: string;
    name: string;
    code: string;
    type: 'store' | 'warehouse';
    email?: string;
    phone?: string;
    address?: {
        street?: string;
        city?: string;
        state?: string;
        country: string;
        postal_code?: string;
    };
    settings?: {
        tax_rate: number;
        currency: string;
        timezone: string;
    };
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

interface Props {
    stores: Store[];
    currencies: Record<string, string>;
    timezones: Record<string, string>;
    countries: Record<string, string>;
}

export default function StoresSettings({ stores, currencies, timezones, countries }: Props) {
    const [isCreateDialogOpen, setIsCreateDialogOpen] = useState(false);
    const [isEditDialogOpen, setIsEditDialogOpen] = useState(false);
    const [editingStore, setEditingStore] = useState<Store | null>(null);

    const { data: createData, setData: setCreateData, post: createPost, processing: createProcessing, errors: createErrors, reset: resetCreate } = useForm({
        name: '',
        code: '',
        type: 'store' as 'store' | 'warehouse',
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
        is_active: true,
    });

    const { data: editData, setData: setEditData, patch: editPatch, processing: editProcessing, errors: editErrors } = useForm({
        name: '',
        code: '',
        type: 'store' as 'store' | 'warehouse',
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
        is_active: true,
    });

    const handleCreateSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        createPost('/settings/stores', {
            onSuccess: () => {
                setIsCreateDialogOpen(false);
                resetCreate();
            },
        });
    };

    const handleEditSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!editingStore) return;
        
        editPatch(`/settings/stores/${editingStore.id}`, {
            onSuccess: () => {
                setIsEditDialogOpen(false);
                setEditingStore(null);
            },
        });
    };

    const handleEdit = (store: Store) => {
        setEditingStore(store);
        setEditData({
            name: store.name,
            code: store.code,
            type: store.type,
            email: store.email || '',
            phone: store.phone || '',
            address: store.address?.street || '',
            city: store.address?.city || '',
            state: store.address?.state || '',
            country: store.address?.country || 'HN',
            postal_code: store.address?.postal_code || '',
            tax_rate: store.settings?.tax_rate || 0.15,
            currency: store.settings?.currency || 'HNL',
            timezone: store.settings?.timezone || 'America/Tegucigalpa',
            is_active: store.is_active,
        });
        setIsEditDialogOpen(true);
    };

    const handleDelete = (storeId: string) => {
        if (confirm('Are you sure you want to delete this store? This action cannot be undone.')) {
            router.delete(`/settings/stores/${storeId}`);
        }
    };

    const getStoreTypeIcon = (type: string) => {
        return type === 'warehouse' ? Warehouse : StoreIcon;
    };

    return (
        <AppLayout>
            <Head title="Store Management" />
            
            <div className="space-y-6">
                <div className="flex justify-between items-center">
                    <div>
                        <h2 className="text-3xl font-bold tracking-tight">Store Management</h2>
                        <p className="text-muted-foreground">
                            Manage your stores, warehouses, and business locations.
                        </p>
                    </div>
                    <Dialog open={isCreateDialogOpen} onOpenChange={setIsCreateDialogOpen}>
                        <DialogTrigger asChild>
                            <Button>
                                <Plus className="h-4 w-4 mr-2" />
                                Add Store
                            </Button>
                        </DialogTrigger>
                        <DialogContent className="max-w-2xl max-h-[80vh] overflow-y-auto">
                            <DialogHeader>
                                <DialogTitle>Create New Store</DialogTitle>
                                <DialogDescription>
                                    Add a new store or warehouse location to your business.
                                </DialogDescription>
                            </DialogHeader>
                            <form onSubmit={handleCreateSubmit} className="space-y-4">
                                {/* Basic Information */}
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="create_name">Store Name *</Label>
                                        <Input
                                            id="create_name"
                                            value={createData.name}
                                            onChange={(e) => setCreateData('name', e.target.value)}
                                            placeholder="Main Store"
                                            required
                                        />
                                        {createErrors.name && (
                                            <p className="text-sm text-red-600">{createErrors.name}</p>
                                        )}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="create_code">Store Code *</Label>
                                        <Input
                                            id="create_code"
                                            value={createData.code}
                                            onChange={(e) => setCreateData('code', e.target.value.toUpperCase())}
                                            placeholder="MAIN"
                                            maxLength={10}
                                            required
                                        />
                                        {createErrors.code && (
                                            <p className="text-sm text-red-600">{createErrors.code}</p>
                                        )}
                                    </div>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="create_type">Store Type *</Label>
                                    <Select
                                        value={createData.type}
                                        onValueChange={(value: 'store' | 'warehouse') => setCreateData('type', value)}
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="store">Retail Store</SelectItem>
                                            <SelectItem value="warehouse">Warehouse</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                {/* Contact Information */}
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="create_email">Email</Label>
                                        <Input
                                            id="create_email"
                                            type="email"
                                            value={createData.email}
                                            onChange={(e) => setCreateData('email', e.target.value)}
                                            placeholder="store@example.com"
                                        />
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="create_phone">Phone</Label>
                                        <Input
                                            id="create_phone"
                                            value={createData.phone}
                                            onChange={(e) => setCreateData('phone', e.target.value)}
                                            placeholder="+504 2222-2222"
                                        />
                                    </div>
                                </div>

                                {/* Address */}
                                <div className="space-y-2">
                                    <Label htmlFor="create_address">Address</Label>
                                    <Textarea
                                        id="create_address"
                                        value={createData.address}
                                        onChange={(e) => setCreateData('address', e.target.value)}
                                        placeholder="Street address..."
                                        rows={2}
                                    />
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="create_city">City</Label>
                                        <Input
                                            id="create_city"
                                            value={createData.city}
                                            onChange={(e) => setCreateData('city', e.target.value)}
                                            placeholder="Tegucigalpa"
                                        />
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="create_state">State/Department</Label>
                                        <Input
                                            id="create_state"
                                            value={createData.state}
                                            onChange={(e) => setCreateData('state', e.target.value)}
                                            placeholder="Francisco Morazán"
                                        />
                                    </div>
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="create_country">Country *</Label>
                                        <Select
                                            value={createData.country}
                                            onValueChange={(value) => setCreateData('country', value)}
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
                                        <Label htmlFor="create_postal_code">Postal Code</Label>
                                        <Input
                                            id="create_postal_code"
                                            value={createData.postal_code}
                                            onChange={(e) => setCreateData('postal_code', e.target.value)}
                                            placeholder="11101"
                                        />
                                    </div>
                                </div>

                                {/* Settings */}
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="create_currency">Currency *</Label>
                                        <Select
                                            value={createData.currency}
                                            onValueChange={(value) => setCreateData('currency', value)}
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
                                        <Label htmlFor="create_timezone">Timezone *</Label>
                                        <Select
                                            value={createData.timezone}
                                            onValueChange={(value) => setCreateData('timezone', value)}
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
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="create_tax_rate">Tax Rate (%)</Label>
                                    <Input
                                        id="create_tax_rate"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        max="100"
                                        value={(createData.tax_rate * 100).toFixed(2)}
                                        onChange={(e) => setCreateData('tax_rate', parseFloat(e.target.value) / 100)}
                                    />
                                </div>

                                <div className="flex items-center space-x-2">
                                    <Switch
                                        id="create_is_active"
                                        checked={createData.is_active}
                                        onCheckedChange={(checked) => setCreateData('is_active', checked)}
                                    />
                                    <Label htmlFor="create_is_active">Store is active</Label>
                                </div>

                                <div className="flex justify-end space-x-2">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() => setIsCreateDialogOpen(false)}
                                    >
                                        Cancel
                                    </Button>
                                    <Button type="submit" disabled={createProcessing}>
                                        {createProcessing ? 'Creating...' : 'Create Store'}
                                    </Button>
                                </div>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>

                {/* Summary Cards */}
                <div className="grid gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Stores</CardTitle>
                            <StoreIcon className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {stores.filter(s => s.type === 'store').length}
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Warehouses</CardTitle>
                            <Warehouse className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {stores.filter(s => s.type === 'warehouse').length}
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Active Locations</CardTitle>
                            <div className="h-2 w-2 bg-green-500 rounded-full" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {stores.filter(s => s.is_active).length}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Stores Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>Stores & Warehouses</CardTitle>
                        <CardDescription>
                            Manage all your business locations
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Code</TableHead>
                                    <TableHead>Type</TableHead>
                                    <TableHead>Location</TableHead>
                                    <TableHead>Currency</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {stores.map((store) => {
                                    const StoreTypeIcon = getStoreTypeIcon(store.type);
                                    return (
                                        <TableRow key={store.id}>
                                            <TableCell>
                                                <div className="flex items-center space-x-2">
                                                    <StoreTypeIcon className="h-4 w-4 text-muted-foreground" />
                                                    <span className="font-medium">{store.name}</span>
                                                </div>
                                            </TableCell>
                                            <TableCell className="font-mono">{store.code}</TableCell>
                                            <TableCell>
                                                <Badge variant="outline">
                                                    {store.type === 'store' ? 'Retail Store' : 'Warehouse'}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                {store.address?.city && store.address?.country ? (
                                                    <span>{store.address.city}, {countries[store.address.country]}</span>
                                                ) : (
                                                    <span className="text-muted-foreground">Not set</span>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                <span className="font-mono">
                                                    {store.settings?.currency || 'HNL'}
                                                </span>
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant={store.is_active ? 'default' : 'secondary'}>
                                                    {store.is_active ? 'Active' : 'Inactive'}
                                                </Badge>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <div className="flex justify-end space-x-2">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => handleEdit(store)}
                                                    >
                                                        <Edit className="h-4 w-4" />
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => handleDelete(store.id)}
                                                        className="text-red-600 hover:text-red-700"
                                                    >
                                                        <Trash2 className="h-4 w-4" />
                                                    </Button>
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    );
                                })}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                {/* Edit Store Dialog - Similar structure to create but populated with existing data */}
                <Dialog open={isEditDialogOpen} onOpenChange={setIsEditDialogOpen}>
                    <DialogContent className="max-w-2xl max-h-[80vh] overflow-y-auto">
                        <DialogHeader>
                            <DialogTitle>Edit Store</DialogTitle>
                            <DialogDescription>
                                Update store information for {editingStore?.name}.
                            </DialogDescription>
                        </DialogHeader>
                        <form onSubmit={handleEditSubmit} className="space-y-4">
                            {/* Same form structure as create but with edit data */}
                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="edit_name">Store Name *</Label>
                                    <Input
                                        id="edit_name"
                                        value={editData.name}
                                        onChange={(e) => setEditData('name', e.target.value)}
                                        required
                                    />
                                    {editErrors.name && (
                                        <p className="text-sm text-red-600">{editErrors.name}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="edit_code">Store Code *</Label>
                                    <Input
                                        id="edit_code"
                                        value={editData.code}
                                        onChange={(e) => setEditData('code', e.target.value.toUpperCase())}
                                        maxLength={10}
                                        required
                                    />
                                    {editErrors.code && (
                                        <p className="text-sm text-red-600">{editErrors.code}</p>
                                    )}
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="edit_type">Store Type *</Label>
                                <Select
                                    value={editData.type}
                                    onValueChange={(value: 'store' | 'warehouse') => setEditData('type', value)}
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="store">Retail Store</SelectItem>
                                        <SelectItem value="warehouse">Warehouse</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="flex items-center space-x-2">
                                <Switch
                                    id="edit_is_active"
                                    checked={editData.is_active}
                                    onCheckedChange={(checked) => setEditData('is_active', checked)}
                                />
                                <Label htmlFor="edit_is_active">Store is active</Label>
                            </div>

                            <div className="flex justify-end space-x-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setIsEditDialogOpen(false)}
                                >
                                    Cancel
                                </Button>
                                <Button type="submit" disabled={editProcessing}>
                                    {editProcessing ? 'Updating...' : 'Update Store'}
                                </Button>
                            </div>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}