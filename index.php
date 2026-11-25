
<?php
// Session ko safely start karein
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Login status check
$isLoggedIn = isset($_SESSION['userid']) && !empty($_SESSION['userid']);

// Booking page
$bookingPage = 'services.php';
?>

<?php include 'header.php'; ?>

<?php include 'menu.php'; ?>

<!-- =========================
     HOME BANNER SECTION
========================= -->

<section class="banner bg-banner-one overlay">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 text-center">

                <div class="welcome">
                    <h1>Welcome To</h1>
                </div>

                <div class="block">
                    <h1>SAMRUDDHI</h1>
                    <h2>DECORATORS</h2>

                    <p>
                        Making Your Special Moments Beautiful and Memorable
                    </p>

                    <ul class="list-inline">
                        <li class="list-inline-item">
                            <a href="<?php echo htmlspecialchars($bookingPage, ENT_QUOTES, 'UTF-8'); ?>"
                               class="btn btn-transparent-md">
                                Book Now
                            </a>
                        </li>
                    </ul>

                </div>

            </div>
        </div>
    </div>
</section>

<!-- =========================
     ABOUT SECTION
========================= -->

<section class="section about" id="about">
    <div class="container">

        <div class="row">

            <div class="col-lg-4 col-md-6 align-self-center">
                <div class="image-block bg-about">

                    <img
                        class="img-fluid"
                        src="images/background/images (1).jpeg"
                        alt="Samruddhi Decorators event decoration"
                        loading="lazy"
                    >

                </div>
            </div>

            <div class="col-lg-8 col-md-6 align-self-center">

                <div class="content-block">

                    <h2>
                        About <span class="alternate">Us</span>
                    </h2>

                    <div class="description-one">

                        <p>
                            At SAMRUDDHI DECORATORS, we're passionate about
                            helping you create unforgettable events and
                            celebrations. Our team of expert decorators
                            and event designers work tirelessly to bring
                            your vision to life.
                        </p>

                        <p>
                            Our mission is to deliver outstanding
                            decoration services that exceed our clients'
                            expectations. We build long-lasting
                            relationships by understanding your needs
                            and providing personalized decoration
                            solutions that match your style and budget.
                        </p>

                        <a href="services.php" class="btn btn-main-md">
                            Explore Our Services
                        </a>

                    </div>

                </div>

            </div>

        </div>

    </div>
</section>

<!-- =========================
     GALLERY SECTION
========================= -->

<section class="section gallery" id="gallery">

    <div class="container">

        <div class="row">
            <div class="col-12 text-center">

                <div class="section-title">
                    <h2>Our <span class="alternate">Gallery</span></h2>

                    <p>
                        Take a look at some of our beautiful decoration
                        work and memorable celebrations.
                    </p>
                </div>

            </div>
        </div>

        <div class="row gallery-wrapper">

            <!-- Gallery Image 1 -->
            <div class="col-lg-4 col-md-6 gallery-item mix meeting">
                <div class="image-block">
                    <div class="image">

                        <img
                            src="images/gallery/th (2).jpeg"
                            alt="Event decoration gallery image 1"
                            class="img-fluid"
                            loading="lazy"
                        >

                        <div class="primary-overlay">
                            <a class="image-popup"
                               data-effect="mfp-with-zoom"
                               href="images/gallery/th (2).jpeg"
                               aria-label="View event decoration image 1">
                                <i class="fa fa-picture-o"></i>
                            </a>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Gallery Image 2 -->
            <div class="col-lg-4 col-md-6 gallery-item mix party events">
                <div class="image-block">
                    <div class="image">

                        <img
                            src="images/gallery/images.jpeg"
                            alt="Party decoration gallery image"
                            class="img-fluid"
                            loading="lazy"
                        >

                        <div class="primary-overlay">
                            <a class="image-popup"
                               data-effect="mfp-with-zoom"
                               href="images/gallery/images.jpeg"
                               aria-label="View party decoration image">
                                <i class="fa fa-picture-o"></i>
                            </a>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Gallery Image 3 -->
            <div class="col-lg-4 col-md-6 gallery-item mix meeting party">
                <div class="image-block">
                    <div class="image">

                        <img
                            src="images/gallery/wed3.jpg"
                            alt="Wedding decoration gallery image 1"
                            class="img-fluid"
                            loading="lazy"
                        >

                        <div class="primary-overlay">
                            <a class="image-popup"
                               data-effect="mfp-with-zoom"
                               href="images/gallery/wed3.jpg"
                               aria-label="View wedding decoration image 1">
                                <i class="fa fa-picture-o"></i>
                            </a>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Gallery Image 4 -->
            <div class="col-lg-4 col-md-6 gallery-item mix meeting concert">
                <div class="image-block">
                    <div class="image">

                        <img
                            src="images/gallery/wed1.jpg"
                            alt="Wedding decoration gallery image 2"
                            class="img-fluid"
                            loading="lazy"
                        >

                        <div class="primary-overlay">
                            <a class="image-popup"
                               data-effect="mfp-with-zoom"
                               href="images/gallery/wed1.jpg"
                               aria-label="View wedding decoration image 2">
                                <i class="fa fa-picture-o"></i>
                            </a>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Gallery Image 5 -->
            <div class="col-lg-4 col-md-6 gallery-item mix concert party">
                <div class="image-block">
                    <div class="image">

                        <img
                            src="images/gallery/wed4.jpg"
                            alt="Wedding decoration gallery image 3"
                            class="img-fluid"
                            loading="lazy"
                        >

                        <div class="primary-overlay">
                            <a class="image-popup"
                               data-effect="mfp-with-zoom"
                               href="images/gallery/wed4.jpg"
                               aria-label="View wedding decoration image 3">
                                <i class="fa fa-picture-o"></i>
                            </a>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Gallery Image 6 -->
            <div class="col-lg-4 col-md-6 gallery-item mix events conference">
                <div class="image-block">
                    <div class="image">

                        <img
                            src="images/gallery/th.jpg"
                            alt="Event decoration gallery image 2"
                            class="img-fluid"
                            loading="lazy"
                        >

                        <div class="primary-overlay">
                            <a class="image-popup"
                               data-effect="mfp-with-zoom"
                               href="images/gallery/th.jpg"
                               aria-label="View event decoration image 2">
                                <i class="fa fa-picture-o"></i>
                            </a>
                        </div>

                    </div>
                </div>
            </div>

        </div>

    </div>

</section>

<?php include 'footer.php'; ?>
