<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/menu.php';

// 1. Handle Admin Status Update via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_booking_status') {
    $postedCsrf = $_POST['csrf_token'] ?? '';
    if (!verify_admin_csrf($postedCsrf)) {
        $_SESSION['error'] = "Security token mismatch. Please try again.";
        header("Location: view_booking.php");
        exit();
    }

    $bId = filter_var($_POST['booking_id'] ?? null, FILTER_VALIDATE_INT);
    $newStatus = filter_var($_POST['new_status'] ?? null, FILTER_VALIDATE_INT);
    $adminNotes = trim($_POST['admin_notes'] ?? '');

    if (!$bId || $bId <= 0 || ($newStatus !== 1 && $newStatus !== 2)) {
        $_SESSION['error'] = "Invalid booking status parameters submitted.";
        header("Location: view_booking.php");
        exit();
    }

    // Begin Transaction to prevent race conditions
    mysqli_begin_transaction($conn);

    $fetchStmt = mysqli_prepare(
        $conn,
        "SELECT id, service_name, date, status, transaction_status, transaction_id, failure_reason 
         FROM booking 
         WHERE id = ? 
         FOR UPDATE"
    );

    if (!$fetchStmt) {
        mysqli_rollback($conn);
        $_SESSION['error'] = "Database query error: " . mysqli_error($conn);
        header("Location: view_booking.php");
        exit();
    }

    mysqli_stmt_bind_param($fetchStmt, "i", $bId);
    mysqli_stmt_execute($fetchStmt);
    $bRes = mysqli_stmt_get_result($fetchStmt);
    $currBooking = mysqli_fetch_assoc($bRes);
    mysqli_stmt_close($fetchStmt);

    if (!$currBooking) {
        mysqli_rollback($conn);
        $_SESSION['error'] = "Booking record not found.";
        header("Location: view_booking.php");
        exit();
    }

    $eventDate = $currBooking['date'];

    if ($newStatus === 1) {
        // Approving/Confirming booking:
        // Check whether this event date is already reserved by another confirmed booking
        $conflictStmt = mysqli_prepare(
            $conn,
            "SELECT id, service_name FROM booking WHERE date = ? AND status = 1 AND id != ? LIMIT 1"
        );
        mysqli_stmt_bind_param($conflictStmt, "si", $eventDate, $bId);
        mysqli_stmt_execute($conflictStmt);
        $confRes = mysqli_stmt_get_result($conflictStmt);
        $conflict = mysqli_fetch_assoc($confRes);
        mysqli_stmt_close($conflictStmt);

        if ($conflict) {
            mysqli_rollback($conn);
            $_SESSION['error'] = "Cannot confirm booking: Event date " . date('d M Y', strtotime($eventDate)) . " is already booked by Booking #BK-" . $conflict['id'] . " (" . $conflict['service_name'] . ").";
            header("Location: view_booking.php");
            exit();
        }

        // Safe Confirmation
        $reason = !empty($adminNotes) ? substr($adminNotes, 0, 200) : "Confirmed by administrator";
        $upStmt = mysqli_prepare($conn, "UPDATE booking SET status = 1, failure_reason = ? WHERE id = ?");
        mysqli_stmt_bind_param($upStmt, "si", $reason, $bId);
        mysqli_stmt_execute($upStmt);
        mysqli_stmt_close($upStmt);

        mysqli_commit($conn);
        $_SESSION['success'] = "Booking #BK-$bId has been confirmed successfully.";
        header("Location: view_booking.php");
        exit();

    } elseif ($newStatus === 2) {
        // Rejecting or Cancelling
        $reason = !empty($adminNotes) ? substr($adminNotes, 0, 200) : "Booking rejected/cancelled by administrator";
        $upStmt = mysqli_prepare($conn, "UPDATE booking SET status = 2, failure_reason = ? WHERE id = ?");
        mysqli_stmt_bind_param($upStmt, "si", $reason, $bId);
        mysqli_stmt_execute($upStmt);
        mysqli_stmt_close($upStmt);

        mysqli_commit($conn);
        $_SESSION['success'] = "Booking #BK-$bId status updated to Rejected/Cancelled.";
        header("Location: view_booking.php");
        exit();
    }
}

// 2. Fetch All Real Bookings from Database
$query = mysqli_query($conn, "SELECT * FROM booking ORDER BY id DESC");
$csrfToken = admin_csrf_token();
?>

