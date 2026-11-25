<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/admin/config.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/razorpay-php/Razorpay.php';

use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

// 1. Verify user authentication
if (
    !isset($_SESSION['userid']) ||
    !filter_var($_SESSION['userid'], FILTER_VALIDATE_INT) ||
    (int)$_SESSION['userid'] <= 0
) {
    header("Location: login.php");
    exit();
}

$userid = (int)$_SESSION['userid'];

// 2. Only POST method allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: services.php");
    exit();
}

// 3. CSRF Verification
$postedToken = trim($_POST['csrf_token'] ?? '');
$sessionToken = $_SESSION['booking_csrf_token'] ?? '';

if (
    !is_string($sessionToken) ||
    $sessionToken === '' ||
    $postedToken === '' ||
    !hash_equals($sessionToken, $postedToken)
) {
    error_log("CSRF token verification failed in verify.php for user " . $userid);
    $_SESSION['booking_notice'] = [
        'type' => 'error',
        'title' => 'Security Token Invalid',
        'message' => 'CSRF verification failed. Please try your booking again.'
    ];
    header("Location: booking_status.php");
    exit();
}

// 4. Resolve and validate Booking ID
$bookingId = 0;
if (isset($_POST['booking_id']) && filter_var($_POST['booking_id'], FILTER_VALIDATE_INT)) {
    $bookingId = (int)$_POST['booking_id'];
} elseif (isset($_SESSION['current_booking_id']) && filter_var($_SESSION['current_booking_id'], FILTER_VALIDATE_INT)) {
    $bookingId = (int)$_SESSION['current_booking_id'];
}

if ($bookingId <= 0) {
    $_SESSION['booking_notice'] = [
        'type' => 'error',
        'title' => 'Booking Session Expired',
        'message' => 'Your booking session could not be found. Please select your service and book again.'
    ];
    header("Location: booking_status.php");
    exit();
}

// 5. Read Razorpay POST parameters
$paymentId = trim($_POST['razorpay_payment_id'] ?? '');
$orderId = trim($_POST['razorpay_order_id'] ?? '');
$signature = trim($_POST['razorpay_signature'] ?? '');
$paymentError = trim($_POST['razorpay_error'] ?? '');

// 6. Database Transaction & Row Lock on Booking
mysqli_begin_transaction($conn);

$stmt = mysqli_prepare(
    $conn,
    "SELECT id, service_name, user_id, username, email, contact, location, date, price, 
            status, transaction_id, razorpay_order_id, transaction_status, failure_reason 
     FROM booking 
     WHERE id = ? AND user_id = ? 
     FOR UPDATE"
);

if (!$stmt) {
    mysqli_rollback($conn);
    error_log("Verify query prepare failed: " . mysqli_error($conn));
    $_SESSION['booking_notice'] = [
        'type' => 'error',
        'title' => 'Database Error',
        'message' => 'Unable to access booking record. Please contact support.'
    ];
    header("Location: booking_status.php");
    exit();
}

mysqli_stmt_bind_param($stmt, "ii", $bookingId, $userid);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$booking = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$booking) {
    mysqli_rollback($conn);
    error_log("Booking not found or ownership mismatch for booking ID " . $bookingId . " and user " . $userid);
    $_SESSION['booking_notice'] = [
        'type' => 'error',
        'title' => 'Booking Record Not Found',
        'message' => 'Booking record not found or you do not have permission to access it.'
    ];
    header("Location: booking_status.php");
    exit();
}

// Prevent duplicate verification if already confirmed
if ((int)$booking['status'] === 1 && (int)$booking['transaction_status'] === 1) {
    mysqli_commit($conn);
    $_SESSION['completed_booking'] = [
        'id' => $booking['id'],
        'service_name' => $booking['service_name'],
        'date' => $booking['date'],
        'location' => $booking['location'],
        'price' => $booking['price'],
        'transaction_id' => $booking['transaction_id']
    ];
    header("Location: order_completed.php");
    exit();
}

// Verify that the order ID matches what was stored in the booking record
$dbOrderId = (string)($booking['razorpay_order_id'] ?? '');
if ($dbOrderId !== '' && $orderId !== '' && !hash_equals($dbOrderId, $orderId)) {
    mysqli_rollback($conn);
    error_log("Razorpay order ID mismatch: expected {$dbOrderId}, got {$orderId}");
    $_SESSION['booking_notice'] = [
        'type' => 'error',
        'title' => 'Invalid Payment Order',
        'message' => 'Payment order does not match our records.'
    ];
    header("Location: booking_status.php");
    exit();
}

