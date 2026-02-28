<?php $siteSettings = getSettings(); ?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <script>(function(){var t=localStorage.getItem('theme')||'light';document.documentElement.setAttribute('data-theme',t);})()</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? htmlspecialchars($siteSettings['school_name']) . ' - Student Portal'; ?></title>

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
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
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
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#studentNavbarNav">
                <span class="navbar-toggler-icon"></span>
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
