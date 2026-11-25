<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/admin/config.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/razorpay-php/Razorpay.php';

use Razorpay\Api\Api;

// 1. Verify user authentication
if (
    !isset($_SESSION['userid']) ||
    !filter_var($_SESSION['userid'], FILTER_VALIDATE_INT) ||
    (int)$_SESSION['userid'] <= 0
) {
    $_SESSION['error'] = "Please log in to book a service.";
    header("Location: login.php");
    exit();
}

$userid = (int)$_SESSION['userid'];

// 2. Only POST method allowed
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: services.php");
    exit();
}

// Helper: read POST strings safely
function getPostVal($key) {
    if (!isset($_POST[$key]) || !is_string($_POST[$key])) {
        return '';
    }
    return trim($_POST[$key]);
}

$serviceId = filter_var($_POST['service_id'] ?? null, FILTER_VALIDATE_INT);
$userName = getPostVal('user_name');
$email = getPostVal('email');
$contact = getPostVal('contact');
$location = getPostVal('location');
$bookingDate = getPostVal('date');
$postedToken = getPostVal('csrf_token');

// Validate service ID first so we have a target to redirect to
if (!$serviceId || $serviceId <= 0) {
    $_SESSION['error'] = "Please select a valid service.";
    header("Location: services.php");
    exit();
}

// Preserve submitted form data in session so user doesn't lose inputs if validation fails
$formInputs = [
    'service_id' => $serviceId,
    'user_name' => $userName,
    'email' => $email,
    'contact' => $contact,
    'location' => $location,
    'date' => $bookingDate
];

// Helper to redirect back to booking page with error
function redirectBackWithError($serviceId, $errorKey, $errorMessage, $inputs) {
    $_SESSION['booking_form_data'] = $inputs;
    $_SESSION[$errorKey] = $errorMessage;
    header("Location: booking.php?services_id=" . (int)$serviceId);
    exit();
}

// 3. CSRF Validation
$sessionToken = $_SESSION['booking_csrf_token'] ?? '';
if (
    !is_string($sessionToken) ||
    $sessionToken === '' ||
    $postedToken === '' ||
    !hash_equals($sessionToken, $postedToken)
) {
    redirectBackWithError($serviceId, 'booking_error', "Invalid session security token. Please try submitting again.", $formInputs);
}

// 4. Input Validations
if (!preg_match("/^[A-Za-z ]{2,100}$/", $userName)) {
    redirectBackWithError($serviceId, 'booking_error', "Please enter a valid full name (letters and spaces only).", $formInputs);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
    redirectBackWithError($serviceId, 'booking_error', "Please enter a valid email address.", $formInputs);
}

if (!preg_match('/^[0-9]{10}$/', $contact)) {
    redirectBackWithError($serviceId, 'booking_error', "Please enter a valid 10-digit mobile number.", $formInputs);
}

if (strlen($location) < 5 || strlen($location) > 255) {
    redirectBackWithError($serviceId, 'booking_error', "Please enter a valid event location (minimum 5 characters).", $formInputs);
}

// 5. Date format and past-date validation
$dateObject = DateTimeImmutable::createFromFormat('!Y-m-d', $bookingDate);
$dateErrors = DateTimeImmutable::getLastErrors();
$today = date('Y-m-d');

if (
    !$dateObject ||
    ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0)) ||
    $dateObject->format('Y-m-d') !== $bookingDate ||
    $bookingDate < $today
) {
    redirectBackWithError($serviceId, 'booking_error', "Please select a valid future event date.", $formInputs);
}

// 6. Fetch authoritative service details from database (never trust client price/name)
$svcStmt = mysqli_prepare(
    $conn,
    "SELECT id, name, price FROM services WHERE id = ? LIMIT 1"
);

if (!$svcStmt) {
    error_log("Service lookup prepare error: " . mysqli_error($conn));
    redirectBackWithError($serviceId, 'booking_error', "Unable to verify service details. Please try again.", $formInputs);
}

mysqli_stmt_bind_param($svcStmt, "i", $serviceId);
mysqli_stmt_execute($svcStmt);
mysqli_stmt_bind_result($svcStmt, $dbSvcId, $serviceName, $servicePrice);
$serviceFound = mysqli_stmt_fetch($svcStmt);
mysqli_stmt_close($svcStmt);

if (!$serviceFound || !is_numeric($servicePrice) || (float)$servicePrice <= 0) {
    redirectBackWithError($serviceId, 'booking_error', "The selected service is currently unavailable.", $formInputs);
}

$price = (float)$servicePrice;
$amountInPaise = (int)round($price * 100);

if ($amountInPaise <= 0) {
    redirectBackWithError($serviceId, 'booking_error', "Invalid service price calculation.", $formInputs);
}

// 7. Database-level Concurrency Protection & Date Validation
// Start transaction
mysqli_begin_transaction($conn);

