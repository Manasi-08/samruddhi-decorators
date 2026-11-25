<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/menu.php';

$query = mysqli_query(
    $conn,
    "SELECT s.id, s.name, s.price, s.description, s.photo, 
            (SELECT COUNT(*) FROM booking b WHERE b.service_name = s.name) as booking_count 
     FROM services s 
     ORDER BY s.id DESC"
);
$csrfToken = admin_csrf_token();
?>

<!-- Page Breadcrumb Header -->
<div class="page-breadcrumb-header">
    <div>
        <h1 class="page-title-text">Service Catalog</h1>
        <div class="page-subtitle-text">Manage all your decoration services, packages, and pricing details.</div>
    </div>
    <div>
        <a href="add_services.php" class="btn-primary-custom">
            <i class="fa fa-plus-circle"></i> Add New Service
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

<!-- Services Table Card -->
<div class="custom-card">
    <div class="custom-card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title-custom"><i class="fa fa-cubes text-primary me-2"></i> Registered Decoration Packages</h5>
        <span class="badge bg-primary-subtle text-primary fw-bold">Active Catalog</span>
    </div>
    <div class="custom-card-body p-3">
        <div class="table-responsive-custom">
            <table id="services" class="table table-custom align-middle">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 50px;">No.</th>
                        <th>Photo</th>
                        <th>Service Package</th>
                        <th>Package Price</th>
                        <th style="width: 35%;">Description</th>
                        <th class="text-center">Bookings</th>
                        <th class="text-center" style="width: 140px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $i = 1;
                if ($query && mysqli_num_rows($query) > 0) {
                    while ($row = mysqli_fetch_assoc($query)) {
                        $bCount = intval($row['booking_count'] ?? 0);
                ?>
                    <tr>
                        <td class="text-center fw-bold text-muted"><?php echo $i++; ?></td>
                        <td>
                            <div class="rounded-3 border overflow-hidden shadow-sm" style="width: 80px; height: 60px; background: #F1F5F9;">
                                <?php if (!empty($row['photo'])) { ?>
                                    <img src="uploads/<?php echo htmlspecialchars($row['photo']); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php } else { ?>
                                    <div class="d-flex align-items-center justify-content-center h-100 text-muted small"><i class="fa fa-image fs-4"></i></div>
                                <?php } ?>
                            </div>
                        </td>
                        <td>
                            <div class="fw-bold text-dark fs-6"><?php echo htmlspecialchars($row['name']); ?></div>
                            <small class="text-muted">ID: #SRV-<?php echo $row['id']; ?></small>
                        </td>
                        <td>
                            <span class="badge bg-success-subtle text-success fs-6 fw-bold px-3 py-2 border border-success-subtle">
                                &#8377;<?php echo number_format(floatval($row['price']), 2); ?>
                            </span>
                        </td>
                        <td>
                            <div class="text-muted small" style="line-height: 1.4; max-height: 48px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                                <?php echo htmlspecialchars($row['description']); ?>
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border px-2 py-1">
                                <i class="fa fa-calendar-check-o text-primary me-1"></i> <?php echo $bCount; ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center gap-1">
                                <a href="edit_services.php?services_id=<?php echo $row['id']; ?>" class="btn-action-edit" title="Edit Service">
                                    <i class="fa fa-pencil"></i>
                                </a>
                                <?php if ($bCount === 0) { ?>
                                    <a href="delete_services.php?services-id=<?php echo $row['id']; ?>&csrf_token=<?php echo admin_escape($csrfToken); ?>" 
                                       onclick="return confirm('Are you sure you want to delete this service package?')" 
                                       class="btn-action-delete" title="Delete Service">
                                        <i class="fa fa-trash"></i>
                                    </a>
                                <?php } else { ?>
                                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2" 
                                            onclick="alert('Cannot delete: <?php echo $bCount; ?> customer booking(s) exist for this service in history. You can edit the package instead.')" 
                                            title="Package has bookings">
                                        <i class="fa fa-lock"></i>
                                    </button>
                                <?php } ?>
                            </div>
                        </td>
                    </tr>
                <?php
                    }
                } else {
                ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No decoration packages found in catalog.</td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    $('#services').DataTable({
        "pageLength": 10,
        "language": {
            "search": "_INPUT_",
            "searchPlaceholder": "Search services..."
        }
    });
});
</script>

<?php include 'footer.php'; ?>
