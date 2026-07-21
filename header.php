<?php
require_once 'config.php';
requireLogin();
$currentPage = basename($_SERVER['PHP_SELF']);
$app_name = getAppName();
$app_logo = getAppLogo();
$current_user = $_SESSION['username'] ?? 'User';

?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($app_name) ?> Management</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/design-system.css?v=<?= time() ?>">
</head>
<body>
    <div id="app-wrapper">
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <aside id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <?php if ($app_logo): ?>
                        <img src="<?= htmlspecialchars($app_logo) ?>" alt="Logo" style="max-width:100%; max-height:100%; object-fit:contain;">
                    <?php else: ?>
                        IMS
                    <?php endif; ?>
                </div>
                <div class="sidebar-title">
                    <span class="name"><?= htmlspecialchars($app_name) ?></span>
                    <span class="subtitle">Administration</span>
                </div>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Main</div>
                    <a href="index.php" class="nav-link <?= $currentPage=='index.php' ? 'active' : '' ?>">
                        <i class="fas fa-chart-pie"></i><span>Dashboard</span>
                    </a>
                </div>
                <div class="nav-section<?= in_array($currentPage, ['teachers.php','teacher_create.php','teacher_edit.php','students.php']) ? '' : ' collapsed' ?>">
                    <button class="nav-section-title" data-toggle="collapse">
                        <span>User Management</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="nav-section-content">
                        <a href="teachers.php" class="nav-link <?= in_array($currentPage, ['teachers.php','teacher_create.php','teacher_edit.php']) ? 'active' : '' ?>">
                            <i class="fas fa-user-tie"></i><span>Teachers</span>
                        </a>
                        <a href="students.php" class="nav-link <?= $currentPage=='students.php' ? 'active' : '' ?>">
                            <i class="fas fa-user-graduate"></i><span>Students</span>
                        </a>
                    </div>
                </div>
                <div class="nav-section<?= in_array($currentPage, ['classes.php','class_create.php','class_edit.php','class_view.php','subjects.php','teacher_subjects.php','class_schedule.php','master_schedule.php']) ? '' : ' collapsed' ?>">
                    <button class="nav-section-title" data-toggle="collapse">
                        <span>Academic</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="nav-section-content">
                        <a href="classes.php" class="nav-link <?= in_array($currentPage, ['classes.php','class_create.php','class_edit.php','class_view.php']) ? 'active' : '' ?>">
                            <i class="fas fa-chalkboard"></i><span>Classes</span>
                        </a>
                        <a href="subjects.php" class="nav-link <?= $currentPage=='subjects.php' ? 'active' : '' ?>">
                            <i class="fas fa-book"></i><span>Subjects</span>
                        </a>
                        <a href="teacher_subjects.php" class="nav-link <?= $currentPage=='teacher_subjects.php' ? 'active' : '' ?>">
                            <i class="fas fa-user-graduate"></i><span>Teacher Subjects</span>
                        </a>
                        <a href="class_schedule.php" class="nav-link <?= $currentPage=='class_schedule.php' ? 'active' : '' ?>">
                            <i class="fas fa-calendar-alt"></i><span>Schedule</span>
                        </a>
                        <a href="master_schedule.php" class="nav-link <?= $currentPage=='master_schedule.php' ? 'active' : '' ?>">
                            <i class="fas fa-table"></i><span>Master Schedule</span>
                        </a>
                    </div>
                </div>
                <div class="nav-section<?= in_array($currentPage, ['fee_structures.php','fee_payments.php','receipt.php','expense_categories.php','expenses.php','salary_payments.php','finance_report.php']) ? '' : ' collapsed' ?>">
                    <button class="nav-section-title" data-toggle="collapse">
                        <span>Finance</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="nav-section-content">
                        <a href="fee_structures.php" class="nav-link <?= $currentPage=='fee_structures.php' ? 'active' : '' ?>">
                            <i class="fas fa-hand-holding-usd"></i><span>Fee Structure</span>
                        </a>
                        <a href="fee_payments.php" class="nav-link <?= in_array($currentPage, ['fee_payments.php','receipt.php']) ? 'active' : '' ?>">
                            <i class="fas fa-money-bill"></i><span>Payments</span>
                        </a>
                        <div class="nav-section-divider"></div>
                        <a href="expense_categories.php" class="nav-link <?= $currentPage=='expense_categories.php' ? 'active' : '' ?>">
                            <i class="fas fa-tags"></i><span>Categories</span>
                        </a>
                        <a href="expenses.php" class="nav-link <?= $currentPage=='expenses.php' ? 'active' : '' ?>">
                            <i class="fas fa-receipt"></i><span>Expenses</span>
                        </a>
                        <a href="salary_payments.php" class="nav-link <?= $currentPage=='salary_payments.php' ? 'active' : '' ?>">
                            <i class="fas fa-money-check-alt"></i><span>Salary Payments</span>
                        </a>
                        <a href="finance_report.php" class="nav-link <?= $currentPage=='finance_report.php' ? 'active' : '' ?>">
                            <i class="fas fa-chart-bar"></i><span>Finance Report</span>
                        </a>
                    </div>
                </div>
                <div class="nav-section<?= in_array($currentPage, ['settings.php','users.php','timeslots.php','reports.php']) ? '' : ' collapsed' ?>">
                    <button class="nav-section-title" data-toggle="collapse">
                        <span>Settings</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="nav-section-content">
                        <a href="settings.php" class="nav-link <?= $currentPage=='settings.php' ? 'active' : '' ?>">
                            <i class="fas fa-cog"></i><span>Institute Settings</span>
                        </a>
                        <a href="users.php" class="nav-link <?= $currentPage=='users.php' ? 'active' : '' ?>">
                            <i class="fas fa-users"></i><span>Users</span>
                        </a>
                        <a href="timeslots.php" class="nav-link <?= $currentPage=='timeslots.php' ? 'active' : '' ?>">
                            <i class="fas fa-clock"></i><span>Time Slots</span>
                        </a>
                        <a href="reports.php" class="nav-link <?= $currentPage=='reports.php' ? 'active' : '' ?>">
                            <i class="fas fa-chart-bar"></i><span>Reports</span>
                        </a>
                    </div>
                </div>
            </nav>
            <div class="sidebar-footer">
                <a href="logout.php" class="nav-link" style="color: var(--danger);">
                    <i class="fas fa-sign-out-alt"></i><span>Logout</span>
                </a>
            </div>
        </aside>
        <div id="page-content">
            <header class="page-header">
                <button class="menu-toggle" id="menuToggle"><i class="fas fa-bars"></i></button>
                <div class="header-left">
                    <nav class="breadcrumbs">
                        <a href="index.php"><i class="fas fa-home"></i></a>
                        <i class="fas fa-chevron-right"></i>
                        <span class="current"><?php
                            $titles = [
                                'index.php'=>'Dashboard','classes.php'=>'Classes','class_create.php'=>'Create Class',
                                'class_edit.php'=>'Edit Class','class_view.php'=>'Class Details',
                                'students.php'=>'Students','timeslots.php'=>'Time Slots','teachers.php'=>'Teachers',
                                'teacher_create.php'=>'Add Teacher','teacher_edit.php'=>'Edit Teacher',
                                'fee_structures.php'=>'Fee Structure','fee_payments.php'=>'Payments','receipt.php'=>'Receipt',
                                'expense_categories.php'=>'Expense Categories','expenses.php'=>'Expenses',
                                'salary_payments.php'=>'Salary Payments',
                                'finance_report.php'=>'Finance Report',
                                'reports.php'=>'Reports','settings.php'=>'Settings','users.php'=>'Users',
                                'subjects.php'=>'Subjects','teacher_subjects.php'=>'Teacher Subjects',
                                'class_schedule.php'=>'Schedule',
                                'master_schedule.php'=>'Master Schedule'
                            ];
                            echo $titles[$currentPage] ?? 'Dashboard';
                        ?></span>
                    </nav>
                </div>
                <div class="header-center">
                    <div class="header-search">
                        <i class="fas fa-search"></i>
                        <input type="text" id="globalSearch" placeholder="Search..." autocomplete="off">
                        <kbd>⌘K</kbd>
                    </div>
                    <div class="search-results" id="searchResults"></div>
                </div>
                <div class="header-right">
                    <div class="quick-add-dropdown">
                        <button class="header-icon-btn" id="quickAddBtn" title="Quick Add">
                            <i class="fas fa-plus"></i>
                        </button>
                        <div class="quick-add-menu" id="quickAddMenu">
                            <a href="students.php?action=add"><i class="fas fa-user-graduate"></i> New Student</a>
                            <a href="teachers.php?action=add"><i class="fas fa-user-tie"></i> New Teacher</a>
                            <a href="class_create.php"><i class="fas fa-chalkboard"></i> New Class</a>
                            <a href="class_schedule.php"><i class="fas fa-calendar-alt"></i> Schedule</a>
                            <a href="fee_payments.php?action=add"><i class="fas fa-money-bill"></i> Record Payment</a>
                            <a href="expenses.php"><i class="fas fa-receipt"></i> Record Expense</a>
                        </div>
                    </div>
                    <button class="header-icon-btn" id="themeToggle" title="Toggle Theme">
                        <i class="fas fa-moon"></i>
                    </button>
                    <div class="header-date">
                        <i class="fas fa-calendar-alt"></i>
                        <span><?= date('M d, Y') ?></span>
                    </div>
                    <div class="header-avatar">
                        <?= strtoupper(substr($current_user, 0, 1)) ?>
                    </div>
                </div>
            </header>
            <main class="page-content">