try {
    // A. Clean up abandoned pending bookings (> 15 minutes old) so they do NOT permanently block dates
    $expireStmt = mysqli_prepare(
        $conn,
        "UPDATE booking 
         SET status = 2, transaction_status = 2, failure_reason = 'Payment timed out / abandoned' 
         WHERE date = ? AND status = 0 AND transaction_date < DATE_SUB(NOW(), INTERVAL 15 MINUTE)"
    );
    if ($expireStmt) {
        mysqli_stmt_bind_param($expireStmt, "s", $bookingDate);
        mysqli_stmt_execute($expireStmt);
        mysqli_stmt_close($expireStmt);
    }

    // B. Check for existing active bookings with row lock (FOR UPDATE)
    $lockStmt = mysqli_prepare(
        $conn,
        "SELECT id, user_id, status, transaction_date 
         FROM booking 
         WHERE date = ? AND status IN (0, 1) 
         FOR UPDATE"
    );

    if (!$lockStmt) {
        throw new RuntimeException("Database lock error: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($lockStmt, "s", $bookingDate);
    mysqli_stmt_execute($lockStmt);
    $lockRes = mysqli_stmt_get_result($lockStmt);

    $reusableBookingId = null;
    $dateBlocked = false;

    while ($lockedRow = mysqli_fetch_assoc($lockRes)) {
        $rowStatus = (int)$lockedRow['status'];
        $rowUserId = (int)$lockedRow['user_id'];

        if ($rowStatus === 1) {
            // Already confirmed by anyone -> Blocked
            $dateBlocked = true;
            break;
        } elseif ($rowStatus === 0) {
            // Active pending booking
            if ($rowUserId === $userid) {
                // Same user retrying payment -> reuse this booking record to prevent duplicate entries
                $reusableBookingId = (int)$lockedRow['id'];
            } else {
                // Belongs to another user -> Blocked
                $dateBlocked = true;
                break;
            }
        }
    }
    mysqli_stmt_close($lockStmt);

    if ($dateBlocked) {
        // Rollback and notify user
        mysqli_rollback($conn);
        redirectBackWithError(
            $serviceId,
            'date_error',
            "Sorry! This date is already booked. Please select another date.",
            $formInputs
        );
    }

    // Release any previous abandoned pending bookings on DIFFERENT dates for this user
    $relStmt = mysqli_prepare(
        $conn,
        "UPDATE booking 
         SET status = 2, transaction_status = 2, failure_reason = 'Payment cancelled / customer selected different date' 
         WHERE user_id = ? AND status = 0 AND date != ?"
    );
    if ($relStmt) {
        mysqli_stmt_bind_param($relStmt, "is", $userid, $bookingDate);
        mysqli_stmt_execute($relStmt);
        mysqli_stmt_close($relStmt);
    }

    // If no active pending booking was found for reuse, check if user has an unconfirmed/cancelled booking on this date to reuse
    if ($reusableBookingId === null) {
        $checkPrev = mysqli_prepare(
            $conn,
            "SELECT id FROM booking 
             WHERE user_id = ? AND date = ? AND status = 2 
             ORDER BY id DESC LIMIT 1"
        );
        if ($checkPrev) {
            mysqli_stmt_bind_param($checkPrev, "is", $userid, $bookingDate);
            mysqli_stmt_execute($checkPrev);
            $prevRes = mysqli_stmt_get_result($checkPrev);
            if ($pRow = mysqli_fetch_assoc($prevRes)) {
                $reusableBookingId = (int)$pRow['id'];
            }
            mysqli_stmt_close($checkPrev);
        }
    }

    $bookingId = 0;
    $now = date('Y-m-d H:i:s');
    $initBookingStatus = 0;
    $initTxnStatus = 0;
    $initTxnId = "0";
    $initFailureReason = "pending";

    if ($reusableBookingId !== null && $reusableBookingId > 0) {
        // Update existing pending booking for payment retry
        $bookingId = $reusableBookingId;
        $updStmt = mysqli_prepare(
            $conn,
            "UPDATE booking 
             SET service_name = ?, username = ?, email = ?, contact = ?, location = ?, 
                 date = ?, price = ?, status = 0, transaction_id = '0', 
                 transaction_date = ?, transaction_status = 0, failure_reason = 'pending' 
             WHERE id = ? AND user_id = ?"
        );

        if (!$updStmt) {
            throw new RuntimeException("Booking update prepare failed: " . mysqli_error($conn));
        }

        mysqli_stmt_bind_param(
            $updStmt,
            "ssssssdsii",
            $serviceName,
            $userName,
            $email,
            $contact,
            $location,
            $bookingDate,
            $price,
            $now,
            $bookingId,
            $userid
        );

        if (!mysqli_stmt_execute($updStmt)) {
            $err = mysqli_stmt_error($updStmt);
            mysqli_stmt_close($updStmt);
            throw new RuntimeException("Failed to update booking for retry: " . $err);
        }
        mysqli_stmt_close($updStmt);

    } else {
        // Insert new pending booking record
        $insStmt = mysqli_prepare(
            $conn,
            "INSERT INTO booking 
             (service_name, user_id, username, email, contact, location, date, price, status, transaction_id, transaction_date, transaction_status, failure_reason) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        if (!$insStmt) {
            throw new RuntimeException("Booking insert prepare failed: " . mysqli_error($conn));
        }

        mysqli_stmt_bind_param(
            $insStmt,
            "sisssssdissis",
            $serviceName,
            $userid,
            $userName,
            $email,
            $contact,
            $location,
            $bookingDate,
            $price,
            $initBookingStatus,
            $initTxnId,
            $now,
            $initTxnStatus,
            $initFailureReason
        );

        if (!mysqli_stmt_execute($insStmt)) {
            $err = mysqli_stmt_error($insStmt);
            mysqli_stmt_close($insStmt);
            throw new RuntimeException("Failed to insert booking: " . $err);
        }

        $bookingId = mysqli_insert_id($conn);
        mysqli_stmt_close($insStmt);
    }

    if ($bookingId <= 0) {
        throw new RuntimeException("Invalid booking ID generated.");
    }

    // 8. Generate Razorpay Order via SDK
    if (empty($keyId) || empty($keySecret)) {
        throw new RuntimeException("Razorpay configuration keys are missing.");
    }

    $api = new Api($keyId, $keySecret);
    $orderData = [
        'receipt' => 'BK_' . $bookingId,
        'amount' => $amountInPaise,
        'currency' => 'INR',
        'notes' => [
            'booking_id' => (string)$bookingId,
            'user_id' => (string)$userid,
            'service_name' => $serviceName
        ]
    ];

    $razorpayOrder = $api->order->create($orderData);
    $razorpayOrderId = $razorpayOrder['id'] ?? '';

    if (!is_string($razorpayOrderId) || $razorpayOrderId === '') {
        throw new RuntimeException("Razorpay did not return a valid order ID.");
    }

    // Save Razorpay order ID in booking table
    $orderUpStmt = mysqli_prepare(
        $conn,
        "UPDATE booking SET razorpay_order_id = ? WHERE id = ? AND user_id = ?"
    );

    if (!$orderUpStmt) {
        throw new RuntimeException("Order ID update prepare failed: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($orderUpStmt, "sii", $razorpayOrderId, $bookingId, $userid);
    mysqli_stmt_execute($orderUpStmt);
    mysqli_stmt_close($orderUpStmt);

    // Commit database transaction
    mysqli_commit($conn);

    // Save identifiers in session for verify.php
    $_SESSION['current_booking_id'] = $bookingId;
    $_SESSION['razorpay_order_id'] = $razorpayOrderId;

    // Build checkout parameters for manual.php
    $data = [
        "key" => $keyId,
        "amount" => $amountInPaise,
        "currency" => "INR",
        "order_id" => $razorpayOrderId,
        "name" => "Samruddhi Decorators",
        "description" => "Payment for " . $serviceName,
        "prefill" => [
            "name" => $userName,
            "email" => $email,
            "contact" => $contact
        ],
        "order_data" => [
            "booking_id" => $bookingId,
            "service_name" => $serviceName,
            "event_date" => $bookingDate,
            "amount" => number_format($price, 2, '.', '')
        ],
        "theme" => [
            "color" => "#06416c"
        ]
    ];

    $json = json_encode(
        $data,
        JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
    );

    if ($json === false) {
        throw new RuntimeException("JSON encoding failed for checkout data.");
    }

} catch (Throwable $e) {
    error_log("Pay.php transaction error: " . $e->getMessage());
    mysqli_rollback($conn);

    if (isset($bookingId) && $bookingId > 0) {
        $failStmt = mysqli_prepare(
            $conn,
            "UPDATE booking SET status = 2, transaction_status = 2, failure_reason = 'order_creation_failed' WHERE id = ? AND user_id = ? AND status = 0"
        );
        if ($failStmt) {
            mysqli_stmt_bind_param($failStmt, "ii", $bookingId, $userid);
            mysqli_stmt_execute($failStmt);
            mysqli_stmt_close($failStmt);
        }
    }

    unset($_SESSION['current_booking_id'], $_SESSION['razorpay_order_id']);

    redirectBackWithError(
        $serviceId,
        'booking_error',
        "Payment order could not be created. Please try again.",
        $formInputs
    );
}

// 9. Render Razorpay checkout view
include __DIR__ . '/header.php';
include __DIR__ . '/menu.php';

require __DIR__ . '/checkout/manual.php';

include __DIR__ . '/footer.php';
?>
