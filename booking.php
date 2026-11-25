<?php

// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection
require_once __DIR__ . '/admin/config.php';

// Login verification
if (
    !isset($_SESSION['userid']) ||
    !filter_var($_SESSION['userid'], FILTER_VALIDATE_INT) ||
    (int)$_SESSION['userid'] <= 0
) {
    $_SESSION['error'] = "Please log in to book a service.";
    header("Location: login.php");
    exit();
}

$currentUserId = (int)$_SESSION['userid'];

// Default values
$service_name = '';
$price = '';
$service_id = 0;
$page_error = '';

// Check flash messages from session
$date_error = '';
if (isset($_SESSION['date_error'])) {
    $date_error = (string)$_SESSION['date_error'];
    unset($_SESSION['date_error']);
}

$booking_error = '';
if (isset($_SESSION['booking_error'])) {
    $booking_error = (string)$_SESSION['booking_error'];
    unset($_SESSION['booking_error']);
}

// Check restored form data if redirected back
$savedFormData = $_SESSION['booking_form_data'] ?? [];
unset($_SESSION['booking_form_data']);

// Validate service ID from GET or saved form data
$rawServiceId = $_GET['services_id'] ?? ($savedFormData['service_id'] ?? null);
$service_id = filter_var($rawServiceId, FILTER_VALIDATE_INT);

if ($service_id === false || $service_id <= 0) {
    $_SESSION['error'] = "Invalid service selected.";
    header("Location: services.php");
    exit();
}

// Fetch service using prepared statement
$stmt = mysqli_prepare(
    $conn,
    "SELECT id, name, price FROM services WHERE id = ? LIMIT 1"
);

if (!$stmt) {
    error_log("Service prepare error: " . mysqli_error($conn));
    $page_error = "Unable to load the selected service. Please try again.";
} else {
    mysqli_stmt_bind_param($stmt, "i", $service_id);

    if (!mysqli_stmt_execute($stmt)) {
        error_log("Service execute error: " . mysqli_stmt_error($stmt));
        $page_error = "Unable to load the selected service. Please try again.";
    } else {
        mysqli_stmt_bind_result(
            $stmt,
            $dbServiceId,
            $dbServiceName,
            $dbServicePrice
        );

        if (mysqli_stmt_fetch($stmt)) {
            $service_id = (int)$dbServiceId;
            $service_name = $dbServiceName;
            $price = $dbServicePrice;
        } else {
            $page_error = "Selected service does not exist. Please choose another service.";
        }
    }
    mysqli_stmt_close($stmt);
}

// Fetch currently booked dates (Confirmed OR Active Pending by another user within 15 min)
$bookedDates = [];
$dateStmt = mysqli_prepare(
    $conn,
    "SELECT DISTINCT date FROM booking 
     WHERE status = 1 
        OR (status = 0 AND user_id != ? AND transaction_date >= DATE_SUB(NOW(), INTERVAL 15 MINUTE))"
);
if ($dateStmt) {
    mysqli_stmt_bind_param($dateStmt, "i", $currentUserId);
    mysqli_stmt_execute($dateStmt);
    $dateRes = mysqli_stmt_get_result($dateStmt);
    if ($dateRes) {
        while ($dRow = mysqli_fetch_assoc($dateRes)) {
            if (!empty($dRow['date'])) {
                $bookedDates[] = $dRow['date'];
            }
        }
    }
    mysqli_stmt_close($dateStmt);
}

// User details from session or restored form
$user_name = $savedFormData['user_name'] ?? ($_SESSION['name'] ?? '');
$email = $savedFormData['email'] ?? ($_SESSION['email'] ?? '');
$contact = $savedFormData['contact'] ?? ($_SESSION['mobile'] ?? '');
$location = $savedFormData['location'] ?? '';

