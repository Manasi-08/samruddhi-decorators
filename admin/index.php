<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/menu.php';

// 1. Fetch Real Database Statistics
$total_revenue = 0;
$res_rev = mysqli_query($conn, "SELECT SUM(price) as total FROM booking WHERE status = 1 AND transaction_status = 1");
if ($res_rev && $row = mysqli_fetch_assoc($res_rev)) {
    $total_revenue = floatval($row['total'] ?? 0);
}

$total_bookings = 0;
$res_b = mysqli_query($conn, "SELECT COUNT(*) as total FROM booking");
if ($res_b && $row = mysqli_fetch_assoc($res_b)) {
    $total_bookings = intval($row['total'] ?? 0);
}

$confirmed_bookings = 0;
$res_bc = mysqli_query($conn, "SELECT COUNT(*) as total FROM booking WHERE status = 1");
if ($res_bc && $row = mysqli_fetch_assoc($res_bc)) {
    $confirmed_bookings = intval($row['total'] ?? 0);
}

$pending_bookings = 0;
$res_bp = mysqli_query($conn, "SELECT COUNT(*) as total FROM booking WHERE status = 0");
if ($res_bp && $row = mysqli_fetch_assoc($res_bp)) {
    $pending_bookings = intval($row['total'] ?? 0);
}

$failed_bookings = 0;
$res_bf = mysqli_query($conn, "SELECT COUNT(*) as total FROM booking WHERE status = 2");
if ($res_bf && $row = mysqli_fetch_assoc($res_bf)) {
    $failed_bookings = intval($row['total'] ?? 0);
}

$user_count = 0;
$res_user = mysqli_query($conn, "SELECT COUNT(*) as total FROM user WHERE type = 0");
if ($res_user && $row = mysqli_fetch_assoc($res_user)) {
    $user_count = intval($row['total'] ?? 0);
}

$services_count = 0;
$res_services = mysqli_query($conn, "SELECT COUNT(*) as total FROM services");
if ($res_services && $row = mysqli_fetch_assoc($res_services)) {
    $services_count = intval($row['total'] ?? 0);
}

$contact_count = 0;
$res_contact = mysqli_query($conn, "SELECT COUNT(*) as total FROM contact");
if ($res_contact && $row = mysqli_fetch_assoc($res_contact)) {
    $contact_count = intval($row['total'] ?? 0);
}

// 2. Fetch Real Monthly Trend for Current Year
$currentYear = date('Y');
$monthlyBookings = array_fill(1, 12, 0);
$monthlyRevenue = array_fill(1, 12, 0);

$res_monthly = mysqli_query(
    $conn,
    "SELECT MONTH(date) as m, 
            COUNT(*) as total_b, 
            SUM(CASE WHEN status = 1 AND transaction_status = 1 THEN price ELSE 0 END) as total_rev 
     FROM booking 
     WHERE YEAR(date) = '$currentYear' 
     GROUP BY MONTH(date)"
);
if ($res_monthly) {
    while ($mRow = mysqli_fetch_assoc($res_monthly)) {
        $mIdx = (int)$mRow['m'];
        if ($mIdx >= 1 && $mIdx <= 12) {
            $monthlyBookings[$mIdx] = (int)$mRow['total_b'];
            $monthlyRevenue[$mIdx] = round(floatval($mRow['total_rev']) / 1000, 2); // Expressed in thousands for clean chart scaling
        }
    }
}

// 3. Fetch Real Service Popularity Distribution
$pieLabels = [];
$pieData = [];
$res_pie = mysqli_query(
    $conn,
    "SELECT service_name, COUNT(*) as cnt 
     FROM booking 
     WHERE service_name IS NOT NULL AND service_name != '' 
     GROUP BY service_name 
     ORDER BY cnt DESC 
     LIMIT 5"
);
if ($res_pie) {
    while ($pRow = mysqli_fetch_assoc($res_pie)) {
        $pieLabels[] = $pRow['service_name'];
        $pieData[] = (int)$pRow['cnt'];
    }
}

