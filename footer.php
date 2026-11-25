
<!-- =========================
     MAIN FOOTER
========================= -->

<footer class="footer-main">
    <div class="container">

        <div class="row">

            <div class="col-lg-4 col-md-6 mb-4">
                <div class="footer-about">

                    <a href="index.php" class="footer-logo">
                        <img
                            src="/samruddhi_decorators/admin/assets/images/Logo.png"
                            alt="Samruddhi Decorators Logo"
                            class="img-fluid"
                        >
                    </a>

                    <p>
                        Samruddhi Decorators makes your special occasions
                        beautiful and memorable with creative decoration
                        ideas and personalized services.
                    </p>

                </div>
            </div>

            <div class="col-lg-4 col-md-6 mb-4">
                <div class="footer-links">

                    <h4>Quick Links</h4>

                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="services.php">Our Services</a></li>
                        <li><a href="gallery.php">Gallery</a></li>
                        <li><a href="about-us.php">About Us</a></li>
                        <li><a href="contact.php">Contact Us</a></li>
                    </ul>

                </div>
            </div>

            <div class="col-lg-4 col-md-12 mb-4">
                <div class="footer-contact">

                    <h4>Get In Touch</h4>

                    <p>
                        Have an event coming up? Contact us to discuss
                        your decoration requirements.
                    </p>

                    <a href="contact.php" class="footer-contact-btn">
                        Contact Us
                        <i class="fa fa-arrow-right ml-2"></i>
                    </a>

                </div>
            </div>

        </div>

        <div class="footer-social text-center">

            <h5>Follow Samruddhi Decorators</h5>

            <!-- Add your actual social media profile links here
                 when they are available. -->

            <div class="social-links-footer">
                <span aria-label="Facebook">
                    <i class="fa fa-facebook"></i>
                </span>

                <span aria-label="Instagram">
                    <i class="fa fa-instagram"></i>
                </span>

                <span aria-label="Twitter">
                    <i class="fa fa-twitter"></i>
                </span>
            </div>

        </div>

    </div>
</footer>


<!-- =========================
     COPYRIGHT FOOTER
========================= -->

<footer class="subfooter">

    <div class="container">

        <div class="row align-items-center">

            <div class="col-md-12 text-center">

                <div class="copyright-text">

                    <p>
                        &copy; <?php echo date('Y'); ?>

                        <a href="index.php">
                            Samruddhi Decorators
                        </a>.

                        All Rights Reserved.

                        <br>

                        Designed by Manasi Nagavekar.
                    </p>

                </div>

            </div>

        </div>

    </div>

</footer>


<!-- =========================
     BACK TO TOP BUTTON
========================= -->

<button
    type="button"
    class="to-top"
    id="backToTop"
    aria-label="Back to top"
>
    <i class="fa fa-angle-up"></i>
</button>


<!-- =========================
     JAVASCRIPTS
========================= -->

<!-- jQuery: Must load before Bootstrap 4 -->
<script src="plugins/jquery/jquery.js"></script>

<!-- Popper JS -->
<script src="plugins/popper/popper.min.js"></script>

<!-- Bootstrap 4 -->
<script src="plugins/bootstrap/js/bootstrap.min.js"></script>

<!-- Smooth Scroll -->
<script src="plugins/smoothscroll/SmoothScroll.min.js"></script>

<!-- Isotope / Mixitup -->
<script src="plugins/isotope/mixitup.min.js"></script>

<!-- Magnific Popup -->
<script src="plugins/magnific-popup/jquery.magnific-popup.min.js"></script>

<!-- Slick Carousel -->
<script src="plugins/slick/slick.min.js"></script>

<!-- SyoTimer -->
<script src="plugins/syotimer/jquery.syotimer.min.js"></script>

<!-- Custom Script -->
<script src="js/custom.js"></script>


<!-- Back To Top Script -->
<script>
document.addEventListener('DOMContentLoaded', function () {

    const backToTop = document.getElementById('backToTop');

    if (backToTop) {

        window.addEventListener('scroll', function () {

            if (window.scrollY > 300) {
                backToTop.classList.add('show');
            } else {
                backToTop.classList.remove('show');
            }

        });

        backToTop.addEventListener('click', function () {

            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });

        });

    }

});
</script>

</body>
</html>
