<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/menu.php';

// 1. Fetch Real Transaction Statistics from Database
$res_captured = mysqli_query($conn, "SELECT SUM(price) as rev, COUNT(*) as cnt FROM booking WHERE status = 1 AND transaction_status = 1");
$capData = mysqli_fetch_assoc($res_captured);
$capturedRevenue = floatval($capData['rev'] ?? 0);
$capturedCount = intval($capData['cnt'] ?? 0);

$res_pending = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM booking WHERE transaction_status = 0 AND razorpay_order_id IS NOT NULL");
$pendingCount = intval(mysqli_fetch_assoc($res_pending)['cnt'] ?? 0);

$res_failed = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM booking WHERE transaction_status = 2");
$failedCount = intval(mysqli_fetch_assoc($res_failed)['cnt'] ?? 0);

$res_total = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM booking WHERE (transaction_id IS NOT NULL AND transaction_id != '' AND transaction_id != '0') OR razorpay_order_id IS NOT NULL");
$totalTxnCount = intval(mysqli_fetch_assoc($res_total)['cnt'] ?? 0);

// 2. Fetch Actual Transaction Ledger Records
$query = mysqli_query(
    $conn,
    "SELECT id, service_name, username, email, contact, price, transaction_id, razorpay_order_id, 
            transaction_date, transaction_status, status, failure_reason 
     FROM booking 
     WHERE (transaction_id IS NOT NULL AND transaction_id != '' AND transaction_id != '0') 
        OR razorpay_order_id IS NOT NULL 
     ORDER BY id DESC"
);
?>

<!-- Page Breadcrumb Header -->
<div class="page-breadcrumb-header">
    <div>
        <h1 class="page-title-text">Payment & Transaction Ledger</h1>
        <div class="page-subtitle-text">Verified gateway records, Razorpay payment captures, order tracking, and financial audit history.</div>
    </div>
    <div>
        <a href="view_booking.php" class="btn-primary-custom">
            <i class="fa fa-calendar-check-o"></i> View Bookings
        </a>
    </div>
</div>

<!-- Financial Summary Metric Cards -->
<div class="row g-3 mb-4">
    <!-- Verified Captured Revenue -->
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-card-header">
                <div class="stat-icon-wrapper stat-icon-blue">
                    <i class="fa fa-inr"></i>
                </div>
                <span class="badge bg-success-subtle text-success fw-bold px-2 py-1 border border-success-subtle">
                    Captured
                </span>
            </div>
            <div class="stat-value">&#8377;<?php echo number_format($capturedRevenue, 2); ?></div>
            <div class="stat-label">Net Captured Revenue</div>
            <div class="stat-footer text-muted">
                <i class="fa fa-check-circle text-success me-1"></i> <?php echo $capturedCount; ?> Successful Payments
            </div>
        </div>
    </div>

    <!-- Total Initiated Transactions -->
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-card-header">
                <div class="stat-icon-wrapper stat-icon-emerald">
                    <i class="fa fa-exchange"></i>
                </div>
                <span class="badge bg-primary-subtle text-primary fw-bold px-2 py-1 border border-primary-subtle">
                    Total
                </span>
            </div>
            <div class="stat-value"><?php echo $totalTxnCount; ?></div>
            <div class="stat-label">Total Payment Requests</div>
            <div class="stat-footer text-muted">
                <i class="fa fa-history text-primary me-1"></i> All recorded gateway sessions
            </div>
        </div>
    </div>

    <!-- Pending / Authorized Transactions -->
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-card-header">
                <div class="stat-icon-wrapper stat-icon-amber">
                    <i class="fa fa-clock-o"></i>
                </div>
                <span class="badge bg-warning-subtle text-warning-emphasis fw-bold px-2 py-1 border border-warning-subtle">
                    Pending
                </span>
            </div>
            <div class="stat-value"><?php echo $pendingCount; ?></div>
            <div class="stat-label">Pending / Incomplete</div>
            <div class="stat-footer text-muted">
                <i class="fa fa-hourglass-half text-warning me-1"></i> Awaiting capture / completion
            </div>
        </div>
    </div>

    <!-- Failed / Cancelled Transactions -->
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-card-header">
                <div class="stat-icon-wrapper stat-icon-rose">
                    <i class="fa fa-ban"></i>
                </div>
                <span class="badge bg-danger-subtle text-danger fw-bold px-2 py-1 border border-danger-subtle">
                    Cancelled/Failed
                </span>
            </div>
            <div class="stat-value"><?php echo $failedCount; ?></div>
            <div class="stat-label">Failed / Cancelled</div>
            <div class="stat-footer text-muted">
                <i class="fa fa-times-circle text-danger me-1"></i> Excluded from revenue calculations
            </div>
        </div>
    </div>
