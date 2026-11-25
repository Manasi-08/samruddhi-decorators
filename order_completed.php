<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/admin/config.php';

$completed = $_SESSION['completed_booking'] ?? null;
unset($_SESSION['completed_booking']);

// Fallback to latest successful booking if refreshed
if (!$completed && isset($_SESSION['userid'])) {
    $uid = (int)$_SESSION['userid'];
    $stmt = mysqli_prepare(
        $conn,
        "SELECT id, service_name, date, location, price, transaction_id 
         FROM booking 
         WHERE user_id = ? AND status = 1 
         ORDER BY id DESC 
         LIMIT 1"
    );
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $uid);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $completed = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);
    }
}

include 'header.php';
include 'menu.php';
?>

<section class="section" style="min-height: 550px; padding: 60px 0;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6 text-center">
                <div class="card border-0 shadow-lg p-4 p-md-5 rounded-lg" style="border-radius: 16px;">
                    
                    <!-- Celebration Success Icon -->
                    <div class="mb-4">
                        <div class="rounded-circle bg-success text-white d-inline-flex align-items-center justify-content-center shadow" 
                             style="width: 86px; height: 86px; font-size: 42px;">
                            <i class="fa fa-check"></i>
                        </div>
                    </div>

                    <h2 class="font-weight-bold text-dark mb-2">Payment Successful!</h2>
                    <p class="text-muted lead mb-4">Thank you! Your decoration booking payment has been captured and your event date is successfully reserved.</p>

                    <!-- Receipt Details Box -->
                    <?php if ($completed) { ?>
                    <div class="bg-light p-3 rounded mb-4 text-left border">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Booking Reference:</span>
                            <span class="font-weight-bold text-dark">#BK-<?php echo htmlspecialchars((string)$completed['id']); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Service Package:</span>
                            <span class="font-weight-bold text-dark"><?php echo htmlspecialchars($completed['service_name']); ?></span>
                        </div>
                        <?php if (!empty($completed['date'])) { ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Reserved Event Date:</span>
                            <span class="font-weight-bold text-primary">
                                <i class="fa fa-calendar mr-1"></i><?php echo date('d M Y', strtotime($completed['date'])); ?>
                            </span>
                        </div>
                        <?php } ?>
                        <?php if (!empty($completed['location'])) { ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Event Venue:</span>
                            <span class="text-dark small text-right" style="max-width: 250px;"><?php echo htmlspecialchars($completed['location']); ?></span>
                        </div>
                        <?php } ?>
                        <?php if (!empty($completed['transaction_id']) && $completed['transaction_id'] !== '0') { ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Transaction ID:</span>
                            <span class="text-monospace font-weight-bold text-dark"><?php echo htmlspecialchars($completed['transaction_id']); ?></span>
                        </div>
                        <?php } ?>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="font-weight-bold text-dark">Amount Paid:</span>
                            <span class="h4 font-weight-bold text-success mb-0">&#8377;<?php echo number_format((float)$completed['price'], 2); ?></span>
                        </div>
                    </div>
                    <?php } ?>

                    <!-- Action Navigation Buttons -->
                    <div class="d-flex flex-column flex-sm-row justify-content-center gap-3">
                        <a href="mystatus.php" class="btn btn-main-md m-1">
                            <i class="fa fa-list-alt mr-2"></i> View Booking History
                        </a>
                        <a href="index.php" class="btn btn-outline-secondary m-1 px-4 py-2" style="border-radius: 6px;">
                            <i class="fa fa-home mr-2"></i> Return to Home
                        </a>
                    </div>

                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'footer.php'; ?>
