<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/admin/config.php';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['userid']) && filter_var($_SESSION['userid'], FILTER_VALIDATE_INT) && (int)$_SESSION['userid'] > 0;
$userid = $isLoggedIn ? (int)$_SESSION['userid'] : 0;

// 1. Try to read flash notice from session
$notice = $_SESSION['booking_notice'] ?? null;
unset($_SESSION['booking_notice']);

// Fallback to legacy $_SESSION['booking_success'] if present
if (!$notice && isset($_SESSION['booking_success'])) {
    $legacyMsg = (string)$_SESSION['booking_success'];
    unset($_SESSION['booking_success']);

    $legacyType = 'info';
    $legacyTitle = 'Booking Notice';
    if (stripos($legacyMsg, 'successful') !== false || stripos($legacyMsg, 'confirmed') !== false) {
        $legacyType = 'success';
        $legacyTitle = 'Booking Confirmed';
    } elseif (stripos($legacyMsg, 'cancel') !== false) {
        $legacyType = 'cancelled';
        $legacyTitle = 'Payment Cancelled';
    } elseif (stripos($legacyMsg, 'fail') !== false) {
        $legacyType = 'failed';
        $legacyTitle = 'Payment Failed';
    }

    $notice = [
        'type' => $legacyType,
        'title' => $legacyTitle,
        'message' => $legacyMsg
    ];
}

// 2. If no flash notice, look up user's most recent booking from database
if (!$notice && $isLoggedIn) {
    $stmt = mysqli_prepare(
        $conn,
        "SELECT id, service_name, date, location, price, status, transaction_status, 
                transaction_id, failure_reason 
         FROM booking 
         WHERE user_id = ? 
         ORDER BY id DESC 
         LIMIT 1"
    );

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $userid);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $recent = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);

        if ($recent) {
            $bStatus = (int)$recent['status'];
            $tStatus = (int)$recent['transaction_status'];
            $reason = (string)($recent['failure_reason'] ?? '');

            if ($bStatus === 1 && $tStatus === 1) {
                $notice = [
                    'type' => 'success',
                    'title' => 'Booking Successfully Completed',
                    'message' => 'Your decoration booking for ' . $recent['service_name'] . ' is confirmed and payment was received.',
                    'booking_id' => $recent['id'],
                    'service_name' => $recent['service_name'],
                    'date' => $recent['date'],
                    'price' => $recent['price'],
                    'transaction_id' => $recent['transaction_id']
                ];
            } elseif ($bStatus === 0 && $tStatus === 0) {
                $isAuth = (stripos($reason, 'authorized') !== false);
                $notice = [
                    'type' => 'pending',
                    'title' => $isAuth ? 'Payment Pending Capture' : 'Booking Initiated - Payment Pending',
                    'message' => $isAuth 
                        ? 'Your payment is authorized and awaiting final confirmation.'
                        : 'Your booking has been initiated. If you have not completed payment, please try again.',
                    'booking_id' => $recent['id'],
                    'service_name' => $recent['service_name'],
                    'date' => $recent['date'],
                    'price' => $recent['price']
                ];
            } elseif ($bStatus === 2) {
                if (stripos($reason, 'cancel') !== false || stripos($reason, 'close') !== false) {
                    $notice = [
                        'type' => 'cancelled',
                        'title' => 'Payment Cancelled',
                        'message' => 'The payment for this booking was cancelled by customer.',
                        'booking_id' => $recent['id'],
                        'service_name' => $recent['service_name'],
                        'date' => $recent['date']
                    ];
                } elseif (stripos($reason, 'reject') !== false) {
                    $notice = [
                        'type' => 'rejected',
                        'title' => 'Booking Rejected',
                        'message' => 'This booking request was declined or rejected.',
                        'booking_id' => $recent['id'],
                        'service_name' => $recent['service_name'],
                        'date' => $recent['date']
                    ];
                } elseif (stripos($reason, 'verification') !== false || stripos($reason, 'signature') !== false) {
                    $notice = [
                        'type' => 'verification_failed',
                        'title' => 'Payment Verification Failed',
                        'message' => 'Payment signature verification could not be validated.',
                        'booking_id' => $recent['id'],
                        'service_name' => $recent['service_name'],
                        'date' => $recent['date']
                    ];
                } else {
                    $notice = [
                        'type' => 'failed',
                        'title' => 'Payment Failed',
                        'message' => 'The payment transaction for this booking failed. Reason: ' . ($reason ?: 'Unknown error') . '.',
                        'booking_id' => $recent['id'],
                        'service_name' => $recent['service_name'],
                        'date' => $recent['date']
                    ];
                }
            }
        }
    }
}

