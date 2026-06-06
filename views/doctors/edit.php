<?php
Auth::requireRole('admin');
require_once __DIR__ . '/../../views/partials/header.php';
require_once __DIR__ . '/../../views/partials/navbar.php';
require_once __DIR__ . '/../../views/partials/sidebar.php';

$allDays     = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
$selectedDays = $_POST['available_days']
    ?? $doctor['available_days_array']
    ?? [];
?>

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6"><h1>Edit Doctor</h1></div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item">
                        <a href="<?= BASE_URL ?>/index.php?page=dashboard">Dashboard</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="<?= BASE_URL ?>/index.php?page=doctors">Doctors</a>
                    </li>
                    <li class="breadcrumb-item active">Edit</li>
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
                <div class="card card-warning">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-user-edit mr-2"></i>
                            Edit: <?= e($doctor['name']) ?>
                        </h3>
                    </div>

                    <form method="POST"
                          enctype="multipart/form-data"
                          action="<?= BASE_URL ?>/index.php?page=doctors&action=edit&id=<?= $doctor['id'] ?>">
                        <?= CSRF::tokenInput() ?>

                        <div class="card-body">

                            <!-- Read-only Account Info -->
                            <div class="callout callout-info mb-4">
                                <h5>Account Info (read-only)</h5>
                                <p class="mb-0">
                                    <strong>Name:</strong> <?= e($doctor['name']) ?>
                                    &nbsp;|&nbsp;
                                    <strong>Email:</strong> <?= e($doctor['email']) ?>
                                    &nbsp;|&nbsp;
                                    <strong>Phone:</strong> <?= e($doctor['phone'] ?? '—') ?>
                                </p>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Specialization *</label>
                                        <select name="specialization_id"
                                                class="form-control"
                                                required>
                                            <?php foreach ($specializations as $spec): ?>
                                                <option value="<?= $spec['id'] ?>"
                                                    <?= $spec['id'] == ($doctor['specialization_id']) ? 'selected' : '' ?>>
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
                                               step="0.01"
                                               min="0"
                                               value="<?= e($doctor['consultation_fee']) ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Bio</label>
                                <textarea name="bio"
                                          class="form-control"
                                          rows="3"><?= e($doctor['bio'] ?? '') ?></textarea>
                            </div>

                            <div class="form-group">
                                <label>Doctor Photo (JPEG/PNG, max 1MB)</label>
                                <input type="file"
                                       name="doctor_photo"
                                       class="form-control-file"
                                       accept="image/jpeg,image/png">
                                <?php if (!empty($doctor['avatar'])): ?>
                                    <small class="text-muted d-block mt-1">
                                        Current file: <?= e($doctor['avatar']) ?>
                                    </small>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label>Available Days *</label>
                                <div class="row">
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
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-save mr-1"></i>
                                Save Changes
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
