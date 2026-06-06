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
                <h1>Create New User</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item">
                        <a href="<?= BASE_URL ?>/index.php?page=dashboard">
                            Dashboard
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="<?= BASE_URL ?>/index.php?page=users">
                            Users
                        </a>
                    </li>
                    <li class="breadcrumb-item active">Create</li>
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
                <div class="card card-primary">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-user-plus mr-2"></i>
                            New User Details
                        </h3>
                    </div>

                    <form method="POST"
                          action="<?= BASE_URL ?>/index.php?page=users&action=create">
                        <?= CSRF::tokenInput() ?>

                        <div class="card-body">

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Full Name *</label>
                                        <input type="text"
                                               name="name"
                                               class="form-control"
                                               placeholder="Enter full name"
                                               value="<?= e($_POST['name'] ?? '') ?>"
                                               required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Email Address *</label>
                                        <input type="email"
                                               name="email"
                                               class="form-control"
                                               placeholder="Enter email"
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

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Role *</label>
                                        <select name="role" class="form-control" required>
                                            <option value="patient"
                                                <?= ($_POST['role'] ?? 'patient') === 'patient' ? 'selected' : '' ?>>
                                                Patient
                                            </option>
                                            <option value="doctor"
                                                <?= ($_POST['role'] ?? '') === 'doctor' ? 'selected' : '' ?>>
                                                Doctor
                                            </option>
                                            <option value="admin"
                                                <?= ($_POST['role'] ?? '') === 'admin' ? 'selected' : '' ?>>
                                                Admin
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i>
                                Create User
                            </button>
                            <a href="<?= BASE_URL ?>/index.php?page=users"
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