<?php
// ============================================================
// views/doctors/profile.php
// Doctor's own profile edit page (self-edit)
// ============================================================

// Ensure current user is a doctor
Auth::requireRole('doctor');
require_once __DIR__ . '/../../views/partials/header.php';
require_once __DIR__ . '/../../views/partials/navbar.php';
require_once __DIR__ . '/../../views/partials/sidebar.php';

// All available days for checkboxes
$allDays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

// Currently selected days (POST overrides DB value for re-display on error)
$selectedDays = $_POST['available_days']
    ?? $doctor['available_days_array']
    ?? [];
?>

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>My Profile</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item">
                        <a href="<?= BASE_URL ?>/index.php?page=dashboard">Dashboard</a>
                    </li>
                    <li class="breadcrumb-item active">My Profile</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">

        <?php require_once __DIR__ . '/../../views/partials/alerts.php'; ?>

        <div class="row">

            <!-- ============================================ -->
            <!-- Column 1: Current Photo + Quick Info        -->
            <!-- ============================================ -->
            <div class="col-md-3">
                <div class="card card-primary card-outline">
                    <div class="card-body text-center pt-4">

                        <!-- Current doctor photo -->
                        <?php if (!empty($doctor['avatar'])): ?>
                            <img src="<?= e(uploadUrl($doctor['avatar'])) ?>"
                                 alt="<?= e($doctor['name']) ?>"
                                 class="img-fluid rounded-circle mb-3"
                                 style="width:120px; height:120px; object-fit:cover;">
                        <?php else: ?>
                            <!-- Default avatar placeholder -->
                            <div class="bg-secondary rounded-circle d-inline-flex align-items-center
                                        justify-content-center mb-3"
                                 style="width:120px; height:120px;">
                                <i class="fas fa-user-md fa-3x text-white"></i>
                            </div>
                        <?php endif; ?>

                        <h5 class="mb-0"><?= e($doctor['name']) ?></h5>
                        <p class="text-muted small mb-1"><?= e($doctor['specialization_name']) ?></p>
                        <span class="badge badge-success">Active Doctor</span>
                    </div>

                    <!-- Quick info card -->
                    <div class="card-footer p-0">
                        <ul class="nav flex-column">
                            <li class="nav-item border-bottom py-2 px-3">
                                <i class="fas fa-envelope mr-2 text-muted"></i>
                                <small><?= e($doctor['email']) ?></small>
                            </li>
                            <li class="nav-item border-bottom py-2 px-3">
                                <i class="fas fa-phone mr-2 text-muted"></i>
                                <small><?= e($doctor['phone'] ?? '—') ?></small>
                            </li>
                            <li class="nav-item py-2 px-3">
                                <i class="fas fa-dollar-sign mr-2 text-muted"></i>
                                <small>Fee: $<?= e(number_format($doctor['consultation_fee'], 2)) ?></small>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- ============================================ -->
            <!-- Column 2: Edit Form                         -->
            <!-- ============================================ -->
            <div class="col-md-9">
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-user-edit mr-2"></i>
                            Edit My Profile
                        </h3>
                    </div>

                    <!--
                        Required for photo upload
                    -->
                    <form method="POST"
                          enctype="multipart/form-data"
                          action="<?= BASE_URL ?>/index.php?page=doctors&action=profile"
                          id="profileEditForm">

                        <!-- CSRF protection token -->
                        <?= CSRF::tokenInput() ?>

                        <div class="card-body">

                            <!-- ================================ -->
                            <!-- Section 1: Account Info         -->
                            <!-- ================================ -->
                            <h5 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-user mr-2"></i>
                                Account Information
                            </h5>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="profile_name">Full Name *</label>
                                        <input type="text"
                                               id="profile_name"
                                               name="name"
                                               class="form-control"
                                               value="<?= e($_POST['name'] ?? $doctor['name']) ?>"
                                               required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="profile_email">Email Address *</label>
                                        <input type="email"
                                               id="profile_email"
                                               name="email"
                                               class="form-control"
                                               value="<?= e($_POST['email'] ?? $doctor['email']) ?>"
                                               required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="profile_phone">Phone Number</label>
                                        <input type="text"
                                               id="profile_phone"
                                               name="phone"
                                               class="form-control"
                                               placeholder="Optional"
                                               value="<?= e($_POST['phone'] ?? $doctor['phone'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>

                            <hr>

                            <!-- ================================ -->
                            <!-- Section 2: Doctor Profile Info  -->
                            <!-- ================================ -->
                            <h5 class="border-bottom pb-2 mb-3 mt-2">
                                <i class="fas fa-stethoscope mr-2"></i>
                                Doctor Profile
                            </h5>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="profile_fee">Consultation Fee ($)</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">$</span>
                                            </div>
                                            <input type="number"
                                                   id="profile_fee"
                                                   name="consultation_fee"
                                                   class="form-control"
                                                   step="0.01"
                                                   min="0"
                                                   value="<?= e($_POST['consultation_fee'] ?? $doctor['consultation_fee']) ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="profile_bio">Bio / About Me</label>
                                <textarea id="profile_bio"
                                          name="bio"
                                          class="form-control"
                                          rows="4"
                                          placeholder="Write a brief professional biography..."><?= e($_POST['bio'] ?? $doctor['bio'] ?? '') ?></textarea>
                                <small class="text-muted">
                                    This bio will be visible to patients when booking.
                                </small>
                            </div>

                            <!-- ================================ -->
                            <!-- Section 3: Available Days       -->
                            <!-- ================================ -->
                            <div class="form-group">
                                <label>Available Days *</label>
                                <div class="row">
                                    <?php foreach ($allDays as $day): ?>
                                        <div class="col-md-3 col-6">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       class="custom-control-input"
                                                       id="profile_day_<?= $day ?>"
                                                       name="available_days[]"
                                                       value="<?= $day ?>"
                                                       <?= in_array($day, $selectedDays) ? 'checked' : '' ?>>
                                                <label class="custom-control-label"
                                                       for="profile_day_<?= $day ?>">
                                                    <?= $day ?>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <small class="text-muted">
                                    Select all days you are available for appointments.
                                </small>
                            </div>

                            <!-- ================================ -->
                            <!-- Section 4: Profile Photo Upload -->
                            <!-- ================================ -->
                            <div class="form-group mt-3">
                                <label>Profile Photo</label>
                                <div class="input-group">
                                    <div class="custom-file">
                                        <input type="file"
                                               class="custom-file-input"
                                               id="profile_photo"
                                               name="doctor_photo"
                                               accept="image/jpeg,image/png">
                                        <label class="custom-file-label" for="profile_photo">
                                            Choose photo...
                                        </label>
                                    </div>
                                </div>
                                <small class="text-muted">
                                    <!-- Accepts JPEG and PNG only -->
                                    JPEG or PNG only, max 1MB.
                                    <?php if (!empty($doctor['avatar'])): ?>
                                        Current: <strong><?= e(basename($doctor['avatar'])) ?></strong>
                                    <?php endif; ?>
                                </small>
                            </div>

                        </div><!-- /.card-body -->

                        <div class="card-footer">
                            <button type="submit"
                                    class="btn btn-primary"
                                    id="saveProfileBtn">
                                <i class="fas fa-save mr-1"></i>
                                Save Changes
                            </button>
                            <a href="<?= BASE_URL ?>/index.php?page=dashboard"
                               class="btn btn-secondary ml-2">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div><!-- /.card -->
            </div><!-- /.col-md-9 -->

        </div><!-- /.row -->
    </div><!-- /.container-fluid -->
</section>

<!-- Script to show selected filename in custom file input label -->
<script>
document.getElementById('profile_photo').addEventListener('change', function () {
    var label = this.nextElementSibling;
    label.textContent = this.files.length > 0 ? this.files[0].name : 'Choose photo...';
});
</script>

<?php require_once __DIR__ . '/../../views/partials/footer.php'; ?>
