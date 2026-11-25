<?php
require_once __DIR__ . '/auth_check.php';

$serviceId = filter_var($_GET['services-id'] ?? ($_POST['services_id'] ?? null), FILTER_VALIDATE_INT);
$token = $_GET['csrf_token'] ?? ($_POST['csrf_token'] ?? '');

if (!verify_admin_csrf($token)) {
    $_SESSION['error'] = "Security token mismatch. Action declined.";
    header('Location: view_services.php');
    exit();
}

if (!$serviceId || $serviceId <= 0) {
    $_SESSION['error'] = "Invalid service ID.";
    header('Location: view_services.php');
    exit();
}

// 1. Fetch Service Name
$stmt = mysqli_prepare($conn, "SELECT name, photo FROM services WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $serviceId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$service = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$service) {
    $_SESSION['error'] = "Service record not found.";
    header('Location: view_services.php');
    exit();
}

$serviceName = $service['name'];

// 2. Check Dependencies: Do any customer bookings use this service?
$depStmt = mysqli_prepare($conn, "SELECT COUNT(*) as total FROM booking WHERE service_name = ?");
mysqli_stmt_bind_param($depStmt, "s", $serviceName);
mysqli_stmt_execute($depStmt);
$depRes = mysqli_stmt_get_result($depStmt);
$bookingCount = intval(mysqli_fetch_assoc($depRes)['total'] ?? 0);
mysqli_stmt_close($depStmt);

if ($bookingCount > 0) {
    $_SESSION['error'] = "Cannot delete package '" . htmlspecialchars($serviceName) . "': $bookingCount customer reservation(s) exist for this service in booking history. You can edit the service instead of deleting it.";
    header('Location: view_services.php');
    exit();
}

// 3. Safe Deletion
$delStmt = mysqli_prepare($conn, "DELETE FROM services WHERE id = ?");
mysqli_stmt_bind_param($delStmt, "i", $serviceId);
$executed = mysqli_stmt_execute($delStmt);
mysqli_stmt_close($delStmt);

if ($executed) {
    // Optionally delete uploaded image file if exists
    if (!empty($service['photo'])) {
        $imgPath = __DIR__ . '/uploads/' . $service['photo'];
        if (file_exists($imgPath)) {
            @unlink($imgPath);
        }
    }
    $_SESSION['success'] = "Service package '" . htmlspecialchars($serviceName) . "' deleted successfully.";
} else {
    $_SESSION['error'] = "Database error while deleting service package: " . mysqli_error($conn);
}

header('Location: view_services.php');
exit();
?>