<!-- Page Breadcrumb Header -->
<div class="page-breadcrumb-header">
    <div>
        <h1 class="page-title-text">Event Bookings Management</h1>
        <div class="page-subtitle-text">Monitor customer decoration reservations, update status, and inspect transaction verification.</div>
    </div>
    <div>
        <a href="view_payments.php" class="btn-secondary-custom">
            <i class="fa fa-credit-card"></i> View Payment Ledger
        </a>
    </div>
</div>

<!-- Notifications / Alerts -->
<?php if (isset($_SESSION['success'])) { ?>
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4 p-3" role="alert">
        <div class="d-flex align-items-center gap-2">
            <i class="fa fa-check-circle fs-5 text-success"></i>
            <div class="fw-semibold"><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php } elseif (isset($_SESSION['error'])) { ?>
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3 mb-4 p-3" role="alert">
        <div class="d-flex align-items-center gap-2">
            <i class="fa fa-exclamation-circle fs-5 text-danger"></i>
            <div class="fw-semibold"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php } ?>

<!-- Bookings Table Card -->
<div class="custom-card">
    <div class="custom-card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title-custom"><i class="fa fa-calendar-check-o text-primary me-2"></i> All Customer Reservations</h5>
        <span class="badge bg-primary-subtle text-primary fw-bold">Live Data</span>
    </div>
    <div class="custom-card-body p-3">
        <div class="table-responsive-custom">
            <table id="bookingTable" class="table table-custom align-middle">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 70px;">Ref ID</th>
                        <th>Service Package</th>
                        <th>Customer Contact</th>
                        <th>Event Venue</th>
                        <th>Event Date</th>
                        <th>Amount</th>
                        <th class="text-center">Booking Status</th>
                        <th class="text-center">Payment</th>
                        <th class="text-center" style="width: 140px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                if ($query && mysqli_num_rows($query) > 0) {
                    while ($row = mysqli_fetch_assoc($query)) {
                        $st = intval($row['status']);
                        $txnSt = intval($row['transaction_status'] ?? 0);
                        $reason = strval($row['failure_reason'] ?? '');
                        $txnId = trim(strval($row['transaction_id'] ?? ''));
                        $hasTxnId = (!empty($txnId) && $txnId !== '0' && strtolower($txnId) !== 'null');

                        // Booking Status Badge
                        if ($st === 1) {
                            $status_badge = '<span class="badge-custom badge-success-custom"><i class="fa fa-check-circle"></i> Confirmed</span>';
                        } elseif ($st === 0) {
                            $status_badge = '<span class="badge-custom badge-warning-custom"><i class="fa fa-clock-o"></i> Initiated</span>';
                        } else {
                            if (stripos($reason, 'cancel') !== false || stripos($reason, 'close') !== false) {
                                $status_badge = '<span class="badge-custom badge-secondary-custom"><i class="fa fa-ban"></i> Cancelled</span>';
                            } elseif (stripos($reason, 'reject') !== false) {
                                $status_badge = '<span class="badge-custom badge-danger-custom"><i class="fa fa-times-circle"></i> Rejected</span>';
                            } else {
                                $status_badge = '<span class="badge-custom badge-danger-custom"><i class="fa fa-exclamation-triangle"></i> Failed</span>';
                            }
                        }

                        // Payment Status Badge
                        if ($txnSt === 1) {
                            $payment_badge = '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="fa fa-check"></i> Captured</span>';
                        } elseif ($txnSt === 0) {
                            $payment_badge = '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1"><i class="fa fa-clock-o"></i> Pending</span>';
                        } else {
                            if (stripos($reason, 'cancel') !== false || stripos($reason, 'close') !== false) {
                                $payment_badge = '<span class="badge bg-secondary-subtle text-secondary border px-2 py-1">Cancelled</span>';
                            } else {
                                $payment_badge = '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">Failed</span>';
                            }
                        }

                        // JSON Payload for modal view
                        $bookingDataJson = htmlspecialchars(json_encode([
                            'id' => $row['id'],
                            'service_name' => $row['service_name'],
                            'username' => $row['username'],
                            'email' => $row['email'],
                            'contact' => $row['contact'],
                            'location' => $row['location'],
                            'date' => !empty($row['date']) ? date('d M Y', strtotime($row['date'])) : 'N/A',
                            'price' => number_format(floatval($row['price']), 2),
                            'status' => $st,
                            'status_text' => ($st === 1 ? 'Confirmed' : ($st === 0 ? 'Initiated' : 'Rejected / Cancelled')),
                            'transaction_id' => $hasTxnId ? $txnId : 'N/A',
                            'razorpay_order_id' => $row['razorpay_order_id'] ?? 'N/A',
                            'transaction_date' => $row['transaction_date'] ?? 'N/A',
                            'transaction_status_text' => ($txnSt === 1 ? 'Captured (Successful)' : ($txnSt === 0 ? 'Pending' : 'Failed / Cancelled')),
                            'failure_reason' => $reason
                        ]), ENT_QUOTES, 'UTF-8');
                ?>
                    <tr>
                        <td class="text-center fw-bold">
                            <span class="badge bg-light text-dark border">#BK-<?php echo $row['id']; ?></span>
                        </td>
                        <td>
                            <div class="fw-bold text-dark fs-6"><?php echo htmlspecialchars($row['service_name']); ?></div>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark"><?php echo htmlspecialchars($row['username']); ?></div>
                            <div class="text-muted small"><i class="fa fa-envelope-o me-1"></i><?php echo htmlspecialchars($row['email']); ?></div>
                            <div class="text-muted small"><i class="fa fa-phone me-1"></i><?php echo htmlspecialchars($row['contact']); ?></div>
                        </td>
                        <td>
                            <div class="text-dark small text-truncate" style="max-width: 180px;" title="<?php echo htmlspecialchars($row['location']); ?>">
                                <i class="fa fa-map-marker text-danger me-1"></i><?php echo htmlspecialchars($row['location']); ?>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border px-2 py-1">
                                <i class="fa fa-calendar text-primary me-1"></i><?php echo !empty($row['date']) ? date('d M Y', strtotime($row['date'])) : 'N/A'; ?>
                            </span>
                        </td>
                        <td>
                            <strong class="text-dark">&#8377;<?php echo number_format(floatval($row['price']), 2); ?></strong>
                        </td>
                        <td class="text-center">
                            <?php echo $status_badge; ?>
                        </td>
                        <td class="text-center">
                            <?php echo $payment_badge; ?>
                        </td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-1">
                                <!-- View Details Modal Trigger -->
                                <button type="button" class="btn btn-sm btn-outline-info rounded-pill px-2" 
                                        onclick="viewBookingDetails(<?php echo $bookingDataJson; ?>)" title="View Full Details">
                                    <i class="fa fa-eye"></i>
                                </button>

                                <!-- Status Action Dropdown -->
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary rounded-pill px-2" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Change Status">
                                        <i class="fa fa-cog"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
                                        <li class="dropdown-header small">Status Action</li>
                                        <?php if ($st !== 1) { ?>
                                        <li>
                                            <a class="dropdown-item text-success small" href="javascript:void(0)" 
                                               onclick="openStatusModal(<?php echo $row['id']; ?>, 1, 'Confirm Reservation for Booking #BK-<?php echo $row['id']; ?>')">
                                                <i class="fa fa-check-circle me-1"></i> Approve & Confirm
                                            </a>
                                        </li>
                                        <?php } ?>
                                        <?php if ($st !== 2) { ?>
                                        <li>
                                            <a class="dropdown-item text-danger small" href="javascript:void(0)" 
                                               onclick="openStatusModal(<?php echo $row['id']; ?>, 2, 'Reject/Cancel Booking #BK-<?php echo $row['id']; ?>')">
                                                <i class="fa fa-times-circle me-1"></i> Reject / Cancel
                                            </a>
                                        </li>
                                        <?php } ?>
                                    </ul>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php
                    }
                } else {
                ?>
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">No booking reservations found in the database.</td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 1. View Booking Details Modal -->
<div class="modal fade" id="bookingDetailModal" tabindex="-1" aria-labelledby="bookingDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-light border-bottom">
                <h5 class="modal-title font-weight-bold text-dark" id="bookingDetailModalLabel">
                    <i class="fa fa-file-text-o text-primary me-2"></i> Reservation Details: <span id="modalRefId"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="text-muted small fw-bold">Customer Full Name</label>
                        <div class="fw-bold fs-6 text-dark" id="modalCustomerName"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small fw-bold">Customer Email</label>
                        <div class="text-dark" id="modalCustomerEmail"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small fw-bold">Contact Phone Number</label>
                        <div class="text-dark" id="modalCustomerPhone"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small fw-bold">Service Package</label>
                        <div class="fw-bold text-primary" id="modalServiceName"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small fw-bold">Reserved Event Date</label>
                        <div class="fw-bold text-dark fs-6" id="modalEventDate"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small fw-bold">Package Price</label>
                        <div class="fw-bold text-success fs-5">&#8377;<span id="modalPrice"></span></div>
                    </div>
                    <div class="col-12">
                        <label class="text-muted small fw-bold">Event Venue / Location Address</label>
                        <div class="p-2.5 rounded bg-light border text-dark" id="modalLocation"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small fw-bold">Booking Status</label>
                        <div id="modalBookingStatus" class="fw-bold"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small fw-bold">Payment Gateway Status</label>
                        <div id="modalPaymentStatus" class="fw-bold"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small fw-bold">Razorpay Transaction ID</label>
                        <div class="text-monospace text-dark fw-bold" id="modalTxnId"></div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small fw-bold">Razorpay Order ID</label>
                        <div class="text-monospace text-dark" id="modalOrderId"></div>
                    </div>
                    <div class="col-12">
                        <label class="text-muted small fw-bold">System Status Notes / Failure Reason</label>
                        <div class="p-2 rounded bg-light border text-muted small" id="modalNotes"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top bg-light">
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- 2. Update Status Confirmation Modal -->
<div class="modal fade" id="statusActionModal" tabindex="-1" aria-labelledby="statusActionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form method="POST" action="view_booking.php">
                <input type="hidden" name="action" value="update_booking_status">
                <input type="hidden" name="csrf_token" value="<?php echo admin_escape($csrfToken); ?>">
                <input type="hidden" name="booking_id" id="statusActionBookingId" value="">
                <input type="hidden" name="new_status" id="statusActionNewStatus" value="">

                <div class="modal-header border-bottom">
                    <h5 class="modal-title font-weight-bold" id="statusActionModalLabel">Confirm Status Change</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="mb-3 lead fs-6 text-dark" id="statusActionPromptText"></p>
                    <div class="mb-3">
                        <label class="form-label text-muted small fw-bold">Reason / Administrative Notes (Optional)</label>
                        <textarea name="admin_notes" class="form-control" rows="2" placeholder="e.g. Verified date availability and approved."></textarea>
                    </div>
                    <div class="alert alert-warning py-2 small mb-0">
                        <i class="fa fa-info-circle me-1"></i> Date collision checks are enforced automatically before approving any reservation.
                    </div>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-custom" id="statusActionSubmitBtn">Confirm Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    $('#bookingTable').DataTable({
        "pageLength": 10,
        "order": [[ 0, "desc" ]],
        "language": {
            "search": "_INPUT_",
            "searchPlaceholder": "Search bookings..."
        }
    });
});

