
<?php
// Active menu page check
if (!function_exists('setActive')) {
    function setActive($url) {
        $activePage = basename($_SERVER['SCRIPT_NAME']);

        if ($activePage === $url) {
            echo 'active';
        }
    }
}

// Login status
$isLoggedIn = isset($_SESSION['userid']) && !empty($_SESSION['userid']);
$userName = $isLoggedIn && isset($_SESSION['name'])
    ? $_SESSION['name']
    : 'User';
?>

<!-- Navigation Section -->
<nav class="navbar main-nav border-less fixed-top navbar-expand-lg p-0">

    <div class="container-fluid px-4">

        <!-- Website Logo -->
        <a class="navbar-brand" href="index.php">
            <img
                src="/samruddhi_decorators/admin/assets/images/Logo.png"
                alt="Samruddhi Decorators Logo"
            >
        </a>

        <!-- Mobile Menu Button -->
        <button
            class="navbar-toggler"
            type="button"
            data-toggle="collapse"
            data-target="#navbarNav"
            aria-controls="navbarNav"
            aria-expanded="false"
            aria-label="Toggle navigation"
        >
            <span class="fa fa-bars"></span>
        </button>

        <!-- Navigation Menu -->
        <div class="collapse navbar-collapse" id="navbarNav">

            <!-- Main Menu -->
            <ul class="navbar-nav mx-auto align-items-center">

                <li class="nav-item <?php setActive('index.php'); ?>">
                    <a class="nav-link" href="index.php">
                        Home
                    </a>
                </li>

                <li class="nav-item <?php setActive('services.php'); ?>">
                    <a class="nav-link" href="services.php">
                        Services
                    </a>
                </li>

                <li class="nav-item <?php setActive('gallery.php'); ?>">
                    <a class="nav-link" href="gallery.php">
                        Gallery
                    </a>
                </li>

                <li class="nav-item <?php setActive('about-us.php'); ?>">
                    <a class="nav-link" href="about-us.php">
                        About Us
                    </a>
                </li>

                <li class="nav-item <?php setActive('contact.php'); ?>">
                    <a class="nav-link" href="contact.php">
                        Contact
                    </a>
                </li>

            </ul>

            <!-- Right Side Profile / Login -->
            <ul class="navbar-nav align-items-center ml-auto">

                <?php if ($isLoggedIn) { ?>

                    <li class="nav-item dropdown dropdown-slide">

                        <a
                            class="nav-link dropdown-toggle profile-link"
                            href="#"
                            id="profileDropdown"
                            role="button"
                            data-toggle="dropdown"
                            aria-haspopup="true"
                            aria-expanded="false"
                        >

                            <i class="fa fa-user-circle profile-icon"></i>

                            <span class="profile-name">
                                <?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?>
                            </span>

                        </a>

                        <div
                            class="dropdown-menu dropdown-menu-right profile-dropdown"
                            aria-labelledby="profileDropdown"
                        >

                            <div class="dropdown-header">
                                <i class="fa fa-user-circle mr-2"></i>
                                My Account
                            </div>

                            <a
                                class="dropdown-item <?php setActive('mystatus.php'); ?>"
                                href="mystatus.php"
                            >
                                <i class="fa fa-list-alt mr-2"></i>
                                My Status
                            </a>

                            <div class="dropdown-divider"></div>

                            <a class="dropdown-item logout-link"
                               href="logout.php"
                               onclick="return confirm('Are you sure you want to logout?');">

                                <i class="fa fa-sign-out mr-2"></i>
                                Logout

                            </a>

                        </div>

                    </li>

                <?php } else { ?>

                    <li class="nav-item <?php setActive('login.php'); ?>">

                        <a class="nav-link btn-nav-login" href="login.php">
                            <i class="fa fa-sign-in mr-1"></i>
                            Login
                        </a>

                    </li>

                    <li class="nav-item <?php setActive('register.php'); ?>">

                        <a class="nav-link btn-nav-register" href="register.php">
                            Register
                        </a>

                    </li>

                <?php } ?>

            </ul>

        </div>

    </div>

</nav>

