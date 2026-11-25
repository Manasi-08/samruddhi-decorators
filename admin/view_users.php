<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/menu.php';

// 1. Handle Safe User Deletion via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_user') {
    $postedCsrf = $_POST['csrf_token'] ?? '';
    if (!verify_admin_csrf($postedCsrf)) {
        $_SESSION['error'] = "Security token mismatch. Please try again.";
        header("Location: view_users.php");
        exit();
    }

    $uId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
    $currentAdminId = (int)($_SESSION['userid'] ?? 0);

    if (!$uId || $uId <= 0) {
        $_SESSION['error'] = "Invalid customer ID submitted.";
        header("Location: view_users.php");
        exit();
    }

    if ($uId === $currentAdminId) {
        $_SESSION['error'] = "Action blocked: You cannot delete your own logged-in administrator account.";
        header("Location: view_users.php");
        exit();
    }

    // Check user role
    $checkRoleStmt = mysqli_prepare($conn, "SELECT type, name FROM user WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($checkRoleStmt, "i", $uId);
    mysqli_stmt_execute($checkRoleStmt);
    $roleRes = mysqli_stmt_get_result($checkRoleStmt);
    $targetUser = mysqli_fetch_assoc($roleRes);
    mysqli_stmt_close($checkRoleStmt);

    if (!$targetUser) {
        $_SESSION['error'] = "Customer record not found.";
        header("Location: view_users.php");
        exit();
    }

    if ((int)$targetUser['type'] === 1) {
        $_SESSION['error'] = "Action blocked: Administrator accounts cannot be deleted.";
        header("Location: view_users.php");
        exit();
    }

    // Check if user has associated historical or active bookings
    $checkBookings = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM booking WHERE user_id = ?");
    mysqli_stmt_bind_param($checkBookings, "i", $uId);
    mysqli_stmt_execute($checkBookings);
    $bCount = mysqli_fetch_assoc(mysqli_stmt_get_result($checkBookings))['total'] ?? 0;
    mysqli_stmt_close($checkBookings);

    if ((int)$bCount > 0) {
        $_SESSION['error'] = "Cannot delete customer: $bCount reservation records are associated with this user account.";
        header("Location: view_users.php");
        exit();
    }

    // Delete customer using prepared statement
    $delStmt = mysqli_prepare($conn, "DELETE FROM user WHERE id = ? AND type = 0");
    mysqli_stmt_bind_param($delStmt, "i", $uId);
    $deleted = mysqli_stmt_execute($delStmt);
    mysqli_stmt_close($delStmt);

    if ($deleted) {
        $_SESSION['success'] = "Customer account for " . htmlspecialchars($targetUser['name']) . " removed successfully.";
    } else {
        $_SESSION['error'] = "Failed to remove customer account: " . mysqli_error($conn);
    }

    header("Location: view_users.php");
    exit();
}

// 2. Fetch All Real Registered Users
$query = mysqli_query(
    $conn,
    "SELECT u.id, u.name, u.email_id, u.contact, u.type, 
            (SELECT COUNT(*) FROM booking b WHERE b.user_id = u.id) as booking_count 
     FROM user u 
     ORDER BY u.id DESC"
);

$csrfToken = admin_csrf_token();
?>

<!-- Page Breadcrumb Header -->
<div class="page-breadcrumb-header">
    <div>
        <h1 class="page-title-text">Registered Customers Management</h1>
        <div class="page-subtitle-text">View verified registered customer profiles, contact numbers, and reservation history.</div>
    </div>
    <div>
        <a href="view_booking.php" class="btn-primary-custom">
            <i class="fa fa-calendar-check-o"></i> View Bookings
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

