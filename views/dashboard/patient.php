<?php
Auth::requireRole('patient');
require_once __DIR__ . '/../../views/partials/header.php';
require_once __DIR__ . '/../../views/partials/navbar.php';
require_once __DIR__ . '/../../views/partials/sidebar.php';
?>

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>My Dashboard</h1>
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

        <!-- Stats -->
        <?php if (!empty($nextAppointment)): ?>
            <div class="callout callout-info">
                <h5>
                    <i class="fas fa-calendar-check mr-2"></i>
                    Next Appointment
                </h5>
                <p class="mb-0">
                    Dr. <?= e($nextAppointment['doctor_name']) ?>
                    on <?= formatDate($nextAppointment['appt_date']) ?>
                    at <?= formatTime($nextAppointment['appt_time']) ?>
                </p>
            </div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?= $appointmentCounts['pending'] ?></h3>
                        <p>Pending</p>
                    </div>
                    <div class="icon"><i class="fas fa-hourglass-half"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3><?= $appointmentCounts['confirmed'] ?></h3>
                        <p>Confirmed</p>
                    </div>
                    <div class="icon"><i class="fas fa-calendar-check"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?= $appointmentCounts['completed'] ?></h3>
                        <p>Completed</p>
                    </div>
                    <div class="icon"><i class="fas fa-check-circle"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?= $prescriptionCount ?></h3>
                        <p>Prescriptions</p>
                    </div>
                    <div class="icon"><i class="fas fa-file-medical"></i></div>
                </div>
            </div>
        </div>

        <!-- Upcoming Appointments -->
        <div class="row">
            <div class="col-12">
                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-calendar-alt mr-2"></i>
                            Upcoming Appointments
                        </h3>
                        <div class="card-tools">
                            <a href="<?= BASE_URL ?>/index.php?page=appointments&action=book"
                               class="btn btn-sm btn-success">
                                <i class="fas fa-plus mr-1"></i>
                                Book New
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Doctor</th>
                                    <th>Specialization</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($upcomingAppointments)): ?>
                                    <tr>
                                        <td colspan="5"
                                            class="text-center text-muted py-4">
                                            <i class="fas fa-calendar-plus fa-2x mb-2 d-block"></i>
                                            No upcoming appointments.
                                            <a href="<?= BASE_URL ?>/index.php?page=appointments&action=book">
                                                Book one now!
                                            </a>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($upcomingAppointments as $appt): ?>
                                        <tr>
                                            <td><?= e($appt['doctor_name']) ?></td>
                                            <td><?= e($appt['specialization_name']) ?></td>
                                            <td><?= formatDate($appt['appt_date']) ?></td>
                                            <td><?= formatTime($appt['appt_time']) ?></td>
                                            <td>
                                                <?php
                                                $badgeClass = match($appt['status']) {
                                                    'pending'   => 'badge-warning',
                                                    'confirmed' => 'badge-info',
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
