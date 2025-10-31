<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AccountingConfiguration extends Model
{
    use HasFactory;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_name',
        'company_registration',
        'legal_form',
        'company_address',
        'fiscal_year_start',
        'accounting_method',
        'base_currency',
        'multi_currency_enabled',
        'enabled_currencies',
        'tax_rate',
        'tax_number',
        'tax_inclusive_pricing',
        'chart_of_accounts',
        'account_numbering_scheme',
        'use_departments',
        'use_cost_centers',
        'use_projects',
        'invoice_numbering_pattern',
        'receipt_numbering_pattern',
        'next_invoice_number',
        'next_receipt_number',
        'backup_settings',
        'integration_settings',
        'is_configured',
        'configured_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'multi_currency_enabled' => 'boolean',
        'enabled_currencies' => 'array',
        'tax_rate' => 'decimal:4',
        'tax_inclusive_pricing' => 'boolean',
        'chart_of_accounts' => 'array',
        'account_numbering_scheme' => 'array',
        'use_departments' => 'boolean',
        'use_cost_centers' => 'boolean',
        'use_projects' => 'boolean',
        'backup_settings' => 'array',
        'integration_settings' => 'array',
        'is_configured' => 'boolean',
        'configured_at' => 'datetime',
    ];

    /**
     * Boot function to automatically generate UUID for new configurations.
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = Str::uuid()->toString();
            }
        });
    }

    /**
     * Mark the configuration as completed.
     */
    public function markAsConfigured(): void
    {
        $this->update([
            'is_configured' => true,
            'configured_at' => now(),
        ]);
    }

    /**
     * Get the default chart of accounts for Honduras.
     *
     * @return array
     */
    public static function getDefaultChartOfAccounts(): array
    {
        return [
            '1000' => [
                'name' => 'Activos',
                'type' => 'asset',
                'accounts' => [
                    '1100' => [
                        'name' => 'Activos Corrientes',
                        'accounts' => [
                            '1101' => 'Efectivo y Equivalentes',
                            '1102' => 'Caja Chica',
                            '1103' => 'Bancos',
                            '1104' => 'Cuentas por Cobrar',
                            '1105' => 'Inventarios',
                            '1106' => 'Gastos Pagados por Anticipado',
                        ]
                    ],
                    '1200' => [
                        'name' => 'Activos No Corrientes',
                        'accounts' => [
                            '1201' => 'Propiedades, Planta y Equipo',
                            '1202' => 'Depreciación Acumulada',
                            '1203' => 'Activos Intangibles',
                        ]
                    ]
                ]
            ],
            '2000' => [
                'name' => 'Pasivos',
                'type' => 'liability',
                'accounts' => [
                    '2100' => [
                        'name' => 'Pasivos Corrientes',
                        'accounts' => [
                            '2101' => 'Cuentas por Pagar',
                            '2102' => 'ISV por Pagar',
                            '2103' => 'Retenciones por Pagar',
                            '2104' => 'Préstamos Bancarios Corto Plazo',
                        ]
                    ],
                    '2200' => [
                        'name' => 'Pasivos No Corrientes',
                        'accounts' => [
                            '2201' => 'Préstamos Bancarios Largo Plazo',
                            '2202' => 'Provisiones',
                        ]
                    ]
                ]
            ],
            '3000' => [
                'name' => 'Patrimonio',
                'type' => 'equity',
                'accounts' => [
                    '3101' => 'Capital Social',
                    '3102' => 'Utilidades Retenidas',
                    '3103' => 'Utilidad del Ejercicio',
                ]
            ],
            '4000' => [
                'name' => 'Ingresos',
                'type' => 'revenue',
                'accounts' => [
                    '4101' => 'Ventas',
                    '4102' => 'Servicios',
                    '4103' => 'Otros Ingresos',
                ]
            ],
            '5000' => [
                'name' => 'Gastos',
                'type' => 'expense',
                'accounts' => [
                    '5101' => 'Costo de Ventas',
                    '5102' => 'Gastos Administrativos',
                    '5103' => 'Gastos de Ventas',
                    '5104' => 'Gastos Financieros',
                ]
            ]
        ];
    }

    /**
     * Get the next invoice number and increment counter.
     *
     * @return string
     */
    public function getNextInvoiceNumber(): string
    {
        $number = $this->next_invoice_number;
        $this->increment('next_invoice_number');
        
        return $this->formatDocumentNumber($this->invoice_numbering_pattern, $number);
    }

    /**
     * Get the next receipt number and increment counter.
     *
     * @return string
     */
    public function getNextReceiptNumber(): string
    {
        $number = $this->next_receipt_number;
        $this->increment('next_receipt_number');
        
        return $this->formatDocumentNumber($this->receipt_numbering_pattern, $number);
    }

    /**
     * Format document number based on pattern.
     *
     * @param string $pattern
     * @param int $number
     * @return string
     */
    private function formatDocumentNumber(string $pattern, int $number): string
    {
        $replacements = [
            '{YYYY}' => date('Y'),
            '{YY}' => date('y'),
            '{MM}' => date('m'),
            '{DD}' => date('d'),
            '{####}' => str_pad($number, 4, '0', STR_PAD_LEFT),
            '{###}' => str_pad($number, 3, '0', STR_PAD_LEFT),
            '{##}' => str_pad($number, 2, '0', STR_PAD_LEFT),
        ];

        return strtr($pattern, $replacements);
    }
}