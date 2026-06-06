<?php
Auth::requireAuth();
require_once __DIR__ . '/../../views/partials/header.php';
require_once __DIR__ . '/../../views/partials/navbar.php';
require_once __DIR__ . '/../../views/partials/sidebar.php';
?>

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>
                    <?= Auth::isDoctor() ? 'Prescriptions Issued' : 'My Prescriptions' ?>
                </h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item">
                        <a href="<?= BASE_URL ?>/index.php?page=dashboard">
                            Dashboard
                        </a>
                    </li>
                    <li class="breadcrumb-item active">Prescriptions</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">

        <?php require_once __DIR__ . '/../../views/partials/alerts.php'; ?>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-file-prescription mr-2"></i>
                    <?= Auth::isDoctor()
                        ? 'All Prescriptions'
                        : 'My Prescriptions' ?>
                </h3>
            </div>

            <div class="card-body p-0">
                <table class="table table-striped table-hover">
                    <thead class="thead-dark">
                        <tr>
                            <th>#</th>
                            <?php if (Auth::isDoctor()): ?>
                                <th>Patient</th>
                            <?php else: ?>
                                <th>Doctor</th>
                                <th>Specialization</th>
                            <?php endif; ?>
                            <th>Date</th>
                            <th>Diagnosis</th>
                            <th>PDF</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($prescriptions)): ?>
                            <tr>
                                <td colspan="7"
                                    class="text-center text-muted py-4">
                                    <i class="fas fa-file-medical fa-2x mb-2 d-block"></i>
                                    No prescriptions found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($prescriptions as $presc): ?>
                                <tr>
                                    <td><?= $presc['id'] ?></td>

                                    <?php if (Auth::isDoctor()): ?>
                                        <td>
                                            <?= e($presc['patient_name']) ?>
                                        </td>
                                    <?php else: ?>
                                        <td>
                                            Dr. <?= e($presc['doctor_name']) ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-info">
                                                <?= e($presc['specialization_name']) ?>
                                            </span>
                                        </td>
                                    <?php endif; ?>

                                    <td>
                                        <?= formatDate($presc['appt_date']) ?>
                                    </td>

                                    <td>
                                        <?= e(truncate($presc['diagnosis'], 40)) ?>
                                    </td>

                                    <td>
                                        <?php if ($presc['file_path']): ?>
                                            <span class="badge badge-success">
                                                <i class="fas fa-file-pdf mr-1"></i>
                                                Available
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">
                                                No File
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <!-- View Appointment -->
                                        <a href="<?= BASE_URL ?>/index.php?page=appointments&action=detail&id=<?= $presc['appointment_id'] ?>"
                                           class="btn btn-xs btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>

                                        <!-- Download PDF -->
                                        <?php if ($presc['file_path']): ?>
                                            <a href="<?= BASE_URL ?>/index.php?page=prescriptions&action=download&id=<?= $presc['appointment_id'] ?>"
                                               class="btn btn-xs btn-success">
                                                <i class="fas fa-download"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../../views/partials/footer.php'; ?>