// Use stored order ID if orderId was empty in POST
if ($orderId === '' && $dbOrderId !== '') {
    $orderId = $dbOrderId;
}

$now = date('Y-m-d H:i:s');

// 7. Handle Payment Cancellation or Failure (No payment ID or signature received)
if ($paymentId === '' || $signature === '') {
    $failedStatus = 2;
    $cancelReason = !empty($paymentError) ? substr($paymentError, 0, 250) : 'Payment cancelled or dismissed by customer';

    $failedStmt = mysqli_prepare(
        $conn,
        "UPDATE booking 
         SET status = ?, transaction_status = ?, transaction_date = ?, failure_reason = ? 
         WHERE id = ? AND user_id = ? AND status = 0"
    );

    if ($failedStmt) {
        mysqli_stmt_bind_param(
            $failedStmt,
            "iissii",
            $failedStatus,
            $failedStatus,
            $now,
            $cancelReason,
            $bookingId,
            $userid
        );
        mysqli_stmt_execute($failedStmt);
        mysqli_stmt_close($failedStmt);
    }

    mysqli_commit($conn);
    unset($_SESSION['current_booking_id'], $_SESSION['razorpay_order_id']);

    $isCancellation = (stripos($cancelReason, 'cancel') !== false || stripos($cancelReason, 'closed') !== false || stripos($cancelReason, 'dismiss') !== false);

    $_SESSION['booking_notice'] = [
        'type' => $isCancellation ? 'cancelled' : 'failed',
        'title' => $isCancellation ? 'Payment Cancelled' : 'Payment Failed',
        'message' => $isCancellation
            ? 'Your payment was cancelled. Your booking date has not been reserved. You may retry booking whenever you are ready.'
            : 'Your payment attempt was unsuccessful. Reason: ' . htmlspecialchars($cancelReason) . '. Please try booking again.',
        'booking_id' => $bookingId,
        'service_name' => $booking['service_name'],
        'date' => $booking['date']
    ];

    header("Location: booking_status.php");
    exit();
}

// 8. Verify Razorpay Payment Signature, Fetch Remote Payment & Validate
$paymentStatus = '';
try {
    if (empty($keyId) || empty($keySecret)) {
        throw new RuntimeException("Razorpay API credentials missing.");
    }

    $api = new Api($keyId, $keySecret);

    // Verify cryptographic signature securely
    $attributes = [
        'razorpay_order_id' => $orderId,
        'razorpay_payment_id' => $paymentId,
        'razorpay_signature' => $signature
    ];

    $api->utility->verifyPaymentSignature($attributes);

    // Fetch actual payment entity from Razorpay
    $payment = $api->payment->fetch($paymentId);

    if (!$payment) {
        throw new RuntimeException("Razorpay payment details could not be retrieved.");
    }

    // Validate order ID
    if ((string)$payment['order_id'] !== $orderId) {
        throw new RuntimeException("Fetched payment order ID does not match expected order.");
    }

    // Validate expected amount and currency against database booking
    $expectedAmountPaise = (int)round((float)$booking['price'] * 100);
    $receivedAmountPaise = (int)($payment['amount'] ?? 0);
    $receivedCurrency = strtoupper((string)($payment['currency'] ?? ''));

    if ($receivedAmountPaise !== $expectedAmountPaise) {
        throw new RuntimeException("Payment amount mismatch: expected {$expectedAmountPaise} paise, got {$receivedAmountPaise}");
    }

    if ($receivedCurrency !== 'INR') {
        throw new RuntimeException("Payment currency mismatch: expected INR, got {$receivedCurrency}");
    }

    $paymentStatus = (string)($payment['status'] ?? '');

    // Requirement: If payment is authorized but not captured, safely capture it through Razorpay API
    // after validating the booking, order ID, amount and currency.
    if ($paymentStatus === 'authorized') {
        try {
            $captureResponse = $payment->capture([
                'amount' => $expectedAmountPaise,
                'currency' => 'INR'
            ]);

            if ($captureResponse && in_array($captureResponse['status'], ['captured', 'paid'], true)) {
                $payment = $captureResponse;
                $paymentStatus = 'captured';
            } else {
                $paymentStatus = (string)($captureResponse['status'] ?? 'authorized');
            }
        } catch (Throwable $capEx) {
            error_log("Payment capture failed for payment " . $paymentId . " (booking " . $bookingId . "): " . $capEx->getMessage());
            // Keep current status (authorized)
        }
    }

} catch (SignatureVerificationError $e) {
    error_log("Razorpay signature verification failed for booking " . $bookingId . ": " . $e->getMessage());

    $failStatus = 2;
    $sigFailReason = "Signature verification failed";

    $sigStmt = mysqli_prepare(
        $conn,
        "UPDATE booking 
         SET status = ?, transaction_status = ?, transaction_id = ?, transaction_date = ?, failure_reason = ? 
         WHERE id = ? AND user_id = ?"
    );
    if ($sigStmt) {
        mysqli_stmt_bind_param($sigStmt, "iisssii", $failStatus, $failStatus, $paymentId, $now, $sigFailReason, $bookingId, $userid);
        mysqli_stmt_execute($sigStmt);
        mysqli_stmt_close($sigStmt);
    }
    mysqli_commit($conn);

    unset($_SESSION['current_booking_id'], $_SESSION['razorpay_order_id']);

    $_SESSION['booking_notice'] = [
        'type' => 'verification_failed',
        'title' => 'Payment Verification Failed',
        'message' => 'Security signature verification failed. Please contact customer support with Payment ID: ' . htmlspecialchars($paymentId),
        'booking_id' => $bookingId,
        'transaction_id' => $paymentId
    ];

    header("Location: booking_status.php");
    exit();

} catch (Throwable $e) {
    error_log("Razorpay fetch error for booking " . $bookingId . ": " . $e->getMessage());
    mysqli_rollback($conn);

    $_SESSION['booking_notice'] = [
        'type' => 'error',
        'title' => 'Payment Verification Error',
        'message' => 'An error occurred while communicating with the payment gateway. Please contact support with Payment ID: ' . htmlspecialchars($paymentId),
        'booking_id' => $bookingId,
        'transaction_id' => $paymentId
    ];

    header("Location: booking_status.php");
    exit();
}

