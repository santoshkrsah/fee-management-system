<?php $siteSettings = getSettings(); ?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <script>(function(){var t=localStorage.getItem('theme')||'light';document.documentElement.setAttribute('data-theme',t);})()</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? htmlspecialchars($siteSettings['school_name']) . ' - Student Portal'; ?></title>

    <!-- Favicon -->
    <?php
    $siteIconHref = (!empty($siteSettings['site_icon']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $siteSettings['site_icon']))
        ? '/' . htmlspecialchars($siteSettings['site_icon'])
        : '/assets/images/favicon.svg';
    ?>
    <link rel="icon" href="<?php echo $siteIconHref; ?>">
    <link rel="shortcut icon" href="<?php echo $siteIconHref; ?>">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="/assets/css/style.css" rel="stylesheet">
    <link href="/assets/css/student.css" rel="stylesheet">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
</head>
<body>
    <?php if(isStudentLoggedIn() && basename($_SERVER['PHP_SELF']) != 'login.php'): ?>
    <!-- Student Portal Navigation Bar -->
    <nav class="navbar navbar-expand-md navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="/student/dashboard.php">
                <?php if (!empty($siteSettings['school_logo']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $siteSettings['school_logo'])): ?>
                    <img src="/<?php echo htmlspecialchars($siteSettings['school_logo']); ?>" alt="Logo" style="height: 22px; margin-right: 6px; vertical-align: middle;">
                <?php else: ?>
                    <i class="fas fa-school"></i>
                <?php endif; ?>
                <?php echo htmlspecialchars($siteSettings['school_name']); ?>
                <span class="student-portal-label">Student Portal</span>
            </a>
            <button class="navbar-toggler hamburger-menu" type="button" data-bs-toggle="offcanvas" data-bs-target="#studentMobileSidebar" aria-controls="studentMobileSidebar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="hamburger-bar"></span>
                <span class="hamburger-bar"></span>
                <span class="hamburger-bar"></span>
            </button>
            <div class="collapse navbar-collapse" id="studentNavbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/student/dashboard.php">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/student/receipts.php">
                            <i class="fas fa-receipt"></i> My Receipts
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/student/my_upi_payments.php">
                            <i class="fas fa-qrcode"></i> UPI Payments
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav align-items-center">
                    <li class="nav-item me-2">
                        <button class="btn nav-link" id="darkModeToggle" title="Toggle Dark Mode" style="border:none;background:none;">
                            <i class="fas fa-moon" id="darkModeIcon"></i>
                        </button>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="studentUserDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-graduate"></i> <?php echo htmlspecialchars(getStudentName()); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="/student/change_password.php"><i class="fas fa-key"></i> Change Password</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/student/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <?php endif; ?>

    <!-- Main Content -->
    <div class="<?php echo (isStudentLoggedIn() && basename($_SERVER['PHP_SELF']) != 'login.php') ? 'container-fluid mt-4' : ''; ?>">
        <?php
        // Display flash messages
        $flashMessage = getFlashMessage();
        if ($flashMessage):
        ?>
        <div class="alert alert-<?php echo $flashMessage['type']; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($flashMessage['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

<?php if(isStudentLoggedIn() && basename($_SERVER['PHP_SELF']) != 'login.php'): ?>
<!-- ── Student Mobile Sidebar ────────────────────────────────────── -->
<div class="offcanvas offcanvas-start mobile-side-nav" tabindex="-1" id="studentMobileSidebar" aria-labelledby="studentMobileSidebarLabel">
    <div class="offcanvas-header">
        <div class="d-flex align-items-center gap-2 overflow-hidden">
            <?php if (!empty($siteSettings['school_logo']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $siteSettings['school_logo'])): ?>
                <img src="/<?php echo htmlspecialchars($siteSettings['school_logo']); ?>" alt="Logo" style="height:24px;flex-shrink:0;">
            <?php else: ?>
                <i class="fas fa-school" style="color:#fff;font-size:1.1rem;flex-shrink:0;"></i>
            <?php endif; ?>
            <div id="studentMobileSidebarLabel" class="overflow-hidden">
                <div style="font-size:0.85rem;font-weight:700;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo htmlspecialchars($siteSettings['school_name']); ?></div>
                <div style="font-size:0.7rem;color:rgba(255,255,255,0.8);">Student Portal</div>
            </div>
        </div>
        <button type="button" class="btn-close btn-close-white ms-1" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body p-0">
        <!-- User info strip -->
        <div class="sidebar-user">
            <i class="fas fa-user-graduate fa-2x"></i>
            <div>
                <div class="fw-semibold"><?php echo htmlspecialchars(getStudentName()); ?></div>
                <small>Student</small>
            </div>
        </div>
        <!-- Nav links -->
        <nav class="sidebar-nav">
            <a class="sidebar-link <?php echo (basename($_SERVER['PHP_SELF']) === 'dashboard.php') ? 'active' : ''; ?>" href="/student/dashboard.php">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a class="sidebar-link <?php echo (basename($_SERVER['PHP_SELF']) === 'receipts.php') ? 'active' : ''; ?>" href="/student/receipts.php">
                <i class="fas fa-receipt"></i> My Receipts
            </a>
            <a class="sidebar-link <?php echo (basename($_SERVER['PHP_SELF']) === 'my_upi_payments.php') ? 'active' : ''; ?>" href="/student/my_upi_payments.php">
                <i class="fas fa-qrcode"></i> UPI Payments
            </a>
        </nav>
        <!-- Footer actions -->
        <div class="sidebar-footer">
            <button class="sidebar-link w-100 text-start border-0 bg-transparent" onclick="document.getElementById('darkModeToggle').click()">
                <i class="fas fa-circle-half-stroke"></i> Dark Mode
            </button>
            <a class="sidebar-link" href="/student/change_password.php"><i class="fas fa-key"></i> Change Password</a>
            <a class="sidebar-link sidebar-logout" href="/student/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
</div>

<!-- Hamburger Animation Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const studentSidebar = document.getElementById('studentMobileSidebar');
    const hamburger = document.querySelector('[data-bs-target="#studentMobileSidebar"]');

    if (studentSidebar && hamburger) {
        studentSidebar.addEventListener('show.bs.offcanvas', function() {
            hamburger.classList.add('active');
        });

        studentSidebar.addEventListener('hidden.bs.offcanvas', function() {
            hamburger.classList.remove('active');
        });
    }
});
</script>
<?php endif; ?>
