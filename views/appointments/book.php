<?php
Auth::requireRole('patient');
require_once __DIR__ . '/../../views/partials/header.php';
require_once __DIR__ . '/../../views/partials/navbar.php';
require_once __DIR__ . '/../../views/partials/sidebar.php';
?>

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6"><h1>Book Appointment</h1></div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item">
                        <a href="<?= BASE_URL ?>/index.php?page=dashboard">
                            Dashboard
                        </a>
                    </li>
                    <li class="breadcrumb-item active">Book</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">

        <?php require_once __DIR__ . '/../../views/partials/alerts.php'; ?>

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card card-success">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-calendar-plus mr-2"></i>
                            New Appointment
                        </h3>
                    </div>

                    <form method="POST"
                          action="<?= BASE_URL ?>/index.php?page=appointments&action=book">
                        <?= CSRF::tokenInput() ?>

                        <div class="card-body">

                            <!-- Doctor -->
                            <div class="form-group">
                                <label>Select Doctor *</label>
                                <select name="doctor_id"
                                        id="doctor_select"
                                        class="form-control"
                                        required>
                                    <option value="">-- Select Doctor --</option>
                                    <?php foreach ($doctors as $doc): ?>
                                        <option value="<?= $doc['id'] ?>"
                                                data-days="<?= e($doc['available_days']) ?>"
                                                data-fee="<?= $doc['consultation_fee'] ?>"
                                            <?= ($_POST['doctor_id'] ?? '') == $doc['id'] ? 'selected' : '' ?>>
                                            Dr. <?= e($doc['name']) ?>
                                            — <?= e($doc['specialization_name']) ?>
                                            ($<?= number_format($doc['consultation_fee'], 2) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <!-- Info box: shows the selected doctor's available days dynamically -->
                                <div id="doctor_info"
                                     class="callout callout-info mt-2"
                                     style="display:none;">
                                    <small>
                                        <strong>Available Days:</strong>
                                        <span id="doctor_days"></span>
                                    </small>
                                </div>
                            </div>

                            <div class="row">
                                <!-- Date -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Appointment Date *</label>
                                        <input type="date"
                                               name="appt_date"
                                               class="form-control"
                                               min="<?= date('Y-m-d') ?>"
                                               value="<?= e($_POST['appt_date'] ?? '') ?>"
                                               required>
                                    </div>
                                </div>

                                <!-- Time -->
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Time Slot *</label>
                                        <select name="appt_time"
                                                class="form-control"
                                                required>
                                            <option value="">-- Select Time --</option>
                                            <?php foreach ($timeSlots as $slot): ?>
                                                <option value="<?= $slot ?>"
                                                    <?= ($_POST['appt_time'] ?? '') === $slot ? 'selected' : '' ?>>
                                                    <?= formatTime($slot) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Reason -->
                            <div class="form-group">
                                <label>Reason for Visit</label>
                                <textarea name="reason"
                                          class="form-control"
                                          rows="3"
                                          placeholder="Brief description of your visit reason..."><?= e($_POST['reason'] ?? '') ?></textarea>
                            </div>

                        </div>

                        <div class="card-footer">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-calendar-check mr-1"></i>
                                Book Appointment
                            </button>
                            <a href="<?= BASE_URL ?>/index.php?page=appointments"
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

<!-- JavaScript: show the selected doctor's available days when the dropdown changes -->
<script>
document.getElementById('doctor_select').addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    const days     = selected.getAttribute('data-days');
    const info     = document.getElementById('doctor_info');
    const daysSpan = document.getElementById('doctor_days');

    if (days) {
        daysSpan.textContent = days;
        info.style.display   = 'block';
    } else {
        info.style.display = 'none';
    }
});

// Also trigger on page load in case a doctor was already selected (e.g. after form validation failure).
window.addEventListener('load', function() {
    document.getElementById('doctor_select').dispatchEvent(new Event('change'));
});
</script>

<?php require_once __DIR__ . '/../../views/partials/footer.php'; ?>