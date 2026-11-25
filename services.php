
<?php
// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection
require_once __DIR__ . '/admin/config.php';

// Fetch services
$services = [];
$serviceError = false;

$sql = "SELECT * FROM services ORDER BY id DESC";
$query = mysqli_query($conn, $sql);

if ($query === false) {
    // Log actual error, but do not expose database details to visitors
    error_log("Services query error: " . mysqli_error($conn));
    $serviceError = true;
} else {
    while ($row = mysqli_fetch_assoc($query)) {
        $services[] = $row;
    }

    mysqli_free_result($query);
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
                    <h3>Our Services</h3>
                    <p>Make Your Special Moments Memorable With Us</p>
                </div>

                <ol class="breadcrumb p-0 m-0">
                    <li class="breadcrumb-item">
                        <a href="index.php">Home</a>
                    </li>

                    <li class="breadcrumb-item active">
                        Services
                    </li>
                </ol>

            </div>
        </div>

    </div>
</section>


<!-- =========================
     SERVICES SECTION
========================= -->

<section class="section service-section">

    <div class="container">

        <!-- Section Heading -->
        <div class="row">
            <div class="col-12 text-center">

                <div class="service-heading">
                    <h2>Explore Our <span>Services</span></h2>

                    <p>
                        Choose the perfect decoration service for
                        your special occasion.
                    </p>
                </div>

            </div>
        </div>


        <div class="row">

            <?php if ($serviceError) { ?>

                <!-- Database Error -->
                <div class="col-12">
                    <div class="service-message error-message">

                        <i class="fa fa-exclamation-circle"></i>

                        <h4>Unable to Load Services</h4>

                        <p>
                            Sorry, services are temporarily unavailable.
                            Please try again later.
                        </p>

                        <a href="services.php" class="btn btn-main-md">
                            Try Again
                        </a>

                    </div>
                </div>

            <?php } elseif (empty($services)) { ?>

                <!-- No Services Available -->
                <div class="col-12">
                    <div class="service-message">

                        <i class="fa fa-calendar-times-o"></i>

                        <h4>No Services Available</h4>

                        <p>
                            We are updating our services.
                            Please visit again soon.
                        </p>

                    </div>
                </div>

            <?php } else { ?>

                <?php foreach ($services as $row) { ?>

                    <?php
                    // Safely prepare service information
                    $serviceId = (int)($row['id'] ?? 0);

                    $serviceName = htmlspecialchars(
                        $row['name'] ?? 'Decoration Service',
                        ENT_QUOTES,
                        'UTF-8'
                    );

                    $serviceDescription = htmlspecialchars(
                        $row['description'] ?? '',
                        ENT_QUOTES,
                        'UTF-8'
                    );

                    $servicePrice = is_numeric($row['price'] ?? null)
                        ? (float)$row['price']
                        : 0;

                    // Use only the uploaded filename
                    $photoName = basename($row['photo'] ?? '');

                    $photoPath = 'admin/uploads/' . rawurlencode($photoName);
                    ?>

                    <div class="col-lg-4 col-md-6 col-sm-12 mb-4">

                        <div class="service-card">

                            <!-- Service Image -->
                            <div class="service-img-wrapper">

                                <?php if (!empty($photoName)) { ?>

                                    <img
                                        src="<?php echo htmlspecialchars($photoPath, ENT_QUOTES, 'UTF-8'); ?>"
                                        alt="<?php echo $serviceName; ?>"
                                        class="service-img"
                                        loading="lazy"
                                    >

                                <?php } else { ?>

                                    <div class="service-no-image">
                                        <i class="fa fa-picture-o"></i>
                                        <span>Image Not Available</span>
                                    </div>

                                <?php } ?>

                                <!-- Price -->
                                <span class="service-price-badge">
                                    &#8377; <?php echo number_format($servicePrice, 2); ?>
                                </span>

                            </div>


                            <!-- Service Details -->
                            <div class="service-card-body">

                                <h4 class="service-card-title">
                                    <a href="services_detail.php?id=<?php echo $serviceId; ?>" class="text-dark">
                                        <?php echo $serviceName; ?>
                                    </a>
                                </h4>

                                <p class="service-card-desc">
                                    <?php echo $serviceDescription; ?>
                                </p>


                                <!-- Booking Button -->
                                <div class="service-card-action">

                                    <?php if ($serviceId > 0) { ?>

                                        <a
                                            href="booking.php?services_id=<?php echo $serviceId; ?>"
                                            class="btn btn-main-md btn-block"
                                        >
                                            Book Now
                                            <i class="fa fa-arrow-right ml-2"></i>
                                        </a>

                                    <?php } else { ?>

                                        <button
                                            type="button"
                                            class="btn btn-secondary btn-block"
                                            disabled
                                        >
                                            Booking Unavailable
                                        </button>

                                    <?php } ?>

                                </div>

                            </div>

                        </div>

                    </div>

                <?php } ?>

            <?php } ?>

        </div>

    </div>

</section>


<?php include 'footer.php'; ?>
