<?php
Auth::requireRole('admin');
require_once __DIR__ . '/../../views/partials/header.php';
require_once __DIR__ . '/../../views/partials/navbar.php';
require_once __DIR__ . '/../../views/partials/sidebar.php';

$allDays = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
?>

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6"><h1>Add New Doctor</h1></div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item">
                        <a href="<?= BASE_URL ?>/index.php?page=dashboard">Dashboard</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="<?= BASE_URL ?>/index.php?page=doctors">Doctors</a>
                    </li>
                    <li class="breadcrumb-item active">Add New</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">

        <?php require_once __DIR__ . '/../../views/partials/alerts.php'; ?>

        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card card-success">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-user-md mr-2"></i>
                            Doctor Information
                        </h3>
                    </div>

                    <form method="POST"
                          enctype="multipart/form-data"
                          action="<?= BASE_URL ?>/index.php?page=doctors&action=create">
                        <?= CSRF::tokenInput() ?>

                        <div class="card-body">

                            <!-- Account Info -->
                            <h5 class="border-bottom pb-2 mb-3">
                                <i class="fas fa-user mr-2"></i>
                                Account Information
                            </h5>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Full Name *</label>
                                        <input type="text"
                                               name="name"
                                               class="form-control"
                                               placeholder="Dr. Full Name"
                                               value="<?= e($_POST['name'] ?? '') ?>"
                                               required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Email *</label>
                                        <input type="email"
                                               name="email"
                                               class="form-control"
                                               placeholder="doctor@clinic.com"
                                               value="<?= e($_POST['email'] ?? '') ?>"
                                               required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Password *</label>
                                        <input type="password"
                                               name="password"
                                               class="form-control"
                                               placeholder="Min 6 characters"
                                               required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Phone</label>
                                        <input type="text"
                                               name="phone"
                                               class="form-control"
                                               placeholder="Optional"
                                               value="<?= e($_POST['phone'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>

                            <!-- Doctor Info -->
                            <h5 class="border-bottom pb-2 mb-3 mt-3">
                                <i class="fas fa-stethoscope mr-2"></i>
                                Doctor Profile
                            </h5>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Specialization *</label>
                                        <select name="specialization_id"
                                                class="form-control"
                                                required>
                                            <option value="">
                                                -- Select Specialization --
                                            </option>
                                            <?php foreach ($specializations as $spec): ?>
                                                <option value="<?= $spec['id'] ?>"
                                                    <?= ($_POST['specialization_id'] ?? '') == $spec['id'] ? 'selected' : '' ?>>
                                                    <?= e($spec['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Consultation Fee ($)</label>
                                        <input type="number"
                                               name="consultation_fee"
                                               class="form-control"
                                               placeholder="0.00"
                                               step="0.01"
                                               min="0"
                                               value="<?= e($_POST['consultation_fee'] ?? '0') ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Bio</label>
                                <textarea name="bio"
                                          class="form-control"
                                          rows="3"
                                          placeholder="Brief doctor biography..."><?= e($_POST['bio'] ?? '') ?></textarea>
                            </div>

                            <div class="form-group">
                                <label>Doctor Photo (JPEG/PNG, max 1MB)</label>
                                <input type="file"
                                       name="doctor_photo"
                                       class="form-control-file"
                                       accept="image/jpeg,image/png">
                            </div>

                            <!-- Available Days -->
                            <div class="form-group">
                                <label>Available Days *</label>
                                <div class="row">
                                    <?php
                                    $selectedDays = $_POST['available_days']
                                        ?? ['Sun','Mon','Tue','Wed','Thu'];
                                    ?>
                                    <?php foreach ($allDays as $day): ?>
                                        <div class="col-md-3 col-6">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox"
                                                       class="custom-control-input"
                                                       id="day_<?= $day ?>"
                                                       name="available_days[]"
                                                       value="<?= $day ?>"
                                                       <?= in_array($day, $selectedDays) ? 'checked' : '' ?>>
                                                <label class="custom-control-label"
                                                       for="day_<?= $day ?>">
                                                    <?= $day ?>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                        </div>

                        <div class="card-footer">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save mr-1"></i>
                                Add Doctor
                            </button>
                            <a href="<?= BASE_URL ?>/index.php?page=doctors"
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

<?php require_once __DIR__ . '/../../views/partials/footer.php'; ?>
