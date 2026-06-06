<?php
$currentUser = Auth::currentUser();
$currentPage = $currentPage ?? '';
?>
<aside class="main-sidebar sidebar-dark-primary elevation-4">

    <a href="<?= BASE_URL ?>/index.php?page=dashboard"
       class="brand-link">
        <i class="fas fa-clinic-medical brand-image"
           style="font-size:20px; opacity:.8;"></i>
        <span class="brand-text font-weight-bold">
            <?= APP_NAME ?>
        </span>
    </a>

    <div class="sidebar">
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image">
                <i class="fas fa-user-circle fa-2x text-white mt-1"></i>
            </div>
            <div class="info">
                <a href="#" class="d-block">
                    <?= e($currentUser['name'] ?? '') ?>
                </a>
                <small class="text-white-50">
                    <?= match($currentUser['role'] ?? '') {
                        'admin'   => 'System Admin',
                        'doctor'  => 'Doctor',
                        'patient' => 'Patient',
                        default   => ''
                    } ?>
                </small>
            </div>
        </div>

        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column"
                data-widget="treeview" role="menu">

                <!-- Dashboard — All Roles -->
                <li class="nav-item">
                    <a href="<?= BASE_URL ?>/index.php?page=dashboard"
                       class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dashboard</p>
                    </a>
                </li>

                <!-- Admin Links -->
                <?php if (Auth::isAdmin()): ?>

                    <li class="nav-header">MANAGEMENT</li>

                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/index.php?page=users"
                           class="nav-link <?= $currentPage === 'users' ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-users"></i>
                            <p>Users</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/index.php?page=doctors"
                           class="nav-link <?= $currentPage === 'doctors' ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-user-md"></i>
                            <p>Doctors</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/index.php?page=doctors&action=specializations"
                           class="nav-link <?= $currentPage === 'specializations' ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-stethoscope"></i>
                            <p>Specializations</p>
                        </a>
                    </li>

                    <li class="nav-header">APPOINTMENTS & REPORTS</li>

                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/index.php?page=appointments"
                           class="nav-link <?= $currentPage === 'appointments' ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-calendar-check"></i>
                            <p>All Appointments</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/index.php?page=reports"
                           class="nav-link <?= $currentPage === 'reports' ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-chart-bar"></i>
                            <p>Reports</p>
                        </a>
                    </li>

                <?php endif; ?>

                <!-- Doctor Links -->
                <?php if (Auth::isDoctor()): ?>

                    <li class="nav-header">MY SCHEDULE</li>

                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/index.php?page=appointments"
                           class="nav-link <?= $currentPage === 'appointments' ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-calendar-alt"></i>
                            <p>My Appointments</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/index.php?page=prescriptions"
                           class="nav-link <?= $currentPage === 'prescriptions' ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-file-prescription"></i>
                            <p>Prescriptions</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/index.php?page=doctors&action=profile"
                           class="nav-link <?= $currentPage === 'profile' ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-user-edit"></i>
                            <p>My Profile</p>
                        </a>
                    </li>

                <?php endif; ?>

                <!-- Patient Links -->
                <?php if (Auth::isPatient()): ?>

                    <li class="nav-header">MY SERVICES</li>

                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/index.php?page=appointments&action=book"
                           class="nav-link <?= $currentPage === 'book' ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-calendar-plus"></i>
                            <p>Book Appointment</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/index.php?page=appointments"
                           class="nav-link <?= $currentPage === 'appointments' ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-calendar-check"></i>
                            <p>My Appointments</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="<?= BASE_URL ?>/index.php?page=prescriptions"
                           class="nav-link <?= $currentPage === 'prescriptions' ? 'active' : '' ?>">
                            <i class="nav-icon fas fa-file-medical"></i>
                            <p>My Prescriptions</p>
                        </a>
                    </li>

                <?php endif; ?>

            </ul>
        </nav>
    </div>
</aside>

<div class="content-wrapper">