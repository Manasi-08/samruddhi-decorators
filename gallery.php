<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
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
					<h3>Our Event Gallery</h3>
					<p>Capturing the Magic of Your Special Celebrations</p>
				</div>
				<ol class="breadcrumb p-0 m-0">
				  <li class="breadcrumb-item"><a href="index.php">Home</a></li>
				  <li class="breadcrumb-item active">Gallery</li>
				</ol>
			</div>
		</div>
	</div>
</section>

<!--====  End of Page Title  ====-->


<!--=============================
=            Gallery            =
==============================-->

<section class="section gallery-section">
	<div class="container">
		
		<!-- Section Header -->
		<div class="row">
			<div class="col-12 text-center mb-4">
				<div class="section-title">
					<h3>Explore Our <span class="alternate">Decorations</span></h3>
					<p class="text-muted mt-2" style="max-width: 650px; margin: 0 auto;">
						Browse through our portfolio of royal wedding setups, vibrant haldi ceremonies, elegant reception stages, and customized birthday celebrations.
					</p>
				</div>
			</div>
		</div>

		<!-- Filter Controls -->
		<div class="row">
			<div class="col-12">
				<div class="gallery-filters controls">
		            <button type="button" class="control mixitup-control-active" data-filter="all">All Decorations</button>
		            <button type="button" class="control" data-filter=".wedding">Wedding Mandap</button>
		            <button type="button" class="control" data-filter=".haldi">Haldi & Traditional</button>
		            <button type="button" class="control" data-filter=".stage">Stage & Reception</button>
		            <button type="button" class="control" data-filter=".birthday">Birthday & Parties</button>
		        </div>
			</div>
		</div>

		<!-- Gallery Grid -->
		<div class="row gallery-wrapper">

			<!-- Item 1: Wedding Mandap -->
			<div class="col-lg-4 col-md-6 col-12 mb-4 mix wedding stage">
				<div class="gallery-card">
					<div class="gallery-card-img-wrap">
						<img src="images/gallery/wedding.jpg" alt="Grand Wedding Mandap Decoration" class="gallery-card-img" loading="lazy">
						<div class="gallery-card-overlay">
							<a href="images/gallery/wedding.jpg" class="image-popup gallery-zoom-btn" title="Grand Wedding Mandap Decoration" data-effect="mfp-with-zoom">
								<i class="fa fa-search-plus"></i>
							</a>
						</div>
					</div>
					<div class="gallery-card-body">
						<h4 class="gallery-card-title">Grand Wedding Mandap</h4>
						<div class="gallery-card-meta">
							<span class="gallery-badge">Wedding</span>
							<a href="services.php" class="text-primary font-weight-bold small">
								Book Setup <i class="fa fa-arrow-right ml-1"></i>
							</a>
						</div>
					</div>
				</div>
			</div>

			<!-- Item 2: Royal Wedding Stage -->
			<div class="col-lg-4 col-md-6 col-12 mb-4 mix wedding stage">
				<div class="gallery-card">
					<div class="gallery-card-img-wrap">
						<img src="images/gallery/wed1.jpg" alt="Royal Wedding Stage Setup" class="gallery-card-img" loading="lazy">
						<div class="gallery-card-overlay">
							<a href="images/gallery/wed1.jpg" class="image-popup gallery-zoom-btn" title="Royal Wedding Stage Setup" data-effect="mfp-with-zoom">
								<i class="fa fa-search-plus"></i>
							</a>
						</div>
					</div>
					<div class="gallery-card-body">
						<h4 class="gallery-card-title">Royal Wedding Stage</h4>
						<div class="gallery-card-meta">
							<span class="gallery-badge">Stage Decor</span>
							<a href="services.php" class="text-primary font-weight-bold small">
								Book Setup <i class="fa fa-arrow-right ml-1"></i>
							</a>
						</div>
					</div>
				</div>
			</div>

			<!-- Item 3: Haldi Ceremony Setup -->
			<div class="col-lg-4 col-md-6 col-12 mb-4 mix haldi">
				<div class="gallery-card">
					<div class="gallery-card-img-wrap">
						<img src="admin/uploads/haldhi1.jpg" alt="Vibrant Haldi Ceremony Decor" class="gallery-card-img" loading="lazy">
						<div class="gallery-card-overlay">
							<a href="admin/uploads/haldhi1.jpg" class="image-popup gallery-zoom-btn" title="Vibrant Haldi Ceremony Decor" data-effect="mfp-with-zoom">
								<i class="fa fa-search-plus"></i>
							</a>
						</div>
					</div>
					<div class="gallery-card-body">
						<h4 class="gallery-card-title">Vibrant Haldi Ceremony</h4>
						<div class="gallery-card-meta">
							<span class="gallery-badge">Haldi</span>
							<a href="services.php" class="text-primary font-weight-bold small">
								Book Setup <i class="fa fa-arrow-right ml-1"></i>
							</a>
						</div>
					</div>
				</div>
			</div>

			<!-- Item 4: Floral Wedding Backdrop -->
			<div class="col-lg-4 col-md-6 col-12 mb-4 mix wedding stage">
				<div class="gallery-card">
					<div class="gallery-card-img-wrap">
						<img src="images/gallery/wed3.jpg" alt="Floral Wedding Backdrop" class="gallery-card-img" loading="lazy">
						<div class="gallery-card-overlay">
							<a href="images/gallery/wed3.jpg" class="image-popup gallery-zoom-btn" title="Floral Wedding Backdrop" data-effect="mfp-with-zoom">
								<i class="fa fa-search-plus"></i>
							</a>
						</div>
					</div>
					<div class="gallery-card-body">
						<h4 class="gallery-card-title">Floral Wedding Backdrop</h4>
						<div class="gallery-card-meta">
							<span class="gallery-badge">Floral Decor</span>
							<a href="services.php" class="text-primary font-weight-bold small">
								Book Setup <i class="fa fa-arrow-right ml-1"></i>
							</a>
						</div>
					</div>
				</div>
			</div>

			<!-- Item 5: Traditional Haldi & Marigold Setup -->
			<div class="col-lg-4 col-md-6 col-12 mb-4 mix haldi">
				<div class="gallery-card">
					<div class="gallery-card-img-wrap">
						<img src="admin/uploads/haldhi2.jpg" alt="Traditional Haldi Backdrop" class="gallery-card-img" loading="lazy">
						<div class="gallery-card-overlay">
							<a href="admin/uploads/haldhi2.jpg" class="image-popup gallery-zoom-btn" title="Traditional Haldi Backdrop" data-effect="mfp-with-zoom">
								<i class="fa fa-search-plus"></i>
							</a>
						</div>
					</div>
					<div class="gallery-card-body">
						<h4 class="gallery-card-title">Traditional Haldi Backdrop</h4>
						<div class="gallery-card-meta">
							<span class="gallery-badge">Haldi & Mehendi</span>
							<a href="services.php" class="text-primary font-weight-bold small">
								Book Setup <i class="fa fa-arrow-right ml-1"></i>
							</a>
						</div>
					</div>
				</div>
			</div>

			<!-- Item 6: Birthday Celebration Theme -->
			<div class="col-lg-4 col-md-6 col-12 mb-4 mix birthday">
				<div class="gallery-card">
					<div class="gallery-card-img-wrap">
						<img src="admin/uploads/birthday.jpg" alt="Birthday Party Decoration" class="gallery-card-img" loading="lazy">
						<div class="gallery-card-overlay">
							<a href="admin/uploads/birthday.jpg" class="image-popup gallery-zoom-btn" title="Birthday Party Decoration" data-effect="mfp-with-zoom">
								<i class="fa fa-search-plus"></i>
							</a>
						</div>
					</div>
					<div class="gallery-card-body">
						<h4 class="gallery-card-title">Theme Birthday Party</h4>
						<div class="gallery-card-meta">
							<span class="gallery-badge">Birthday</span>
							<a href="services.php" class="text-primary font-weight-bold small">
								Book Setup <i class="fa fa-arrow-right ml-1"></i>
							</a>
						</div>
					</div>
				</div>
			</div>

			<!-- Item 7: Reception Floral Stage -->
			<div class="col-lg-4 col-md-6 col-12 mb-4 mix stage">
				<div class="gallery-card">
					<div class="gallery-card-img-wrap">
						<img src="images/gallery/th.jpg" alt="Reception Floral Stage" class="gallery-card-img" loading="lazy">
						<div class="gallery-card-overlay">
							<a href="images/gallery/th.jpg" class="image-popup gallery-zoom-btn" title="Reception Floral Stage" data-effect="mfp-with-zoom">
								<i class="fa fa-search-plus"></i>
							</a>
						</div>
					</div>
					<div class="gallery-card-body">
						<h4 class="gallery-card-title">Reception Floral Stage</h4>
						<div class="gallery-card-meta">
							<span class="gallery-badge">Reception</span>
							<a href="services.php" class="text-primary font-weight-bold small">
								Book Setup <i class="fa fa-arrow-right ml-1"></i>
							</a>
						</div>
					</div>
				</div>
			</div>

			<!-- Item 8: Traditional Entrance Arch -->
			<div class="col-lg-4 col-md-6 col-12 mb-4 mix wedding haldi">
				<div class="gallery-card">
					<div class="gallery-card-img-wrap">
						<img src="images/gallery/th (2).jpeg" alt="Traditional Welcome Entrance Arch" class="gallery-card-img" loading="lazy">
						<div class="gallery-card-overlay">
							<a href="images/gallery/th (2).jpeg" class="image-popup gallery-zoom-btn" title="Traditional Welcome Entrance Arch" data-effect="mfp-with-zoom">
								<i class="fa fa-search-plus"></i>
							</a>
						</div>
					</div>
					<div class="gallery-card-body">
						<h4 class="gallery-card-title">Traditional Entrance Arch</h4>
						<div class="gallery-card-meta">
							<span class="gallery-badge">Entrance</span>
							<a href="services.php" class="text-primary font-weight-bold small">
								Book Setup <i class="fa fa-arrow-right ml-1"></i>
							</a>
						</div>
					</div>
				</div>
			</div>

			<!-- Item 9: Destination Wedding Canopy -->
			<div class="col-lg-4 col-md-6 col-12 mb-4 mix wedding">
				<div class="gallery-card">
					<div class="gallery-card-img-wrap">
						<img src="images/gallery/wed4.jpg" alt="Destination Wedding Canopy" class="gallery-card-img" loading="lazy">
						<div class="gallery-card-overlay">
							<a href="images/gallery/wed4.jpg" class="image-popup gallery-zoom-btn" title="Destination Wedding Canopy" data-effect="mfp-with-zoom">
								<i class="fa fa-search-plus"></i>
							</a>
						</div>
					</div>
					<div class="gallery-card-body">
						<h4 class="gallery-card-title">Destination Wedding Canopy</h4>
						<div class="gallery-card-meta">
							<span class="gallery-badge">Wedding</span>
							<a href="services.php" class="text-primary font-weight-bold small">
								Book Setup <i class="fa fa-arrow-right ml-1"></i>
							</a>
						</div>
					</div>
				</div>
			</div>

			<!-- Item 10: Balloon & Party Celebration -->
			<div class="col-lg-4 col-md-6 col-12 mb-4 mix birthday">
				<div class="gallery-card">
					<div class="gallery-card-img-wrap">
						<img src="images/gallery/images.jpeg" alt="Party Balloon Decor" class="gallery-card-img" loading="lazy">
						<div class="gallery-card-overlay">
							<a href="images/gallery/images.jpeg" class="image-popup gallery-zoom-btn" title="Party Balloon Decor" data-effect="mfp-with-zoom">
								<i class="fa fa-search-plus"></i>
							</a>
						</div>
					</div>
					<div class="gallery-card-body">
						<h4 class="gallery-card-title">Party Balloon Decor</h4>
						<div class="gallery-card-meta">
							<span class="gallery-badge">Party Decor</span>
							<a href="services.php" class="text-primary font-weight-bold small">
								Book Setup <i class="fa fa-arrow-right ml-1"></i>
							</a>
						</div>
					</div>
				</div>
			</div>

			<!-- Item 11: Elegant Banquet Stage -->
			<div class="col-lg-4 col-md-6 col-12 mb-4 mix stage">
				<div class="gallery-card">
					<div class="gallery-card-img-wrap">
						<img src="images/gallery/mm.jpg" alt="Elegant Banquet Stage Setup" class="gallery-card-img" loading="lazy">
						<div class="gallery-card-overlay">
							<a href="images/gallery/mm.jpg" class="image-popup gallery-zoom-btn" title="Elegant Banquet Stage Setup" data-effect="mfp-with-zoom">
								<i class="fa fa-search-plus"></i>
							</a>
						</div>
					</div>
					<div class="gallery-card-body">
						<h4 class="gallery-card-title">Elegant Banquet Stage</h4>
						<div class="gallery-card-meta">
							<span class="gallery-badge">Banquet Setup</span>
							<a href="services.php" class="text-primary font-weight-bold small">
								Book Setup <i class="fa fa-arrow-right ml-1"></i>
							</a>
						</div>
					</div>
				</div>
			</div>

			<!-- Item 12: Event Gathering & Hall Decor -->
			<div class="col-lg-4 col-md-6 col-12 mb-4 mix stage">
				<div class="gallery-card">
					<div class="gallery-card-img-wrap">
						<img src="admin/uploads/colg.jpg" alt="Event Gathering & Hall Setup" class="gallery-card-img" loading="lazy">
						<div class="gallery-card-overlay">
							<a href="admin/uploads/colg.jpg" class="image-popup gallery-zoom-btn" title="Event Gathering & Hall Setup" data-effect="mfp-with-zoom">
								<i class="fa fa-search-plus"></i>
							</a>
						</div>
					</div>
					<div class="gallery-card-body">
						<h4 class="gallery-card-title">Event Gathering & Hall</h4>
						<div class="gallery-card-meta">
							<span class="gallery-badge">Hall Setup</span>
							<a href="services.php" class="text-primary font-weight-bold small">
								Book Setup <i class="fa fa-arrow-right ml-1"></i>
							</a>
						</div>
					</div>
				</div>
			</div>

		</div>
	</div>
