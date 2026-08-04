<?php

namespace Modules\RequisitionSystem\Support;

use Modules\RequisitionSystem\Models\Setting;

/**
 * Line pricing: distribute a header discount across lines, then apply GST
 * on the post-discount amount for GST-applicable lines.
 */
final class RequisitionLinePricing
{
    public const DISCOUNT_NONE = 'none';

    public const DISCOUNT_PERCENT = 'percent';

    public const DISCOUNT_AMOUNT = 'amount';

    /**
     * @param  array<int, array{quantity?: mixed, unit_cost?: mixed, gst_applicable?: mixed, comments?: mixed, chart_of_account_id?: mixed}>  $items
     * @return array{
     *   items: list<array<string, mixed>>,
     *   lines_subtotal: float,
     *   discount_type: string,
     *   discount_value: float,
     *   discount_amount: float,
     *   gst_rate_percent: float,
     *   gst_total: float,
     *   total: float
     * }
     */
    public static function calculate(
        array $items,
        string $discountType = self::DISCOUNT_NONE,
        float $discountValue = 0.0,
        ?float $gstRatePercent = null
    ): array {
        $discountType = self::normalizeDiscountType($discountType);
        $gstRatePercent ??= Setting::gstRatePercent();

        $prepared = [];
        $linesSubtotal = 0.0;

        foreach (array_values($items) as $item) {
            $quantity = (float) ($item['quantity'] ?? 0);
            $unitCost = (float) ($item['unit_cost'] ?? 0);
            $subtotal = round($quantity * $unitCost, 2);
            $linesSubtotal = round($linesSubtotal + $subtotal, 2);

            $prepared[] = [
                'chart_of_account_id' => $item['chart_of_account_id'] ?? null,
                'quantity' => (int) $quantity,
                'unit_cost' => round($unitCost, 2),
                'subtotal' => $subtotal,
                'gst_applicable' => (bool) ($item['gst_applicable'] ?? false),
                'comments' => $item['comments'] ?? null,
            ];
        }

        $discountAmount = self::headerDiscountAmount(
            $linesSubtotal,
            $discountType,
            $discountValue
        );

        $allocatedDiscount = 0.0;
        $gstTotal = 0.0;
        $grandTotal = 0.0;
        $count = count($prepared);

        foreach ($prepared as $index => &$line) {
            if ($count === 0) {
                $lineDiscount = 0.0;
            } elseif ($index === $count - 1) {
                $lineDiscount = round($discountAmount - $allocatedDiscount, 2);
            } elseif ($linesSubtotal > 0) {
                $lineDiscount = round(
                    $discountAmount * ($line['subtotal'] / $linesSubtotal),
                    2
                );
                $allocatedDiscount = round($allocatedDiscount + $lineDiscount, 2);
            } else {
                $lineDiscount = 0.0;
            }

            $lineDiscount = self::clampLineDiscount($lineDiscount, $line['subtotal']);
            $afterDiscount = round($line['subtotal'] - $lineDiscount, 2);
            $gstAmount = $line['gst_applicable']
                ? round($afterDiscount * $gstRatePercent / 100, 2)
                : 0.0;
            $lineTotal = round($afterDiscount + $gstAmount, 2);

            $line['discount_amount'] = $lineDiscount;
            $line['gst_amount'] = $gstAmount;
            $line['total'] = $lineTotal;

            $gstTotal = round($gstTotal + $gstAmount, 2);
            $grandTotal = round($grandTotal + $lineTotal, 2);
        }
        unset($line);

        return [
            'items' => $prepared,
            'lines_subtotal' => $linesSubtotal,
            'discount_type' => $discountType,
            'discount_value' => round($discountValue, 2),
            'discount_amount' => $discountAmount,
            'gst_rate_percent' => $gstRatePercent,
            'gst_total' => $gstTotal,
            'total' => $grandTotal,
        ];
    }

    public static function normalizeDiscountType(?string $type): string
    {
        return match ($type) {
            self::DISCOUNT_PERCENT, self::DISCOUNT_AMOUNT => $type,
            default => self::DISCOUNT_NONE,
        };
    }

    public static function headerDiscountAmount(
        float $linesSubtotal,
        string $discountType,
        float $discountValue
    ): float {
        $discountType = self::normalizeDiscountType($discountType);

        if ($linesSubtotal <= 0 || $discountValue <= 0) {
            return 0.0;
        }

        $amount = match ($discountType) {
            self::DISCOUNT_PERCENT => round($linesSubtotal * $discountValue / 100, 2),
            self::DISCOUNT_AMOUNT => round($discountValue, 2),
            default => 0.0,
        };

        return min($amount, $linesSubtotal);
    }

    /**
     * Keep a line's discount share within its subtotal (supports credit/negative lines).
     */
    public static function clampLineDiscount(float $lineDiscount, float $subtotal): float
    {
        if ($lineDiscount > 0) {
            return min($lineDiscount, max($subtotal, 0.0));
        }

        if ($lineDiscount < 0) {
            return max($lineDiscount, min($subtotal, 0.0));
        }

        return 0.0;
    }
}
