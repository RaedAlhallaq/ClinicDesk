<?php
Auth::requireRole('doctor');
require_once __DIR__ . '/../../views/partials/header.php';
require_once __DIR__ . '/../../views/partials/navbar.php';
require_once __DIR__ . '/../../views/partials/sidebar.php';
?>

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6"><h1>Add Prescription</h1></div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item">
                        <a href="<?= BASE_URL ?>/index.php?page=appointments">
                            Appointments
                        </a>
                    </li>
                    <li class="breadcrumb-item active">Add Prescription</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">

        <?php require_once __DIR__ . '/../../views/partials/alerts.php'; ?>

        <!-- Appointment Summary -->
        <div class="callout callout-info">
            <h5>Appointment #<?= $appointment['id'] ?></h5>
            <p class="mb-0">
                <strong>Patient:</strong>
                <?= e($appointment['patient_name']) ?>
                &nbsp;|&nbsp;
                <strong>Date:</strong>
                <?= formatDate($appointment['appt_date']) ?>
                &nbsp;|&nbsp;
                <strong>Time:</strong>
                <?= formatTime($appointment['appt_time']) ?>
            </p>
        </div>

        <div class="row justify-content-center">
            <div class="col-md-9">
                <div class="card card-success">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-file-prescription mr-2"></i>
                            Prescription Details
                        </h3>
                    </div>

                    <form method="POST"
                          action="<?= BASE_URL ?>/index.php?page=prescriptions&action=add&appointment_id=<?= $appointmentId ?>"
                          enctype="multipart/form-data">
                        <?= CSRF::tokenInput() ?>

                        <div class="card-body">

                            <!-- Diagnosis -->
                            <div class="form-group">
                                <label>Diagnosis *</label>
                                <textarea name="diagnosis"
                                          class="form-control"
                                          rows="3"
                                          placeholder="Enter diagnosis..."
                                          required><?= e($_POST['diagnosis'] ?? '') ?></textarea>
                            </div>

                            <!-- Medications -->
                            <div class="form-group">
                                <label>Medications *</label>
                                <textarea name="medications"
                                          class="form-control"
                                          rows="4"
                                          placeholder="List medications, dosage, and instructions..."
                                          required><?= e($_POST['medications'] ?? '') ?></textarea>
                            </div>

                            <!-- Notes -->
                            <div class="form-group">
                                <label>Additional Notes</label>
                                <textarea name="notes"
                                          class="form-control"
                                          rows="2"
                                          placeholder="Optional notes..."><?= e($_POST['notes'] ?? '') ?></textarea>
                            </div>

                            <!-- PDF Upload -->
                            <div class="form-group">
                                <label>
                                    Prescription PDF
                                    <small class="text-muted">
                                        (Optional, max 3MB)
                                    </small>
                                </label>
                                <div class="input-group">
                                    <div class="custom-file">
                                        <input type="file"
                                               class="custom-file-input"
                                               id="prescription_file"
                                               name="prescription_file"
                                               accept=".pdf">
                                        <label class="custom-file-label"
                                               for="prescription_file">
                                            Choose PDF file...
                                        </label>
                                    </div>
                                </div>
                                <small class="text-muted">
                                    PDF files only. Max size: 3MB.
                                </small>
                            </div>

                        </div>

                        <div class="card-footer">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save mr-1"></i>
                                Save Prescription
                            </button>
                            <a href="<?= BASE_URL ?>/index.php?page=appointments&action=detail&id=<?= $appointmentId ?>"
                               class="btn btn-secondary ml-2">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Custom file input label update -->
<script>
document.getElementById('prescription_file')
    .addEventListener('change', function() {
        const fileName = this.files[0]
            ? this.files[0].name
            : 'Choose PDF file...';
        this.nextElementSibling.textContent = fileName;
    });
</script>

<?php require_once __DIR__ . '/../../views/partials/footer.php'; ?>