function viewBookingDetails(data) {
    document.getElementById('modalRefId').textContent = '#BK-' + data.id;
    document.getElementById('modalCustomerName').textContent = data.username;
    document.getElementById('modalCustomerEmail').textContent = data.email;
    document.getElementById('modalCustomerPhone').textContent = data.contact;
    document.getElementById('modalServiceName').textContent = data.service_name;
    document.getElementById('modalEventDate').textContent = data.date;
    document.getElementById('modalPrice').textContent = data.price;
    document.getElementById('modalLocation').textContent = data.location;
    document.getElementById('modalBookingStatus').textContent = data.status_text;
    document.getElementById('modalPaymentStatus').textContent = data.transaction_status_text;
    document.getElementById('modalTxnId').textContent = data.transaction_id;
    document.getElementById('modalOrderId').textContent = data.razorpay_order_id;
    document.getElementById('modalNotes').textContent = data.failure_reason || 'None';

    var myModal = new bootstrap.Modal(document.getElementById('bookingDetailModal'));
    myModal.show();
}

function openStatusModal(bookingId, newStatus, promptTitle) {
    document.getElementById('statusActionBookingId').value = bookingId;
    document.getElementById('statusActionNewStatus').value = newStatus;
    document.getElementById('statusActionPromptText').textContent = "Are you sure you want to: " + promptTitle + "?";
    
    var btn = document.getElementById('statusActionSubmitBtn');
    if (newStatus === 1) {
        btn.className = "btn btn-success px-3 rounded-pill";
        btn.textContent = "Approve & Confirm";
    } else {
        btn.className = "btn btn-danger px-3 rounded-pill";
        btn.textContent = "Reject / Cancel";
    }

    var myModal = new bootstrap.Modal(document.getElementById('statusActionModal'));
    myModal.show();
}
</script>

<?php include 'footer.php'; ?>
