<?php

namespace App\Support;

use Illuminate\Support\Str;

class SalePaymentMode
{
    public const DEFAULT = 'cash';
    public const POS_SURCHARGE_RATE = 0.04;

    private const DIRECT_PAYMENT = [
        'cash' => 'Cash',
        'gcash' => 'GCash',
        'maya' => 'Maya',
        'bank_transfer' => 'Bank Transfer',
    ];

    private const BANK_CARDS = [
        'bank_card' => 'Bank Card',
    ];

    private const POS_SURCHARGE_METHODS = [
        'bank_card',
        // Legacy values retained for backward-compatible calculations.
        'pos',
        'bank_card_visa',
        'bank_card_mastercard',
        'bank_card_other',
        'bdo',
    ];

    private const INSTALLMENT = [
        'skyro_installment' => 'Skyro (Installment)',
        'homecredit_installment' => 'Home Credit (Installment)',
        'billease_installment' => 'Billease (Installment)',
        'bdo_credit_card_installment' => 'BDO Credit Card (Installment)',
    ];

    private const SYSTEM_METHODS = [
        'initial' => 'Initial Payment',
        'adjustment' => 'Manual Adjustment',
        'legacy' => 'Legacy Payment',
        // Kept for display compatibility with existing historical records.
        'pos' => 'POS',
        'bdo' => 'BDO',
        'bank_card_visa' => 'Bank Card (Visa)',
        'bank_card_mastercard' => 'Bank Card (Mastercard)',
        'bank_card_other' => 'Bank Card (Other)',
    ];

    public static function groups(): array
    {
        return [
            'Direct Payment' => self::DIRECT_PAYMENT,
            'POS' => self::BANK_CARDS,
            'Installment' => self::INSTALLMENT,
        ];
    }

    public static function selectableValues(): array
    {
        return array_keys(self::selectableOptions());
    }

    public static function selectableOptions(): array
    {
        return array_merge(
            self::DIRECT_PAYMENT,
            self::BANK_CARDS,
            self::INSTALLMENT,
        );
    }

    public static function label(?string $value): string
    {
        $mode = trim((string) $value);
        if ($mode === '') {
            return '-';
        }

        $labels = array_merge(
            self::DIRECT_PAYMENT,
            self::BANK_CARDS,
            self::INSTALLMENT,
            self::SYSTEM_METHODS,
        );

        if (isset($labels[$mode])) {
            return $labels[$mode];
        }

        return (string) Str::of($mode)->replace(['_', '-'], ' ')->title();
    }

    public static function posSurchargeRate(): float
    {
        return self::POS_SURCHARGE_RATE;
    }

    public static function hasPosSurcharge(?string $value): bool
    {
        $mode = strtolower(trim((string) $value));
        if ($mode === '') {
            return false;
        }

        return in_array($mode, self::POS_SURCHARGE_METHODS, true);
    }

    public static function posSurchargeModes(): array
    {
        return self::POS_SURCHARGE_METHODS;
    }

    public static function applyPosSurcharge(float $amount, ?string $value): float
    {
        $baseAmount = max(0, $amount);
        if (! self::hasPosSurcharge($value)) {
            return $baseAmount;
        }

        return $baseAmount * (1 + self::POS_SURCHARGE_RATE);
    }
}