</section>

<!--====  End of Gallery  ====-->


<!--==============================================
=            Call to Action Section             =
===============================================-->

<section class="cta-subscribe bg-subscribe overlay-dark">
	<div class="container">
		<div class="row align-items-center">
			<div class="col-lg-7 col-md-12 text-center text-lg-left mb-4 mb-lg-0">
				<div class="content">
					<h3 class="text-white font-weight-bold">Love Our Decoration Designs?</h3>
					<p class="text-white-50 mb-0">Plan your wedding, haldi, birthday, or corporate event with Samruddhi Decorators today.</p>
				</div>
			</div>
			<div class="col-lg-5 col-md-12 text-center text-lg-right">
				<a href="services.php" class="btn btn-main-md mr-2 mb-2 mb-sm-0">
					<i class="fa fa-calendar-check-o mr-2"></i> Book A Service
				</a>
				<a href="contact.php" class="btn btn-white-md">
					<i class="fa fa-envelope-o mr-2"></i> Contact Us
				</a>
			</div>
		</div>
	</div>
</section>

<!--====  End of Call to Action  ====-->


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
		<a href="contact.php" class="btn btn-white-md">Get Direction</a>
	</div>
</section>

<!--====  End of Google Map  ====-->

<?php include 'footer.php'; ?>
