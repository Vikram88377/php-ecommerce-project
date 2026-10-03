<?php

function getValidCoupon(PDO $pdo, string $code, float $subtotal)
{
    $code = strtoupper(trim($code));

    if ($code === '') {
        return [
            'success' => false,
            'message' => 'Please enter coupon code.'
        ];
    }

    $stmt = $pdo->prepare("
        SELECT *
        FROM coupons
        WHERE code = ?
        AND status = 'active'
        LIMIT 1
    ");

    $stmt->execute([$code]);

    $coupon = $stmt->fetch();

    if (!$coupon) {
        return [
            'success' => false,
            'message' => 'Invalid coupon code.'
        ];
    }

    // Start date
    if (!empty($coupon['starts_at'])) {
        if (strtotime($coupon['starts_at']) > time()) {
            return [
                'success' => false,
                'message' => 'This coupon is not active yet.'
            ];
        }
    }

    // Expiry date
    if (!empty($coupon['expires_at'])) {
        if (strtotime($coupon['expires_at']) < time()) {
            return [
                'success' => false,
                'message' => 'This coupon has expired.'
            ];
        }
    }

    // Usage limit
    if (
        $coupon['usage_limit'] !== null &&
        $coupon['used_count'] >= $coupon['usage_limit']
    ) {
        return [
            'success' => false,
            'message' => 'This coupon usage limit has been reached.'
        ];
    }

    // Minimum order amount
    if ($subtotal < (float) $coupon['minimum_amount']) {
        return [
            'success' => false,
            'message' => 'Minimum order amount for this coupon is ₹'
                . number_format($coupon['minimum_amount'], 2)
        ];
    }

    // Calculate discount
    if ($coupon['type'] === 'percentage') {

        $discount = ($subtotal * (float) $coupon['value']) / 100;

        if (
            $coupon['maximum_discount'] !== null &&
            $discount > (float) $coupon['maximum_discount']
        ) {
            $discount = (float) $coupon['maximum_discount'];
        }

    } else {

        $discount = (float) $coupon['value'];
    }

    // Discount cannot be greater than subtotal
    if ($discount > $subtotal) {
        $discount = $subtotal;
    }

    return [
        'success' => true,
        'coupon' => $coupon,
        'discount' => round($discount, 2)
    ];
}