<!-- Customer Accounts Table Card -->
<div class="custom-card">
    <div class="custom-card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title-custom"><i class="fa fa-users text-primary me-2"></i> All Registered Accounts</h5>
        <span class="badge bg-primary-subtle text-primary fw-bold">Live Database</span>
    </div>
    <div class="custom-card-body p-3">
        <div class="table-responsive-custom">
            <table id="usersTable" class="table table-custom align-middle">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 70px;">User ID</th>
                        <th>Full Name</th>
                        <th>Email Address</th>
                        <th>Phone / Contact</th>
                        <th class="text-center">Account Role</th>
                        <th class="text-center">Total Bookings</th>
                        <th class="text-center" style="width: 140px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                if ($query && mysqli_num_rows($query) > 0) {
                    while ($user = mysqli_fetch_assoc($query)) {
                        $isAccountAdmin = ((int)$user['type'] === 1);
                        $bookingsCount = (int)$user['booking_count'];
                ?>
                    <tr>
                        <td class="text-center font-weight-bold">
                            <span class="badge bg-light text-dark border">#USR-<?php echo $user['id']; ?></span>
                        </td>
                        <td>
                            <div class="fw-bold text-dark fs-6"><?php echo htmlspecialchars($user['name']); ?></div>
                        </td>
                        <td>
                            <a href="mailto:<?php echo htmlspecialchars($user['email_id']); ?>" class="text-primary text-decoration-none">
                                <i class="fa fa-envelope-o me-1"></i><?php echo htmlspecialchars($user['email_id']); ?>
                            </a>
                        </td>
                        <td>
                            <span class="text-dark">
                                <i class="fa fa-phone text-muted me-1"></i><?php echo htmlspecialchars($user['contact'] ?: 'N/A'); ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <?php if ($isAccountAdmin) { ?>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                                    <i class="fa fa-shield me-1"></i> Administrator
                                </span>
                            <?php } else { ?>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                                    <i class="fa fa-user me-1"></i> Customer
                                </span>
                            <?php } ?>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border px-2 py-1">
                                <i class="fa fa-calendar-check-o text-success me-1"></i> <?php echo $bookingsCount; ?> Bookings
                            </span>
                        </td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-1">
                                <?php if (!$isAccountAdmin) { ?>
                                    <!-- Delete Button -->
                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-2" 
                                            onclick="confirmDeleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars(addslashes($user['name'])); ?>', <?php echo $bookingsCount; ?>)" 
                                            title="Delete Customer Account">
                                        <i class="fa fa-trash"></i> Delete
                                    </button>
                                <?php } else { ?>
                                    <span class="badge bg-light text-muted border px-2 py-1 small">Protected</span>
                                <?php } ?>
                            </div>
                        </td>
                    </tr>
                <?php
                    }
                } else {
                ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No registered accounts found in database.</td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Delete User Modal Confirmation -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form method="POST" action="view_users.php">
                <input type="hidden" name="action" value="delete_user">
                <input type="hidden" name="csrf_token" value="<?php echo admin_escape($csrfToken); ?>">
                <input type="hidden" name="user_id" id="deleteUserId" value="">

                <div class="modal-header border-bottom bg-danger text-white">
                    <h5 class="modal-title font-weight-bold" id="deleteUserModalLabel">
                        <i class="fa fa-exclamation-triangle me-2"></i> Confirm Account Removal
                    </h5>
                    <button type="button" class="btn-close text-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="mb-2 fs-6 text-dark">Are you sure you want to remove the customer account for:</p>
                    <div class="fw-bold fs-5 text-danger mb-3" id="deleteUserName"></div>
                    <div id="deleteWarningMsg" class="alert alert-warning small mb-0">
                        <i class="fa fa-info-circle me-1"></i> Customer accounts with existing historical bookings cannot be deleted.
                    </div>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-3" id="confirmDeleteBtn">Delete Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    $('#usersTable').DataTable({
        "pageLength": 10,
        "language": {
            "search": "_INPUT_",
            "searchPlaceholder": "Search customers..."
        }
    });
});

function confirmDeleteUser(id, name, bookingCount) {
    document.getElementById('deleteUserId').value = id;
    document.getElementById('deleteUserName').textContent = name;
    
    var warning = document.getElementById('deleteWarningMsg');
    var btn = document.getElementById('confirmDeleteBtn');
    
    if (bookingCount > 0) {
        warning.className = "alert alert-danger small mb-0";
        warning.innerHTML = "<i class='fa fa-ban me-1'></i> <strong>Blocked:</strong> This user has " + bookingCount + " booking reservations in the database. Customer data cannot be deleted while reservations exist.";
        btn.disabled = true;
    } else {
        warning.className = "alert alert-warning small mb-0";
        warning.innerHTML = "<i class='fa fa-info-circle me-1'></i> This customer has no reservations. Removal is permanent.";
        btn.disabled = false;
    }

    var myModal = new bootstrap.Modal(document.getElementById('deleteUserModal'));
    myModal.show();
}
</script>

<?php include 'footer.php'; ?>
