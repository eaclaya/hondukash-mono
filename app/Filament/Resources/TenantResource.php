<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TenantResource\Pages;
use App\Filament\Resources\TenantResource\RelationManagers;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Tenant;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    
    protected static ?string $navigationLabel = 'Tenants';
    
    protected static ?string $modelLabel = 'Tenant';
    
    protected static ?string $pluralModelLabel = 'Tenants';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Tenant Name'),
                        
                        Forms\Components\TextInput::make('company_name')
                            ->required()
                            ->maxLength(255)
                            ->label('Company Name'),
                        
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(255),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Address & Location')
                    ->schema([
                        Forms\Components\Textarea::make('address')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        
                        Forms\Components\TextInput::make('country')
                            ->maxLength(255),
                        
                        Forms\Components\Select::make('timezone')
                            ->options([
                                'America/Tegucigalpa' => 'America/Tegucigalpa (CST)',
                                'America/New_York' => 'America/New_York (EST)',
                                'America/Chicago' => 'America/Chicago (CST)',
                                'America/Denver' => 'America/Denver (MST)',
                                'America/Los_Angeles' => 'America/Los_Angeles (PST)',
                                'Europe/London' => 'Europe/London (GMT)',
                                'Europe/Madrid' => 'Europe/Madrid (CET)',
                            ])
                            ->default('America/Tegucigalpa')
                            ->required(),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Configuration')
                    ->schema([
                        Forms\Components\Select::make('currency')
                            ->options([
                                'HNL' => 'Honduran Lempira (HNL)',
                                'USD' => 'US Dollar (USD)',
                                'EUR' => 'Euro (EUR)',
                                'GBP' => 'British Pound (GBP)',
                            ])
                            ->default('HNL')
                            ->required(),
                        
                        Forms\Components\Select::make('language')
                            ->options([
                                'es' => 'Español',
                                'en' => 'English',
                            ])
                            ->default('es')
                            ->required(),
                        
                        Forms\Components\Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'suspended' => 'Suspended',
                                'canceled' => 'Canceled',
                            ])
                            ->default('active')
                            ->required(),
                        
                        Forms\Components\Select::make('plan')
                            ->options([
                                'basic' => 'Basic',
                                'pro' => 'Professional',
                                'enterprise' => 'Enterprise',
                            ])
                            ->default('basic')
                            ->required(),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Trial Information')
                    ->schema([
                        Forms\Components\DateTimePicker::make('trial_ends_at')
                            ->label('Trial Ends At')
                            ->nullable(),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('name')
                    ->label('Tenant Name')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('company_name')
                    ->label('Company')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'suspended' => 'warning', 
                        'canceled' => 'danger',
                        default => 'secondary',
                    }),
                
                Tables\Columns\TextColumn::make('plan')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'basic' => 'secondary',
                        'pro' => 'primary',
                        'enterprise' => 'success',
                        default => 'secondary',
                    }),
                
                Tables\Columns\TextColumn::make('currency')
                    ->label('Currency'),
                
                Tables\Columns\TextColumn::make('trial_ends_at')
                    ->label('Trial Ends')
                    ->dateTime()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'suspended' => 'Suspended',
                        'canceled' => 'Canceled',
                    ]),
                
                Tables\Filters\SelectFilter::make('plan')
                    ->options([
                        'basic' => 'Basic',
                        'pro' => 'Professional',
                        'enterprise' => 'Enterprise',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\DomainsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenants::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
        ];
    }
}