// 9. Handle Remote Payment States Correctly
// Requirement: Mark booking successful only after payment is confirmed as captured.
// Do not mark an authorized payment as captured.
if ($paymentStatus === 'captured') {
    $bookingStatus = 1;       // Confirmed
    $transactionStatus = 1;   // Successful
    $reason = 'completed';

    $upStmt = mysqli_prepare(
        $conn,
        "UPDATE booking 
         SET status = ?, transaction_status = ?, transaction_id = ?, transaction_date = ?, failure_reason = ? 
         WHERE id = ? AND user_id = ? AND status = 0"
    );

    if (!$upStmt) {
        error_log("Payment captured update prepare failed: " . mysqli_error($conn));
        mysqli_rollback($conn);
        $_SESSION['booking_notice'] = [
            'type' => 'error',
            'title' => 'Update Error',
            'message' => 'Payment captured but booking update failed. Please contact support with Payment ID: ' . htmlspecialchars($paymentId)
        ];
        header("Location: booking_status.php");
        exit();
    }

    mysqli_stmt_bind_param(
        $upStmt,
        "iisssii",
        $bookingStatus,
        $transactionStatus,
        $paymentId,
        $now,
        $reason,
        $bookingId,
        $userid
    );

    $execOk = mysqli_stmt_execute($upStmt);
    $affected = mysqli_stmt_affected_rows($upStmt);
    mysqli_stmt_close($upStmt);

    if (!$execOk || $affected !== 1) {
        error_log("Booking " . $bookingId . " update affected rows was not 1 (affected: " . $affected . ")");
    }

    mysqli_commit($conn);

    unset($_SESSION['current_booking_id'], $_SESSION['razorpay_order_id']);

    $_SESSION['completed_booking'] = [
        'id' => $bookingId,
        'service_name' => $booking['service_name'],
        'date' => $booking['date'],
        'location' => $booking['location'],
        'price' => $booking['price'],
        'transaction_id' => $paymentId
    ];

    $_SESSION['booking_notice'] = [
        'type' => 'success',
        'title' => 'Booking Successfully Completed',
        'message' => 'Thank you! Your decoration booking is confirmed and payment has been captured.',
        'booking_id' => $bookingId,
        'transaction_id' => $paymentId
    ];

    header("Location: order_completed.php");
    exit();

} elseif ($paymentStatus === 'authorized') {
    // Authorized but not captured: keep as pending
    $bookingStatus = 0;       // Initiated / Pending
    $transactionStatus = 0;   // Pending
    $reason = 'Payment authorized, awaiting capture';

    $upStmt = mysqli_prepare(
        $conn,
        "UPDATE booking 
         SET status = ?, transaction_status = ?, transaction_id = ?, transaction_date = ?, failure_reason = ? 
         WHERE id = ? AND user_id = ?"
    );

    if ($upStmt) {
        mysqli_stmt_bind_param($upStmt, "iisssii", $bookingStatus, $transactionStatus, $paymentId, $now, $reason, $bookingId, $userid);
        mysqli_stmt_execute($upStmt);
        mysqli_stmt_close($upStmt);
    }

    mysqli_commit($conn);

    $_SESSION['booking_notice'] = [
        'type' => 'pending',
        'title' => 'Payment Authorized (Pending Capture)',
        'message' => 'Your payment of INR ' . number_format($booking['price'], 2) . ' is authorized and pending final capture. Your booking will be confirmed upon capture.',
        'booking_id' => $bookingId,
        'transaction_id' => $paymentId
    ];

    header("Location: booking_status.php");
    exit();

} elseif ($paymentStatus === 'failed') {
    $bookingStatus = 2;       // Failed
    $transactionStatus = 2;   // Failed
    $reason = 'Payment failed on Razorpay';

    $upStmt = mysqli_prepare(
        $conn,
        "UPDATE booking 
         SET status = ?, transaction_status = ?, transaction_id = ?, transaction_date = ?, failure_reason = ? 
         WHERE id = ? AND user_id = ?"
    );

    if ($upStmt) {
        mysqli_stmt_bind_param($upStmt, "iisssii", $bookingStatus, $transactionStatus, $paymentId, $now, $reason, $bookingId, $userid);
        mysqli_stmt_execute($upStmt);
        mysqli_stmt_close($upStmt);
    }

    mysqli_commit($conn);
    unset($_SESSION['current_booking_id'], $_SESSION['razorpay_order_id']);

    $_SESSION['booking_notice'] = [
        'type' => 'failed',
        'title' => 'Payment Failed',
        'message' => 'The payment transaction was declined or failed. Please try booking again with an alternate payment method.',
        'booking_id' => $bookingId,
        'transaction_id' => $paymentId
    ];

    header("Location: booking_status.php");
    exit();

} elseif ($paymentStatus === 'refunded') {
    $bookingStatus = 2;       // Cancelled / Refunded
    $transactionStatus = 2;   // Refunded
    $reason = 'Payment refunded on Razorpay';

    $upStmt = mysqli_prepare(
        $conn,
        "UPDATE booking 
         SET status = ?, transaction_status = ?, transaction_id = ?, transaction_date = ?, failure_reason = ? 
         WHERE id = ? AND user_id = ?"
    );

    if ($upStmt) {
        mysqli_stmt_bind_param($upStmt, "iisssii", $bookingStatus, $transactionStatus, $paymentId, $now, $reason, $bookingId, $userid);
        mysqli_stmt_execute($upStmt);
        mysqli_stmt_close($upStmt);
    }

    mysqli_commit($conn);
    unset($_SESSION['current_booking_id'], $_SESSION['razorpay_order_id']);

    $_SESSION['booking_notice'] = [
        'type' => 'cancelled',
        'title' => 'Payment Refunded',
        'message' => 'This payment transaction has been refunded.',
        'booking_id' => $bookingId,
        'transaction_id' => $paymentId
    ];

    header("Location: booking_status.php");
    exit();

} else {
    // Created or other unconfirmed states
    $bookingStatus = 0;
    $transactionStatus = 0;
    $reason = 'Payment state: ' . substr($paymentStatus, 0, 50);

    $upStmt = mysqli_prepare(
        $conn,
        "UPDATE booking 
         SET status = ?, transaction_status = ?, transaction_id = ?, transaction_date = ?, failure_reason = ? 
         WHERE id = ? AND user_id = ?"
    );

    if ($upStmt) {
        mysqli_stmt_bind_param($upStmt, "iisssii", $bookingStatus, $transactionStatus, $paymentId, $now, $reason, $bookingId, $userid);
        mysqli_stmt_execute($upStmt);
        mysqli_stmt_close($upStmt);
    }

    mysqli_commit($conn);

    $_SESSION['booking_notice'] = [
        'type' => 'pending',
        'title' => 'Payment Pending',
        'message' => 'Your payment status is currently ' . htmlspecialchars($paymentStatus) . '. Please check back shortly.',
        'booking_id' => $bookingId,
        'transaction_id' => $paymentId
    ];

    header("Location: booking_status.php");
    exit();
}
?>
