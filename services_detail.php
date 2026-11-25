<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/admin/config.php';

$serviceId = filter_var($_GET['id'] ?? ($_GET['services_id'] ?? null), FILTER_VALIDATE_INT);
$service = null;
$errorMsg = '';

if ($serviceId && $serviceId > 0) {
    $stmt = mysqli_prepare($conn, "SELECT id, name, price, description, photo FROM services WHERE id = ? LIMIT 1");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $serviceId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $service = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);
    }
}

if (!$service) {
    $errorMsg = "The requested decoration service was not found or is currently unavailable.";
}
?>

<?php include 'header.php'; ?>
<?php include 'menu.php'; ?>

<!--================================
=            Page Title            =
=================================-->

<section class="page-title bg-title overlay-dark">
	<div class="container">
		<div class="row">
			<div class="col-12 text-center">
				<div class="title">
					<h3><?php echo htmlspecialchars($service['name'] ?? 'Service Details', ENT_QUOTES, 'UTF-8'); ?></h3>
				</div>
				<ol class="breadcrumb p-0 m-0">
				  <li class="breadcrumb-item"><a href="index.php">Home</a></li>
				  <li class="breadcrumb-item"><a href="services.php">Services</a></li>
				  <li class="breadcrumb-item active"><?php echo htmlspecialchars($service['name'] ?? 'Details', ENT_QUOTES, 'UTF-8'); ?></li>
				</ol>
			</div>
		</div>
	</div>
</section>

<!--====  End of Page Title  ====-->


<!--================================
=            Service Details       =
=================================-->

<section class="news section">
	<div class="container">
		<div class="row mt-30">
			<div class="col-lg-8 col-md-10 mx-auto">
				<div class="block">
					<?php if (!empty($errorMsg)) { ?>
						<div class="alert alert-warning text-center p-4">
							<i class="fa fa-exclamation-triangle fa-2x mb-3 text-warning"></i>
							<h4>Service Not Found</h4>
							<p><?php echo htmlspecialchars($errorMsg, ENT_QUOTES, 'UTF-8'); ?></p>
							<a href="services.php" class="btn btn-main-md mt-2">View All Services</a>
						</div>
					<?php } else { 
						$photoName = basename($service['photo'] ?? '');
						$photoPath = !empty($photoName) ? 'admin/uploads/' . rawurlencode($photoName) : 'images/gallery/gallery-one.jpg';
						$price = is_numeric($service['price']) ? (float)$service['price'] : 0;
					?>
						<!-- Service Article -->
						<article class="blog-post single">
							<div class="post-thumb">
								<img src="<?php echo htmlspecialchars($photoPath, ENT_QUOTES, 'UTF-8'); ?>" 
								     alt="<?php echo htmlspecialchars($service['name'], ENT_QUOTES, 'UTF-8'); ?>" 
								     class="img-fluid rounded" style="width: 100%; max-height: 450px; object-fit: cover;">
							</div>
							<div class="post-content mt-4">
								<div class="post-title d-flex justify-content-between align-items-center flex-wrap">
									<h2 class="font-weight-bold text-dark"><?php echo htmlspecialchars($service['name'], ENT_QUOTES, 'UTF-8'); ?></h2>
									<span class="badge badge-success px-3 py-2" style="font-size: 1.25rem;">
										&#8377; <?php echo number_format($price, 2); ?>
									</span>
								</div>
								<div class="post-details mt-3">
									<h5 class="text-primary font-weight-bold">Package Overview & Description</h5>
									<p style="font-size: 16px; line-height: 1.8; color: #555;">
										<?php echo nl2br(htmlspecialchars($service['description'] ?? '', ENT_QUOTES, 'UTF-8')); ?>
									</p>
									<hr class="my-4">
									<div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
										<a href="booking.php?services_id=<?php echo (int)$service['id']; ?>" class="btn btn-main-md">
											<i class="fa fa-calendar-check-o mr-2"></i> Book This Service Now
										</a>
										<a href="services.php" class="btn btn-outline-secondary">
											<i class="fa fa-arrow-left mr-2"></i> Back to All Services
										</a>
									</div>
								</div>
							</div>
						</article>
					<?php } ?>
				</div>
			</div>
		</div>
	</div>
</section>

<!--================================
=            Google Map            =
=================================-->

<section class="map">
	<div id="map"></div>
	<div class="address-block">
		<h4>Samruddhi Decorators</h4>
		<ul class="address-list p-0 m-0">
			<li><i class="fa fa-home"></i><span>At post Shiroda, Tal. Vengurla<br>Dist: Sindhudurg, State: Maharashtra</span></li>
			<li><i class="fa fa-phone"></i><span>+91 9222510824</span></li>
		</ul>
		<a href="contact.php" class="btn btn-white-md">Contact Us</a>
	</div>
</section>

<!--====  End of Google Map  ====-->
<?php include 'footer.php'; ?>