// 3. Fallback if still no notice
if (!$notice) {
    $notice = [
        'type' => 'info',
        'title' => 'Booking Notice',
        'message' => 'No active booking transaction at this moment.'
    ];
}

// 4. Styling configuration for each state
$type = $notice['type'] ?? 'info';
$title = $notice['title'] ?? 'Booking Notice';
$message = $notice['message'] ?? '';
$bookingId = $notice['booking_id'] ?? null;
$serviceName = $notice['service_name'] ?? null;
$eventDate = $notice['date'] ?? null;
$transactionId = $notice['transaction_id'] ?? null;

switch ($type) {
    case 'success':
        $iconClass = 'fa-check';
        $iconBg = 'background: #28a745; color: #ffffff;';
        $badgeClass = 'badge badge-success';
        break;
    case 'pending':
        $iconClass = 'fa-clock-o';
        $iconBg = 'background: #ffc107; color: #212529;';
        $badgeClass = 'badge badge-warning text-dark';
        break;
    case 'cancelled':
        $iconClass = 'fa-ban';
        $iconBg = 'background: #6c757d; color: #ffffff;';
        $badgeClass = 'badge badge-secondary';
        break;
    case 'rejected':
    case 'failed':
    case 'verification_failed':
    case 'error':
        $iconClass = 'fa-times';
        $iconBg = 'background: #dc3545; color: #ffffff;';
        $badgeClass = 'badge badge-danger';
        break;
    default:
        $iconClass = 'fa-info';
        $iconBg = 'background: #17a2b8; color: #ffffff;';
        $badgeClass = 'badge badge-info';
        break;
}

include 'header.php';
include 'menu.php';
?>

<section class="section" style="min-height: 550px; padding: 60px 0;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6 text-center">
                <div class="card border-0 shadow-lg p-4 p-md-5 rounded-lg" style="border-radius: 16px;">
                    
                    <!-- Status Icon -->
                    <div class="mb-4">
                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center" 
                             style="width: 80px; height: 80px; font-size: 38px; <?php echo $iconBg; ?>">
                            <i class="fa <?php echo $iconClass; ?>"></i>
                        </div>
                    </div>

                    <!-- Title & Badge -->
                    <div class="mb-2">
                        <span class="<?php echo $badgeClass; ?> px-3 py-1 font-weight-normal text-uppercase" style="letter-spacing: 0.5px;">
                            <?php echo htmlspecialchars($title); ?>
                        </span>
                    </div>

                    <h3 class="font-weight-bold text-dark mt-2 mb-3"><?php echo htmlspecialchars($title); ?></h3>
                    <p class="text-muted lead mb-4" style="font-size: 1.05rem;"><?php echo htmlspecialchars($message); ?></p>

                    <!-- Optional Booking Details Box -->
                    <?php if (!empty($bookingId) || !empty($serviceName)) { ?>
                    <div class="bg-light p-3 rounded mb-4 text-left border small">
                        <?php if (!empty($bookingId)) { ?>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Booking Reference:</span>
                            <span class="font-weight-bold text-dark">#BK-<?php echo htmlspecialchars((string)$bookingId); ?></span>
                        </div>
                        <?php } ?>

                        <?php if (!empty($serviceName)) { ?>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Service Package:</span>
                            <span class="font-weight-bold text-dark"><?php echo htmlspecialchars($serviceName); ?></span>
                        </div>
                        <?php } ?>

                        <?php if (!empty($eventDate)) { ?>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Event Date:</span>
                            <span class="font-weight-bold text-dark"><?php echo date('d M Y', strtotime($eventDate)); ?></span>
                        </div>
                        <?php } ?>

                        <?php if (!empty($transactionId) && $transactionId !== '0') { ?>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Payment ID:</span>
                            <span class="font-weight-bold text-monospace text-dark"><?php echo htmlspecialchars($transactionId); ?></span>
                        </div>
                        <?php } ?>
                    </div>
                    <?php } ?>

                    <!-- Action Navigation Buttons -->
                    <div class="d-flex flex-column flex-sm-row justify-content-center gap-2">
                        <a href="mystatus.php" class="btn btn-main-md m-1">
                            <i class="fa fa-list-alt mr-2"></i> View Booking History
                        </a>
                        <a href="services.php" class="btn btn-outline-secondary m-1 px-4 py-2" style="border-radius: 6px;">
                            <i class="fa fa-arrow-left mr-2"></i> Back to Services
                        </a>
                    </div>

                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'footer.php'; ?>
