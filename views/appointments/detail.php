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
                <h1>Appointment #<?= $appointment['id'] ?></h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item">
                        <a href="<?= BASE_URL ?>/index.php?page=appointments">
                            Appointments
                        </a>
                    </li>
                    <li class="breadcrumb-item active">Detail</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">

        <?php require_once __DIR__ . '/../../views/partials/alerts.php'; ?>

        <div class="row">

            <!-- Appointment Info -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-info-circle mr-2"></i>
                            Appointment Details
                        </h3>
                    </div>
                    <div class="card-body">
                        <dl class="row">
                            <dt class="col-sm-4">Patient</dt>
                            <dd class="col-sm-8">
                                <?= e($appointment['patient_name']) ?>
                            </dd>

                            <dt class="col-sm-4">Doctor</dt>
                            <dd class="col-sm-8">
                                Dr. <?= e($appointment['doctor_name']) ?>
                                <span class="badge badge-info ml-1">
                                    <?= e($appointment['specialization_name']) ?>
                                </span>
                            </dd>

                            <dt class="col-sm-4">Date</dt>
                            <dd class="col-sm-8">
                                <?= formatDate($appointment['appt_date']) ?>
                            </dd>

                            <dt class="col-sm-4">Time</dt>
                            <dd class="col-sm-8">
                                <?= formatTime($appointment['appt_time']) ?>
                            </dd>

                            <dt class="col-sm-4">Status</dt>
                            <dd class="col-sm-8">
                                <?php
                                $badgeClass = match ($appointment['status']) {
                                    'pending' => 'badge-warning',
                                    'confirmed' => 'badge-info',
                                    'completed' => 'badge-success',
                                    'cancelled' => 'badge-danger',
                                    default => 'badge-secondary'
                                };
                                ?>
                                <span class="badge <?= $badgeClass ?> badge-lg">
                                    <?= ucfirst($appointment['status']) ?>
                                </span>
                            </dd>

                            <dt class="col-sm-4">Fee</dt>
                            <dd class="col-sm-8">
                                $<?= number_format($appointment['consultation_fee'], 2) ?>
                            </dd>

                            <?php if ($appointment['reason']): ?>
                                <dt class="col-sm-4">Reason</dt>
                                <dd class="col-sm-8">
                                    <?= e($appointment['reason']) ?>
                                </dd>
                            <?php endif; ?>

                            <?php if ($appointment['doctor_notes']): ?>
                                <dt class="col-sm-4">Doctor Notes</dt>
                                <dd class="col-sm-8">
                                    <?= e($appointment['doctor_notes']) ?>
                                </dd>
                            <?php endif; ?>
                        </dl>
                    </div>
                </div>

                <!-- Prescription Info -->
                <?php if ($prescription): ?>
                    <div class="card card-success">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-file-prescription mr-2"></i>
                                Prescription
                            </h3>
                        </div>
                        <div class="card-body">
                            <dl class="row">
                                <dt class="col-sm-4">Diagnosis</dt>
                                <dd class="col-sm-8">
                                    <?= e($prescription['diagnosis']) ?>
                                </dd>

                                <dt class="col-sm-4">Medications</dt>
                                <dd class="col-sm-8">
                                    <?= nl2br(e($prescription['medications'])) ?>
                                </dd>

                                <?php if ($prescription['notes']): ?>
                                    <dt class="col-sm-4">Notes</dt>
                                    <dd class="col-sm-8">
                                        <?= e($prescription['notes']) ?>
                                    </dd>
                                <?php endif; ?>
                            </dl>

                            <?php if ($prescription['file_path']): ?>
                                <a href="<?= BASE_URL ?>/index.php?page=prescriptions&action=download&id=<?= $appointment['id'] ?>"
                                    class="btn btn-sm btn-success">
                                    <i class="fas fa-download mr-1"></i>
                                    Download PDF
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Actions -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-cogs mr-2"></i>
                            Actions
                        </h3>
                    </div>
                    <div class="card-body">

                        <!-- Doctor/Admin Actions -->
                        <?php if (
                            (Auth::isDoctor() || Auth::isAdmin()) &&
                            $appointment['status'] !== 'cancelled' &&
                            $appointment['status'] !== 'completed'
                        ): ?>

                            <form method="POST" action="<?= BASE_URL ?>/index.php?page=appointments&action=status">
                                <?= CSRF::tokenInput() ?>
                                <input type="hidden" name="id" value="<?= $appointment['id'] ?>">

                                <div class="form-group">
                                    <label>Update Status</label>
                                    <select name="status" class="form-control">
                                        <?php if ($appointment['status'] === 'pending'): ?>
                                            <option value="confirmed">Confirm</option>
                                            <option value="cancelled">Cancel</option>
                                        <?php elseif ($appointment['status'] === 'confirmed'): ?>
                                            <option value="completed">Mark Complete</option>
                                            <option value="cancelled">Cancel</option>
                                        <?php endif; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Doctor Notes</label>
                                    <textarea name="notes" class="form-control" rows="3"
                                        placeholder="Optional notes..."><?= e($appointment['doctor_notes'] ?? '') ?></textarea>
                                </div>

                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-save mr-1"></i>
                                    Update
                                </button>
                            </form>

                        <?php endif; ?>

                        <!-- Add Prescription (Doctor, completed, no prescription) -->
                        <?php if (
                            Auth::isDoctor() &&
                            $appointment['status'] === 'completed' &&
                            !$prescription
                        ): ?>
                            <a href="<?= BASE_URL ?>/index.php?page=prescriptions&action=add&appointment_id=<?= $appointment['id'] ?>"
                                class="btn btn-success btn-block">
                                <i class="fas fa-plus mr-1"></i>
                                Add Prescription
                            </a>
                        <?php endif; ?>

                        <!-- Cancel (Patient, pending) -->
                        <?php if (
                            Auth::isPatient() &&
                            $appointment['status'] === 'pending'
                        ): ?>
                            <form method="POST" action="<?= BASE_URL ?>/index.php?page=appointments&action=cancel">
                                <?= CSRF::tokenInput() ?>
                                <input type="hidden" name="id" value="<?= $appointment['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-block"
                                    onclick="return confirm('Cancel this appointment?')">
                                    <i class="fas fa-times mr-1"></i>
                                    Cancel Appointment
                                </button>
                            </form>
                        <?php endif; ?>

                        <a href="<?= BASE_URL ?>/index.php?page=appointments" class="btn btn-secondary btn-block mt-2">
                            <i class="fas fa-arrow-left mr-1"></i>
                            Back to List
                        </a>

                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../../views/partials/footer.php'; ?>