// 4. Fetch Real Recent Bookings (5 latest)
$recent_bookings = mysqli_query(
    $conn,
    "SELECT id, service_name, username, email, date, price, status, transaction_status, transaction_id, failure_reason 
     FROM booking 
     ORDER BY id DESC 
     LIMIT 5"
);

// 5. Fetch Real Recent Payment Transactions (5 latest)
$recent_txns = mysqli_query(
    $conn,
    "SELECT id, service_name, username, price, transaction_id, razorpay_order_id, transaction_date, transaction_status, status, failure_reason 
     FROM booking 
     WHERE (transaction_id IS NOT NULL AND transaction_id != '' AND transaction_id != '0') 
        OR razorpay_order_id IS NOT NULL 
     ORDER BY id DESC 
     LIMIT 5"
);
?>

<!-- Page Breadcrumb Header -->
<div class="page-breadcrumb-header">
    <div>
        <h1 class="page-title-text">Dashboard Overview</h1>
        <div class="page-subtitle-text">Real-time business activity, reservations summary, and payment performance.</div>
    </div>
    <div class="d-flex gap-2">
        <a href="view_booking.php" class="btn-primary-custom">
            <i class="fa fa-calendar-check-o"></i> View All Bookings
        </a>
        <a href="view_payments.php" class="btn-secondary-custom">
            <i class="fa fa-credit-card"></i> Payment Ledger
        </a>
    </div>
</div>

<!-- Key Business Metric Cards -->
<div class="row g-3 mb-4">
    <!-- Captured Payment Revenue -->
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
            <div class="stat-value">&#8377;<?php echo number_format($total_revenue, 2); ?></div>
            <div class="stat-label">Verified Captured Revenue</div>
            <div class="stat-footer text-muted">
                <i class="fa fa-check-circle text-success me-1"></i> Successful gateway captures only
            </div>
        </div>
    </div>

    <!-- Total Bookings with Breakdown -->
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-card-header">
                <div class="stat-icon-wrapper stat-icon-emerald">
                    <i class="fa fa-calendar-check-o"></i>
                </div>
                <span class="badge bg-primary-subtle text-primary fw-bold px-2 py-1 border border-primary-subtle">
                    <?php echo $confirmed_bookings; ?> Confirmed
                </span>
            </div>
            <div class="stat-value"><?php echo $total_bookings; ?></div>
            <div class="stat-label">Total Reservations</div>
            <div class="stat-footer text-muted">
                <span class="text-warning fw-semibold me-2"><i class="fa fa-clock-o"></i> <?php echo $pending_bookings; ?> Pending</span>
                <span class="text-danger fw-semibold"><i class="fa fa-times-circle"></i> <?php echo $failed_bookings; ?> Cancelled</span>
            </div>
        </div>
    </div>

    <!-- Registered Customers -->
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-card-header">
                <div class="stat-icon-wrapper stat-icon-amber">
                    <i class="fa fa-users"></i>
                </div>
                <span class="badge bg-warning-subtle text-warning-emphasis fw-bold px-2 py-1 border border-warning-subtle">
                    Customers
                </span>
            </div>
            <div class="stat-value"><?php echo $user_count; ?></div>
            <div class="stat-label">Registered Customers</div>
            <div class="stat-footer text-muted">
                <a href="view_users.php" class="text-decoration-none text-muted">
                    <i class="fa fa-arrow-circle-right text-primary me-1"></i> Manage customer accounts
                </a>
            </div>
        </div>
    </div>

    <!-- Active Services Catalog -->
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-card-header">
                <div class="stat-icon-wrapper stat-icon-rose">
                    <i class="fa fa-cubes"></i>
                </div>
                <span class="badge bg-info-subtle text-info-emphasis fw-bold px-2 py-1 border border-info-subtle">
                    Catalog
                </span>
            </div>
            <div class="stat-value"><?php echo $services_count; ?></div>
            <div class="stat-label">Active Decoration Packages</div>
            <div class="stat-footer text-muted">
                <span class="text-secondary"><i class="fa fa-envelope-o me-1"></i> <?php echo $contact_count; ?> Inquiries</span>
            </div>
        </div>
    </div>
