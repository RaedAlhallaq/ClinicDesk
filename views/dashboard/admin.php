<?php
Auth::requireRole('admin');
require_once __DIR__ . '/../../views/partials/header.php';
require_once __DIR__ . '/../../views/partials/navbar.php';
require_once __DIR__ . '/../../views/partials/sidebar.php';
?>

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Admin Dashboard</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item active">Dashboard</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">

        <?php require_once __DIR__ . '/../../views/partials/alerts.php'; ?>

        <!-- ================================================ -->
        <!-- Row 1: User Stats -->
        <!-- ================================================ -->
        <div class="row">

            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?= $userCounts['doctor'] ?></h3>
                        <p>Doctors</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-user-md"></i>
                    </div>
                    <a href="<?= BASE_URL ?>/index.php?page=doctors"
                       class="small-box-footer">
                        View All <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?= $userCounts['patient'] ?></h3>
                        <p>Patients</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <a href="<?= BASE_URL ?>/index.php?page=users"
                       class="small-box-footer">
                        View All <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?= $appointmentCounts['pending'] ?></h3>
                        <p>Pending Appointments</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <a href="<?= BASE_URL ?>/index.php?page=appointments"
                       class="small-box-footer">
                        View All <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3><?= $appointmentsToday ?></h3>
                        <p>Appointments Today</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <a href="<?= BASE_URL ?>/index.php?page=appointments"
                       class="small-box-footer">
                        View All <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>

        </div>

        <!-- ================================================ -->
        <!-- Row 2: Appointment Status Cards -->
        <!-- ================================================ -->
        <div class="row">

            <div class="col-md-3 col-6">
                <div class="info-box">
                    <span class="info-box-icon bg-warning elevation-1">
                        <i class="fas fa-hourglass-half"></i>
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text">Pending</span>
                        <span class="info-box-number">
                            <?= $weekStatusCounts['pending'] ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-6">
                <div class="info-box">
                    <span class="info-box-icon bg-info elevation-1">
                        <i class="fas fa-calendar-check"></i>
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text">Confirmed</span>
                        <span class="info-box-number">
                            <?= $weekStatusCounts['confirmed'] ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-6">
                <div class="info-box">
                    <span class="info-box-icon bg-success elevation-1">
                        <i class="fas fa-check-double"></i>
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text">Completed</span>
                        <span class="info-box-number">
                            <?= $weekStatusCounts['completed'] ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-6">
                <div class="info-box">
                    <span class="info-box-icon bg-danger elevation-1">
                        <i class="fas fa-times-circle"></i>
                    </span>
                    <div class="info-box-content">
                        <span class="info-box-text">Cancelled</span>
                        <span class="info-box-number">
                            <?= $weekStatusCounts['cancelled'] ?>
                        </span>
                    </div>
                </div>
            </div>

        </div>

        <!-- ================================================ -->
        <!-- Row 3: Recent Appointments Table -->
        <!-- ================================================ -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-calendar-alt mr-2"></i>
                            Recent Appointments
                        </h3>
                        <div class="card-tools">
                            <a href="<?= BASE_URL ?>/index.php?page=appointments"
                               class="btn btn-sm btn-primary">
                                View All
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped table-hover">
                            <thead class="thead-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Patient</th>
                                    <th>Doctor</th>
                                    <th>Specialization</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentAppointments)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            <i class="fas fa-calendar-times fa-2x mb-2 d-block"></i>
                                            No appointments yet.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recentAppointments as $appt): ?>
                                        <tr>
                                            <td><?= $appt['id'] ?></td>
                                            <td><?= e($appt['patient_name']) ?></td>
                                            <td><?= e($appt['doctor_name']) ?></td>
                                            <td><?= e($appt['specialization_name']) ?></td>
                                            <td><?= formatDate($appt['appt_date']) ?></td>
                                            <td><?= formatTime($appt['appt_time']) ?></td>
                                            <td>
                                                <?php
                                                $badgeClass = match($appt['status']) {
                                                    'pending'   => 'badge-warning',
                                                    'confirmed' => 'badge-info',
                                                    'completed' => 'badge-success',
                                                    'cancelled' => 'badge-danger',
                                                    default     => 'badge-secondary'
                                                };
                                                ?>
                                                <span class="badge <?= $badgeClass ?>">
                                                    <?= ucfirst($appt['status']) ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>

<?php require_once __DIR__ . '/../../views/partials/footer.php'; ?>
