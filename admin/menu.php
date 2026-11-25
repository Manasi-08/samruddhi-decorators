<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$adminName = isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'Admin';
?>
<!-- Sidebar Navigation -->
<aside id="sidebar">
    <div class="sidebar-header">
        <a href="index.php" class="sidebar-brand">
            <div class="sidebar-brand-icon">
                <i class="fa fa-paint-brush"></i>
            </div>
            <div class="brand-text">Samruddhi Admin</div>
        </a>
    </div>

    <div class="sidebar-menu">
        <div class="menu-category">Main Menu</div>
        <ul class="p-0 m-0">
            <li class="nav-item">
                <a href="index.php" class="nav-link-custom <?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>">
                    <i class="fa fa-th-large menu-icon"></i>
                    <span class="menu-text">Dashboard</span>
                </a>
            </li>

            <div class="menu-category">Management</div>
            
            <li class="nav-item <?php echo (in_array($currentPage, ['add_services.php', 'view_services.php', 'edit_services.php'])) ? 'open' : ''; ?>">
                <a href="javascript:void(0)" class="nav-link-custom sidebar-dropdown-toggle <?php echo (in_array($currentPage, ['add_services.php', 'view_services.php', 'edit_services.php'])) ? 'active' : ''; ?>">
                    <i class="fa fa-cubes menu-icon"></i>
                    <span class="menu-text">Services</span>
                    <i class="fa fa-chevron-right arrow-icon"></i>
                </a>
                <ul class="sidebar-submenu">
                    <li>
                        <a href="add_services.php" class="submenu-link <?php echo ($currentPage == 'add_services.php') ? 'active' : ''; ?>">
                            <i class="fa fa-plus me-1"></i> Add Service
                        </a>
                    </li>
                    <li>
                        <a href="view_services.php" class="submenu-link <?php echo (in_array($currentPage, ['view_services.php', 'edit_services.php'])) ? 'active' : ''; ?>">
                            <i class="fa fa-list me-1"></i> View Services
                        </a>
                    </li>
                </ul>
            </li>

            <li class="nav-item <?php echo (in_array($currentPage, ['view_booking.php', 'view_payments.php', 'view_contact.php'])) ? 'open' : ''; ?>">
                <a href="javascript:void(0)" class="nav-link-custom sidebar-dropdown-toggle <?php echo (in_array($currentPage, ['view_booking.php', 'view_payments.php', 'view_contact.php'])) ? 'active' : ''; ?>">
                    <i class="fa fa-calendar-check-o menu-icon"></i>
                    <span class="menu-text">Bookings & Finance</span>
                    <i class="fa fa-chevron-right arrow-icon"></i>
                </a>
                <ul class="sidebar-submenu">
                    <li>
                        <a href="view_booking.php" class="submenu-link <?php echo ($currentPage == 'view_booking.php') ? 'active' : ''; ?>">
                            <i class="fa fa-ticket me-1"></i> View Bookings
                        </a>
                    </li>
                    <li>
                        <a href="view_payments.php" class="submenu-link <?php echo ($currentPage == 'view_payments.php') ? 'active' : ''; ?>">
                            <i class="fa fa-credit-card me-1"></i> Payment Ledger
                        </a>
                    </li>
                    <li>
                        <a href="view_contact.php" class="submenu-link <?php echo ($currentPage == 'view_contact.php') ? 'active' : ''; ?>">
                            <i class="fa fa-envelope-o me-1"></i> View Inquiries
                        </a>
                    </li>
                </ul>
            </li>

            <li class="nav-item">
                <a href="view_users.php" class="nav-link-custom <?php echo ($currentPage == 'view_users.php') ? 'active' : ''; ?>">
                    <i class="fa fa-users menu-icon"></i>
                    <span class="menu-text">Customers</span>
                </a>
            </li>
        </ul>

        <div class="menu-category">Account</div>
        <ul class="p-0 m-0">
            <li class="nav-item">
                <a href="../index.php" target="_blank" class="nav-link-custom">
                    <i class="fa fa-globe menu-icon"></i>
                    <span class="menu-text">Visit Website</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="logout.php" class="nav-link-custom text-danger">
                    <i class="fa fa-power-off menu-icon text-danger"></i>
                    <span class="menu-text">Logout</span>
                </a>
            </li>
        </ul>
    </div>
</aside>

<!-- Main Container -->
<div id="main-container">
    <!-- Top Navbar -->
    <header class="top-navbar">
        <div class="topbar-left">
            <button type="button" class="toggle-sidebar-btn" id="sidebarToggle" title="Toggle Sidebar">
                <i class="fa fa-bars"></i>
            </button>
            <div class="topbar-search d-none d-md-block">
                <i class="fa fa-search"></i>
                <input type="text" placeholder="Search services, bookings, inquiries..." class="form-control">
            </div>
        </div>

        <div class="topbar-right">
            <!-- Notifications Dropdown -->
            <div class="dropdown">
                <div class="topbar-icon-btn" data-bs-toggle="dropdown" aria-expanded="false" title="Notifications">
                    <i class="fa fa-bell-o"></i>
                    <span class="badge-dot"></span>
                </div>
                <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-3 p-2 mt-2" style="width: 300px;">
                    <li class="dropdown-header font-weight-bold text-dark border-bottom pb-2 mb-2">Notifications</li>
                    <li>
                        <a class="dropdown-item rounded-2 py-2 d-flex align-items-center gap-2" href="view_booking.php">
                            <span class="badge bg-success p-2 rounded-circle"><i class="fa fa-calendar"></i></span>
                            <div>
                                <div class="fw-semibold text-dark" style="font-size: 0.85rem;">New Booking Received</div>
                                <small class="text-muted">Check latest event bookings</small>
                            </div>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item rounded-2 py-2 d-flex align-items-center gap-2" href="view_contact.php">
                            <span class="badge bg-primary p-2 rounded-circle"><i class="fa fa-envelope"></i></span>
                            <div>
                                <div class="fw-semibold text-dark" style="font-size: 0.85rem;">New Contact Message</div>
                                <small class="text-muted">Inquiry waiting for response</small>
                            </div>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- User Profile Dropdown -->
            <div class="dropdown">
                <div class="user-profile-menu" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($adminName, 0, 1)); ?>
                    </div>
                    <div class="user-info d-none d-sm-flex">
                        <span class="user-name"><?php echo $adminName; ?></span>
                        <span class="user-role">Administrator</span>
                    </div>
                    <i class="fa fa-angle-down text-muted ms-1"></i>
                </div>
                <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-3 p-2 mt-2" style="min-width: 200px;">
                    <li class="px-3 py-2 border-bottom mb-2">
                        <div class="fw-bold text-dark"><?php echo $adminName; ?></div>
                        <small class="text-muted">admin@samruddhi.com</small>
                    </li>
                    <li>
                        <a class="dropdown-item rounded-2 py-2 text-danger fw-semibold" href="logout.php">
                            <i class="fa fa-sign-out me-2"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <div class="content-wrapper">