</div>

<!-- Transaction Ledger Card -->
<div class="custom-card">
    <div class="custom-card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title-custom"><i class="fa fa-shield text-primary me-2"></i> Razorpay Payment Gateway Transactions</h5>
        <span class="badge bg-primary-subtle text-primary fw-bold">Live Database Audit</span>
    </div>
    <div class="custom-card-body p-3">
        <div class="table-responsive-custom">
            <table id="paymentsTable" class="table table-custom align-middle">
                <thead>
                    <tr>
                        <th>Payment ID</th>
                        <th>Order ID</th>
                        <th>Booking Ref</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th class="text-center">Gateway Status</th>
                        <th>Date & Time</th>
                        <th>Gateway Notes / Failure Reason</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                if ($query && mysqli_num_rows($query) > 0) {
                    while ($row = mysqli_fetch_assoc($query)) {
                        $tst = intval($row['transaction_status'] ?? 0);
                        $reason = strval($row['failure_reason'] ?? '');
                        $txnId = trim(strval($row['transaction_id'] ?? ''));
                        $hasTxnId = (!empty($txnId) && $txnId !== '0' && strtolower($txnId) !== 'null');
                        $orderId = trim(strval($row['razorpay_order_id'] ?? ''));
                        $hasOrderId = (!empty($orderId) && strtolower($orderId) !== 'null');

                        if ($tst === 1) {
                            $p_badge = '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="fa fa-check-circle me-1"></i> Captured</span>';
                        } elseif ($tst === 0) {
                            $isAuth = (stripos($reason, 'authorized') !== false);
                            $p_badge = $isAuth 
                                ? '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1"><i class="fa fa-shield me-1"></i> Authorized</span>'
                                : '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1"><i class="fa fa-clock-o me-1"></i> Pending</span>';
                        } else {
                            if (stripos($reason, 'refund') !== false) {
                                $p_badge = '<span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1"><i class="fa fa-undo me-1"></i> Refunded</span>';
                            } elseif (stripos($reason, 'cancel') !== false || stripos($reason, 'closed') !== false) {
                                $p_badge = '<span class="badge bg-secondary-subtle text-secondary border px-2 py-1"><i class="fa fa-ban me-1"></i> Cancelled</span>';
                            } else {
                                $p_badge = '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1"><i class="fa fa-times me-1"></i> Failed</span>';
                            }
                        }
                ?>
                    <tr>
                        <td>
                            <?php if ($hasTxnId) { ?>
                                <span class="badge bg-light text-dark border text-monospace font-weight-bold">
                                    <i class="fa fa-credit-card text-primary me-1"></i><?php echo htmlspecialchars($txnId); ?>
                                </span>
                            <?php } else { ?>
                                <span class="text-muted small fw-bold">N/A (Uncompleted)</span>
                            <?php } ?>
                        </td>
                        <td>
                            <?php if ($hasOrderId) { ?>
                                <span class="text-monospace text-dark small fw-semibold"><?php echo htmlspecialchars($orderId); ?></span>
                            <?php } else { ?>
                                <span class="text-muted small">N/A</span>
                            <?php } ?>
                        </td>
                        <td>
                            <a href="view_booking.php" class="fw-bold text-decoration-none">
                                #BK-<?php echo $row['id']; ?>
                            </a>
                            <div class="small text-muted"><?php echo htmlspecialchars($row['service_name']); ?></div>
                        </td>
                        <td>
                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($row['username']); ?></div>
                            <small class="text-muted"><?php echo htmlspecialchars($row['email']); ?></small>
                        </td>
                        <td>
                            <strong class="text-dark fs-6">&#8377;<?php echo number_format(floatval($row['price']), 2); ?></strong>
                        </td>
                        <td class="text-center">
                            <?php echo $p_badge; ?>
                        </td>
                        <td>
                            <small class="text-muted d-block">
                                <i class="fa fa-clock-o me-1"></i><?php echo htmlspecialchars($row['transaction_date'] ?: 'N/A'); ?>
                            </small>
                        </td>
                        <td>
                            <div class="text-muted small" style="max-width: 250px; line-height: 1.4;">
                                <?php echo htmlspecialchars($reason ?: 'None'); ?>
                            </div>
                        </td>
                    </tr>
                <?php
                    }
                } else {
                ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">No gateway transactions recorded in the database yet.</td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    $('#paymentsTable').DataTable({
        "pageLength": 10,
        "order": [[ 6, "desc" ]],
        "language": {
            "search": "_INPUT_",
            "searchPlaceholder": "Search payment ID, order ID, customer..."
        }
    });
});
</script>

<?php include 'footer.php'; ?>
