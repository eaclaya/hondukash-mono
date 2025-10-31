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
import { Plus, Edit, Trash2, Eye } from 'lucide-react';

interface Account {
    name: string;
    type: string;
    parent_code?: string;
    description?: string;
    is_active: boolean;
}

interface Props {
    chartOfAccounts: Record<string, Account>;
    accountNumberingScheme: Record<string, number>;
    accountTypes: Record<string, string>;
}

export default function ChartOfAccountsSettings({ 
    chartOfAccounts, 
    accountNumberingScheme,
    accountTypes 
}: Props) {
    const [isCreateDialogOpen, setIsCreateDialogOpen] = useState(false);
    const [isEditDialogOpen, setIsEditDialogOpen] = useState(false);
    const [editingAccount, setEditingAccount] = useState<{ code: string; account: Account } | null>(null);

    const { data: createData, setData: setCreateData, post: createPost, processing: createProcessing, errors: createErrors, reset: resetCreate } = useForm({
        code: '',
        name: '',
        type: '',
        parent_code: '',
        description: '',
    });

    const { data: editData, setData: setEditData, patch: editPatch, processing: editProcessing, errors: editErrors } = useForm({
        name: '',
        type: '',
        parent_code: '',
        description: '',
        is_active: true,
    });

    const handleCreateSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        createPost('/settings/chart-of-accounts', {
            onSuccess: () => {
                setIsCreateDialogOpen(false);
                resetCreate();
            },
        });
    };

    const handleEditSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (!editingAccount) return;
        
        editPatch(`/settings/chart-of-accounts/${editingAccount.code}`, {
            onSuccess: () => {
                setIsEditDialogOpen(false);
                setEditingAccount(null);
            },
        });
    };

    const handleEdit = (code: string, account: Account) => {
        setEditingAccount({ code, account });
        setEditData({
            name: account.name,
            type: account.type,
            parent_code: account.parent_code || '',
            description: account.description || '',
            is_active: account.is_active,
        });
        setIsEditDialogOpen(true);
    };

    const handleDelete = (code: string) => {
        if (confirm('Are you sure you want to delete this account?')) {
            router.delete(`/settings/chart-of-accounts/${code}`);
        }
    };

    const getAccountTypeColor = (type: string) => {
        const colors = {
            asset: 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100',
            liability: 'bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100',
            equity: 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100',
            revenue: 'bg-purple-100 text-purple-800 dark:bg-purple-800 dark:text-purple-100',
            expense: 'bg-orange-100 text-orange-800 dark:bg-orange-800 dark:text-orange-100',
        };
        return colors[type as keyof typeof colors] || 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100';
    };

    const getParentAccounts = (type: string) => {
        return Object.entries(chartOfAccounts).filter(([_, account]) => 
            account.type === type
        );
    };

    const sortedAccounts = Object.entries(chartOfAccounts).sort(([a], [b]) => a.localeCompare(b));

    return (
        <AppLayout>
            <Head title="Chart of Accounts" />
            
            <div className="space-y-6">
                <div className="flex justify-between items-center">
                    <div>
                        <h2 className="text-3xl font-bold tracking-tight">Chart of Accounts</h2>
                        <p className="text-muted-foreground">
                            Manage your chart of accounts and account structure.
                        </p>
                    </div>
                    <Dialog open={isCreateDialogOpen} onOpenChange={setIsCreateDialogOpen}>
                        <DialogTrigger asChild>
                            <Button>
                                <Plus className="h-4 w-4 mr-2" />
                                Add Account
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Create New Account</DialogTitle>
                                <DialogDescription>
                                    Add a new account to your chart of accounts.
                                </DialogDescription>
                            </DialogHeader>
                            <form onSubmit={handleCreateSubmit} className="space-y-4">
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="create_code">Account Code *</Label>
                                        <Input
                                            id="create_code"
                                            value={createData.code}
                                            onChange={(e) => setCreateData('code', e.target.value)}
                                            placeholder="1000"
                                            required
                                        />
                                        {createErrors.code && (
                                            <p className="text-sm text-red-600">{createErrors.code}</p>
                                        )}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="create_type">Account Type *</Label>
                                        <Select
                                            value={createData.type}
                                            onValueChange={(value) => setCreateData('type', value)}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select type" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {Object.entries(accountTypes).map(([value, label]) => (
                                                    <SelectItem key={value} value={value}>
                                                        {label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        {createErrors.type && (
                                            <p className="text-sm text-red-600">{createErrors.type}</p>
                                        )}
                                    </div>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="create_name">Account Name *</Label>
                                    <Input
                                        id="create_name"
                                        value={createData.name}
                                        onChange={(e) => setCreateData('name', e.target.value)}
                                        placeholder="Cash and Cash Equivalents"
                                        required
                                    />
                                    {createErrors.name && (
                                        <p className="text-sm text-red-600">{createErrors.name}</p>
                                    )}
                                </div>

                                {createData.type && (
                                    <div className="space-y-2">
                                        <Label htmlFor="create_parent_code">Parent Account</Label>
                                        <Select
                                            value={createData.parent_code}
                                            onValueChange={(value) => setCreateData('parent_code', value)}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select parent account (optional)" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {getParentAccounts(createData.type).map(([code, account]) => (
                                                    <SelectItem key={code} value={code}>
                                                        {code} - {account.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                )}

                                <div className="space-y-2">
                                    <Label htmlFor="create_description">Description</Label>
                                    <Textarea
                                        id="create_description"
                                        value={createData.description}
                                        onChange={(e) => setCreateData('description', e.target.value)}
                                        placeholder="Optional description for this account"
                                        rows={3}
                                    />
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
                                        {createProcessing ? 'Creating...' : 'Create Account'}
                                    </Button>
                                </div>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>

                {/* Account Numbering Scheme */}
                <Card>
                    <CardHeader>
                        <CardTitle>Account Numbering Scheme</CardTitle>
                        <CardDescription>
                            Current numbering ranges for different account types
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-2 md:grid-cols-5 gap-4">
                            {Object.entries(accountNumberingScheme).map(([type, start]) => (
                                <div key={type} className="text-center">
                                    <div className="text-sm font-medium text-muted-foreground capitalize">
                                        {type.replace('_start', '')}
                                    </div>
                                    <div className="text-lg font-semibold">
                                        {start}+
                                    </div>
                                </div>
                            ))}
                        </div>
                    </CardContent>
                </Card>

                {/* Chart of Accounts Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>Accounts</CardTitle>
                        <CardDescription>
                            {sortedAccounts.length} accounts configured
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Code</TableHead>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Type</TableHead>
                                    <TableHead>Parent</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {sortedAccounts.map(([code, account]) => (
                                    <TableRow key={code}>
                                        <TableCell className="font-mono">{code}</TableCell>
                                        <TableCell className="font-medium">{account.name}</TableCell>
                                        <TableCell>
                                            <Badge className={getAccountTypeColor(account.type)}>
                                                {accountTypes[account.type]}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            {account.parent_code ? (
                                                <span className="font-mono text-sm">
                                                    {account.parent_code}
                                                </span>
                                            ) : (
                                                <span className="text-muted-foreground">None</span>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant={account.is_active ? 'default' : 'secondary'}>
                                                {account.is_active ? 'Active' : 'Inactive'}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <div className="flex justify-end space-x-2">
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => handleEdit(code, account)}
                                                >
                                                    <Edit className="h-4 w-4" />
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => handleDelete(code)}
                                                    className="text-red-600 hover:text-red-700"
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                {/* Edit Account Dialog */}
                <Dialog open={isEditDialogOpen} onOpenChange={setIsEditDialogOpen}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Edit Account</DialogTitle>
                            <DialogDescription>
                                Update account details for {editingAccount?.code}.
                            </DialogDescription>
                        </DialogHeader>
                        <form onSubmit={handleEditSubmit} className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="edit_name">Account Name *</Label>
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
                                <Label htmlFor="edit_type">Account Type *</Label>
                                <Select
                                    value={editData.type}
                                    onValueChange={(value) => setEditData('type', value)}
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {Object.entries(accountTypes).map(([value, label]) => (
                                            <SelectItem key={value} value={value}>
                                                {label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {editErrors.type && (
                                    <p className="text-sm text-red-600">{editErrors.type}</p>
                                )}
                            </div>

                            {editData.type && (
                                <div className="space-y-2">
                                    <Label htmlFor="edit_parent_code">Parent Account</Label>
                                    <Select
                                        value={editData.parent_code}
                                        onValueChange={(value) => setEditData('parent_code', value)}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select parent account (optional)" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {getParentAccounts(editData.type).map(([code, account]) => (
                                                <SelectItem key={code} value={code}>
                                                    {code} - {account.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            )}

                            <div className="space-y-2">
                                <Label htmlFor="edit_description">Description</Label>
                                <Textarea
                                    id="edit_description"
                                    value={editData.description}
                                    onChange={(e) => setEditData('description', e.target.value)}
                                    rows={3}
                                />
                            </div>

                            <div className="flex items-center space-x-2">
                                <Switch
                                    id="edit_is_active"
                                    checked={editData.is_active}
                                    onCheckedChange={(checked) => setEditData('is_active', checked)}
                                />
                                <Label htmlFor="edit_is_active">Account is active</Label>
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
                                    {editProcessing ? 'Updating...' : 'Update Account'}
                                </Button>
                            </div>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}