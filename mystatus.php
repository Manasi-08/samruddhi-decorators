<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/admin/config.php';

if (!isset($_SESSION['userid']) || !filter_var($_SESSION['userid'], FILTER_VALIDATE_INT) || (int)$_SESSION['userid'] <= 0) {
    header("Location: login.php");
    exit();
}

$userid = (int)$_SESSION['userid'];

// Fetch user's own bookings only using prepared statement
$stmt = mysqli_prepare(
    $conn,
    "SELECT id, service_name, user_id, date, location, price, status, 
            transaction_id, transaction_status, failure_reason 
     FROM booking 
     WHERE user_id = ? 
     ORDER BY id DESC"
);

$query = null;
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $userid);
    mysqli_stmt_execute($stmt);
    $query = mysqli_stmt_get_result($stmt);
} else {
    error_log("Failed to prepare bookings query in mystatus.php: " . mysqli_error($conn));
}
?>
<?php include 'header.php';?>
<?php include 'menu.php';?>

<!--================================
=            Page Title            =
=================================-->

<section class="page-title bg-title overlay-dark">
	<div class="container">
		<div class="row">
			<div class="col-12 text-center">
				<div class="title">
					<h3>My Booking History</h3>
				</div>
				<ol class="breadcrumb p-0 m-0">
				  <li class="breadcrumb-item"><a href="index.php">Home</a></li>
				  <li class="breadcrumb-item active">Booking History</li>
				</ol>
			</div>
		</div>
	</div>
</section>

<section class="section">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="card status-card border-0 shadow-sm rounded-lg overflow-hidden">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="m-0 font-weight-bold text-dark">
                            <i class="fa fa-calendar-check-o text-primary mr-2"></i> Your Booking History
                        </h5>
                        <a href="services.php" class="btn btn-main-md btn-sm">
                            <i class="fa fa-plus mr-1"></i> New Booking
                        </a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table id="booking" class="table table-hover align-middle m-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th class="text-center">Booking ID</th>											
                                        <th>Service Name</th>									
                                        <th>Event Date</th>	
                                        <th>Location</th>																															
                                        <th class="text-center">Booking Status</th>
                                        <th>Transaction ID</th>
                                        <th class="text-center">Payment Status</th>
                                        <th class="text-right">Price</th>	
                                     </tr>                                                
                                </thead>
                                <tbody>
                                   <?php
                                        if ($query && mysqli_num_rows($query) > 0) {
                                            while($row = mysqli_fetch_assoc($query)) {
                                                $bookingStatus = (int)$row['status'];
                                                $txnStatus = (int)$row['transaction_status'];
                                                $reason = (string)($row['failure_reason'] ?? '');
                                                $txnId = trim((string)($row['transaction_id'] ?? ''));

                                                // Determine Booking Status Badge
                                                if ($bookingStatus === 1) {
                                                    $bStatusBadge = '<span class="badge badge-success px-3 py-2"><i class="fa fa-check mr-1"></i> Confirmed</span>';
                                                } elseif ($bookingStatus === 0) {
                                                    $bStatusBadge = '<span class="badge badge-warning px-3 py-2 text-dark"><i class="fa fa-clock-o mr-1"></i> Initiated</span>';
                                                } else {
                                                    if (stripos($reason, 'cancel') !== false || stripos($reason, 'closed') !== false) {
                                                        $bStatusBadge = '<span class="badge badge-secondary px-3 py-2"><i class="fa fa-ban mr-1"></i> Cancelled</span>';
                                                    } elseif (stripos($reason, 'reject') !== false) {
                                                        $bStatusBadge = '<span class="badge badge-danger px-3 py-2"><i class="fa fa-times mr-1"></i> Rejected</span>';
                                                    } else {
                                                        $bStatusBadge = '<span class="badge badge-danger px-3 py-2"><i class="fa fa-exclamation-triangle mr-1"></i> Failed</span>';
                                                    }
                                                }

                                                // Determine Payment Status Badge
                                                if ($txnStatus === 1) {
                                                    $pStatusBadge = '<span class="badge badge-pill badge-success"><i class="fa fa-check-circle mr-1"></i> Successful</span>';
                                                } elseif ($txnStatus === 0) {
                                                    $pStatusBadge = '<span class="badge badge-pill badge-warning text-dark"><i class="fa fa-hourglass-half mr-1"></i> Pending</span>';
                                                } else {
                                                    if (stripos($reason, 'cancel') !== false || stripos($reason, 'closed') !== false) {
                                                        $pStatusBadge = '<span class="badge badge-pill badge-secondary">Cancelled</span>';
                                                    } else {
                                                        $pStatusBadge = '<span class="badge badge-pill badge-danger">Failed</span>';
                                                    }
                                                }

                                                // Transaction ID formatting (show N/A only when truly unavailable)
                                                $hasTxnId = (!empty($txnId) && $txnId !== '0' && strtolower($txnId) !== 'null');
                                   ?>
                                             <tr>
                                                <td class="text-center font-weight-bold text-dark">
                                                    <span class="badge badge-light border">#BK-<?php echo (int)$row['id']; ?></span>
                                                </td>
                                                <td class="font-weight-bold text-dark"><?php echo htmlspecialchars($row['service_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td>
                                                    <i class="fa fa-calendar text-muted mr-1"></i>
                                                    <?php echo !empty($row['date']) ? date('d M Y', strtotime($row['date'])) : 'N/A'; ?>
                                                </td>
                                                <td>
                                                    <small class="text-muted d-inline-block" style="max-width: 200px;">
                                                        <i class="fa fa-map-marker text-danger mr-1"></i><?php echo htmlspecialchars($row['location'], ENT_QUOTES, 'UTF-8'); ?>
                                                    </small>
                                                </td>
                                                <td class="text-center">
                                                    <?php echo $bStatusBadge; ?>
                                                </td>
                                                <td>
                                                    <?php if ($hasTxnId) { ?>
                                                        <small class="text-monospace font-weight-bold text-dark"><?php echo htmlspecialchars($txnId, ENT_QUOTES, 'UTF-8'); ?></small>
                                                    <?php } else { ?>
                                                        <small class="text-muted font-weight-bold">N/A</small>
                                                    <?php } ?>
                                                </td>
                                                <td class="text-center">
                                                    <?php echo $pStatusBadge; ?>
                                                </td>
                                                <td class="text-right font-weight-bold text-dark">
                                                    &#8377; <?php echo number_format((float)$row['price'], 2); ?>
                                                </td>
                                            </tr>
                                   <?php 
                                            } 
                                        } else {
                                   ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-5 text-muted">
                                                <i class="fa fa-folder-open-o fa-3x mb-3 d-block text-muted"></i>
                                                <p class="mb-2 lead">No bookings found in your account.</p>
                                                <a href="services.php" class="btn btn-main-md btn-sm">Explore Our Services</a>
                                            </td>
                                        </tr>
                                   <?php } ?>  
                                </tbody>
                            </table>	
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php 
if ($stmt) {
    mysqli_stmt_close($stmt);
}
include 'footer.php'; 
?>