// Generate CSRF token
if (
    empty($_SESSION['booking_csrf_token']) ||
    !is_string($_SESSION['booking_csrf_token'])
) {
    $_SESSION['booking_csrf_token'] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION['booking_csrf_token'];

// Escape output helper
function bookingEscape($value) {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

?>

<?php include 'header.php'; ?>
<?php include 'menu.php'; ?>


<!-- =========================
     PAGE TITLE
========================= -->

<section class="page-title bg-title overlay-dark">
    <div class="container">
        <div class="row">
            <div class="col-12 text-center">
                <div class="title">
                    <h3>Book Service</h3>
                </div>
                <ol class="breadcrumb p-0 m-0">
                    <li class="breadcrumb-item">
                        <a href="index.php">Home</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="services.php">Services</a>
                    </li>
                    <li class="breadcrumb-item active">
                        Booking
                    </li>
                </ol>
            </div>
        </div>
    </div>
</section>


<!-- =========================
     BOOKING FORM
========================= -->

<section class="section contact-form">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="section-title">
                    <h3>
                        <span class="alternate">
                            Service Booking Form
                        </span>
                    </h3>
                </div>
            </div>
        </div>

        <?php if (!empty($page_error)) { ?>

            <div class="alert alert-danger text-center" role="alert">
                <?php echo bookingEscape($page_error); ?>
                <br><br>
                <a href="services.php" class="btn btn-main-md btn-sm">
                    <i class="fa fa-arrow-left mr-2"></i> Go Back to Services
                </a>
            </div>

        <?php } else { ?>

            <!-- Alert notification if date is already booked or other error -->
            <?php if (!empty($date_error)) { ?>
                <div class="alert alert-danger alert-dismissible fade show mx-auto mb-4" style="max-width: 500px;" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="fa fa-calendar-times-o fa-2x mr-3 text-danger"></i>
                        <div>
                            <strong>Booking Date Unavailable!</strong>
                            <div class="small"><?php echo bookingEscape($date_error); ?></div>
                        </div>
                    </div>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php } elseif (!empty($booking_error)) { ?>
                <div class="alert alert-warning alert-dismissible fade show mx-auto mb-4" style="max-width: 500px;" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="fa fa-exclamation-triangle fa-2x mr-3 text-warning"></i>
                        <div>
                            <strong>Notice:</strong>
                            <div class="small"><?php echo bookingEscape($booking_error); ?></div>
                        </div>
                    </div>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php } ?>

            <form action="pay.php" method="POST" class="row form" id="bookingForm">

                <!-- CSRF Protection -->
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?php echo bookingEscape($csrf_token); ?>"
                >

                <!-- Service ID -->
                <input
                    type="hidden"
                    name="service_id"
                    value="<?php echo (int)$service_id; ?>"
                >

                <!-- Selected Service -->
                <div class="col-md-12">
                    <label class="form-label font-weight-bold text-dark mb-1">
                        Service Selected
                    </label>
                    <input
                        type="text"
                        class="form-control main bg-light"
                        name="service_name"
                        id="service_name"
                        value="<?php echo bookingEscape($service_name); ?>"
                        readonly
                        required
                    >
                </div>

                <!-- Full Name -->
                <div class="col-md-12">
                    <label class="form-label font-weight-bold text-dark mb-1">
                        Full Name
                    </label>
                    <input
                        type="text"
                        class="form-control main"
                        name="user_name"
                        id="user_name"
                        placeholder="Your Full Name"
                        value="<?php echo bookingEscape($user_name); ?>"
                        minlength="2"
                        maxlength="100"
                        pattern="[A-Za-z ]{2,100}"
                        title="Please enter a valid name using letters and spaces."
                        autocomplete="name"
                        required
                    >
                </div>

                <!-- Email -->
                <div class="col-md-6">
                    <label class="form-label font-weight-bold text-dark mb-1">
                        Email Address
                    </label>
                    <input
                        type="email"
                        class="form-control main"
                        name="email"
                        id="email"
                        placeholder="Email Address"
                        value="<?php echo bookingEscape($email); ?>"
                        maxlength="254"
                        autocomplete="email"
                        required
                    >
                </div>

                <!-- Contact -->
                <div class="col-md-6">
                    <label class="form-label font-weight-bold text-dark mb-1">
                        Contact Number
                    </label>
                    <input
                        type="tel"
                        class="form-control main"
                        name="contact"
                        id="contact"
                        placeholder="10 Digit Contact Number"
                        value="<?php echo bookingEscape($contact); ?>"
                        pattern="[0-9]{10}"
                        minlength="10"
                        maxlength="10"
                        inputmode="numeric"
                        autocomplete="tel"
                        title="Please enter a valid 10 digit mobile number."
                        required
                    >
                </div>

                <!-- Event Location -->
                <div class="col-md-12">
                    <label class="form-label font-weight-bold text-dark mb-1">
                        Event Location / Venue Address
                    </label>
                    <input
                        type="text"
                        class="form-control main"
                        name="location"
                        id="location"
                        placeholder="Enter Full Event Address"
                        value="<?php echo bookingEscape($location); ?>"
                        minlength="5"
                        maxlength="255"
                        autocomplete="street-address"
                        required
                    >
                </div>

                <!-- Event Date -->
                <div class="col-md-6">
                    <label class="form-label font-weight-bold text-dark mb-1">
                        Booking / Event Date
                    </label>
                    <input
                        type="date"
                        class="form-control main"
                        name="date"
                        id="Date"
                        min="<?php echo date('Y-m-d'); ?>"
                        required
                    >
                </div>

                <!-- Price -->
                <div class="col-md-6">
                    <label class="form-label font-weight-bold text-dark mb-1">
                        Total Amount (&#8377;)
                    </label>
                    <input
                        type="text"
                        class="form-control main bg-light"
                        name="price"
                        id="price"
                        value="<?php echo bookingEscape(number_format((float)$price, 2, '.', '')); ?>"
                        readonly
                        required
                    >
                </div>

                <!-- Payment Button -->
                <div class="col-12 text-center mt-3">
                    <button
                        type="submit"
                        id="rzp-button1"
                        name="booking"
                        class="btn btn-main-md btn-block"
                    >
                        Proceed to Payment
                        <i class="fa fa-arrow-right ml-2"></i>
                    </button>
                </div>

            </form>

        <?php } ?>

    </div>
</section>

<!-- =========================
     DATE BOOKED POPUP MODAL
========================= -->
<div class="modal fade" id="dateBookedModal" tabindex="-1" role="dialog" aria-labelledby="dateBookedModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg border-0" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header bg-danger text-white py-3">
                <h5 class="modal-title font-weight-bold mb-0" id="dateBookedModalLabel">
                    <i class="fa fa-calendar-times-o mr-2"></i> Date Unavailable
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center py-4 px-4">
                <div class="mb-3">
                    <i class="fa fa-exclamation-circle text-danger" style="font-size: 54px;"></i>
                </div>
                <h4 class="font-weight-bold text-dark mb-2">Sorry! This date is already booked. Please select another date.</h4>
                <p class="text-muted mb-0">The event date you selected is currently reserved. Please choose another date to proceed with your booking.</p>
            </div>
            <div class="modal-footer justify-content-center bg-light border-top-0 py-3">
                <button type="button" class="btn btn-main-md px-4" data-dismiss="modal">
                    <i class="fa fa-calendar mr-2"></i> Select Another Date
                </button>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<!-- Script for Real-Time Date Validation & Modal Popup -->
<script>
$(document).ready(function () {
    var bookedDates = <?php echo json_encode($bookedDates); ?>;
    var serverDateError = <?php echo !empty($date_error) ? 'true' : 'false'; ?>;

    // Show popup immediately if redirected back due to server-side date validation
    if (serverDateError) {
        $('#dateBookedModal').modal('show');
    }

    // Client-side date selection check
    $('#Date').on('change', function () {
        var selectedDate = $(this).val();
        if (selectedDate && bookedDates.indexOf(selectedDate) !== -1) {
            $(this).val('');
            $('#dateBookedModal').modal('show');
        }
    });

    // Client-side form submit check
    $('#bookingForm').on('submit', function (e) {
        var selectedDate = $('#Date').val();
        if (selectedDate && bookedDates.indexOf(selectedDate) !== -1) {
            e.preventDefault();
            $('#Date').val('');
            $('#dateBookedModal').modal('show');
            return false;
        }
    });
});
</script>