</div>

<!-- Analytical Charts Section -->
<div class="row g-3 mb-4">
    <!-- Monthly Bookings Trend Chart -->
    <div class="col-xl-8 col-lg-7">
        <div class="custom-card">
            <div class="custom-card-header">
                <h5 class="card-title-custom">
                    <i class="fa fa-line-chart me-2 text-primary"></i> Monthly Bookings & Captured Revenue Overview
                </h5>
                <span class="badge bg-light text-dark border">Year <?php echo $currentYear; ?> (Real Data)</span>
            </div>
            <div class="custom-card-body">
                <canvas id="monthlyBookingsChart" style="height: 300px; width: 100%;"></canvas>
            </div>
        </div>
    </div>

    <!-- Service Distribution Pie Chart -->
    <div class="col-xl-4 col-lg-5">
        <div class="custom-card">
            <div class="custom-card-header">
                <h5 class="card-title-custom"><i class="fa fa-pie-chart me-2 text-primary"></i> Service Demand Distribution</h5>
                <span class="text-muted small">Top Services</span>
            </div>
            <div class="custom-card-body d-flex align-items-center justify-content-center">
                <?php if (!empty($pieData) && array_sum($pieData) > 0) { ?>
                    <canvas id="servicePieChart" style="max-height: 280px;"></canvas>
                <?php } else { ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fa fa-pie-chart fa-3x mb-3 text-secondary opacity-50"></i>
                        <p class="m-0">No booking distribution data yet.</p>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent Event Bookings Table -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="custom-card">
            <div class="custom-card-header">
                <h5 class="card-title-custom"><i class="fa fa-history me-2 text-primary"></i> Recent Customer Reservations</h5>
                <a href="view_booking.php" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                    <i class="fa fa-list mr-1"></i> View All Bookings
                </a>
            </div>
            <div class="custom-card-body p-0">
                <div class="table-responsive">
                    <table class="table table-custom align-middle m-0">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 80px;">Booking ID</th>
                                <th>Customer</th>
                                <th>Service Package</th>
                                <th>Event Date</th>
                                <th>Amount</th>
                                <th class="text-center">Booking Status</th>
                                <th class="text-center">Payment Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if ($recent_bookings && mysqli_num_rows($recent_bookings) > 0) {
                                while ($b = mysqli_fetch_assoc($recent_bookings)) {
                                    $st = (int)$b['status'];
                                    $tst = (int)($b['transaction_status'] ?? 0);
                                    $reason = (string)($b['failure_reason'] ?? '');

                                    if ($st === 1) {
                                        $b_badge = '<span class="badge-custom badge-success-custom"><i class="fa fa-check-circle"></i> Confirmed</span>';
                                    } elseif ($st === 0) {
                                        $b_badge = '<span class="badge-custom badge-warning-custom"><i class="fa fa-clock-o"></i> Initiated</span>';
                                    } else {
                                        if (stripos($reason, 'cancel') !== false || stripos($reason, 'closed') !== false) {
                                            $b_badge = '<span class="badge-custom badge-secondary-custom"><i class="fa fa-ban"></i> Cancelled</span>';
                                        } elseif (stripos($reason, 'reject') !== false) {
                                            $b_badge = '<span class="badge-custom badge-danger-custom"><i class="fa fa-times-circle"></i> Rejected</span>';
                                        } else {
                                            $b_badge = '<span class="badge-custom badge-danger-custom"><i class="fa fa-exclamation-triangle"></i> Failed</span>';
                                        }
                                    }

                                    if ($tst === 1) {
                                        $p_badge = '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="fa fa-check"></i> Successful</span>';
                                    } elseif ($tst === 0) {
                                        $p_badge = '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1"><i class="fa fa-clock-o"></i> Pending</span>';
                                    } else {
                                        if (stripos($reason, 'cancel') !== false || stripos($reason, 'closed') !== false) {
                                            $p_badge = '<span class="badge bg-secondary-subtle text-secondary border px-2 py-1">Cancelled</span>';
                                        } else {
                                            $p_badge = '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">Failed</span>';
                                        }
                                    }
                            ?>
                            <tr>
                                <td class="text-center font-weight-bold">
                                    <span class="badge bg-light text-dark border">#BK-<?php echo (int)$b['id']; ?></span>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($b['username']); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($b['email']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($b['service_name']); ?></td>
                                <td>
                                    <i class="fa fa-calendar text-muted me-1"></i>
                                    <?php echo !empty($b['date']) ? date('d M Y', strtotime($b['date'])) : 'N/A'; ?>
                                </td>
                                <td class="fw-bold text-dark">&#8377;<?php echo number_format((float)$b['price'], 2); ?></td>
                                <td class="text-center"><?php echo $b_badge; ?></td>
                                <td class="text-center"><?php echo $p_badge; ?></td>
                            </tr>
                            <?php 
                                }
                            } else {
                                echo '<tr><td colspan="7" class="text-center text-muted py-4">No recent bookings recorded yet.</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart Initialization Scripts (Using 100% Real Database Metrics) -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    // 1. Monthly Bookings & Revenue Line Chart
    const chartCanvas = document.getElementById('monthlyBookingsChart');
    if (chartCanvas) {
        const ctx1 = chartCanvas.getContext('2d');
        let gradientBg = ctx1.createLinearGradient(0, 0, 0, 300);
        gradientBg.addColorStop(0, 'rgba(37, 99, 235, 0.25)');
        gradientBg.addColorStop(1, 'rgba(37, 99, 235, 0.0)');

        const bookingsData = <?php echo json_encode(array_values($monthlyBookings)); ?>;
        const revenueData = <?php echo json_encode(array_values($monthlyRevenue)); ?>;

        new Chart(ctx1, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [
                    {
                        label: 'Captured Revenue (₹ Thousands)',
                        data: revenueData,
                        borderColor: '#2563EB',
                        backgroundColor: gradientBg,
                        borderWidth: 3,
                        fill: true,
                        tension: 0.3,
                        pointRadius: 4,
                        pointBackgroundColor: '#2563EB'
                    },
                    {
                        label: 'Total Bookings',
                        data: bookingsData,
                        borderColor: '#10B981',
                        borderWidth: 2,
                        borderDash: [5, 5],
                        fill: false,
                        tension: 0.3,
                        pointRadius: 3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                if (context.datasetIndex === 0) {
                                    return ' Revenue: ₹' + (context.parsed.y * 1000).toLocaleString('en-IN');
                                }
                                return ' Bookings: ' + context.parsed.y;
                            }
                        }
                    }
                },
                scales: {
                    y: { 
                        grid: { color: '#E2E8F0' }, 
                        beginAtZero: true 
                    },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // 2. Service Demand Distribution Chart
    const pieCanvas = document.getElementById('servicePieChart');
    if (pieCanvas) {
        const pieLabels = <?php echo json_encode($pieLabels); ?>;
        const pieCounts = <?php echo json_encode($pieData); ?>;

        if (pieCounts.length > 0) {
            const ctx2 = pieCanvas.getContext('2d');
            new Chart(ctx2, {
                type: 'doughnut',
                data: {
                    labels: pieLabels,
                    datasets: [{
                        data: pieCounts,
                        backgroundColor: ['#2563EB', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6'],
                        borderWidth: 2,
                        borderColor: '#FFFFFF'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' }
                    },
                    cutout: '65%'
                }
            });
        }
    }
});
</script>

<?php include 'footer.php'; ?>
