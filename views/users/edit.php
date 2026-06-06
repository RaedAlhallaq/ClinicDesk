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
                <h1>Edit User</h1>
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
            <div class="col-md-8">
                <div class="card card-warning">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-user-edit mr-2"></i>
                            Edit: <?= e($user['name']) ?>
                        </h3>
                    </div>

                    <form method="POST"
                          enctype="multipart/form-data"
                          action="<?= BASE_URL ?>/index.php?page=users&action=edit&id=<?= $user['id'] ?>">
                        <?= CSRF::tokenInput() ?>

                        <div class="card-body">

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Full Name *</label>
                                        <input type="text"
                                               name="name"
                                               class="form-control"
                                               value="<?= e($_POST['name'] ?? $user['name']) ?>"
                                               required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Email Address</label>
                                        <input type="email"
                                               name="email"
                                               class="form-control"
                                               value="<?= e($_POST['email'] ?? $user['email']) ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Phone</label>
                                        <input type="text"
                                               name="phone"
                                               class="form-control"
                                               value="<?= e($_POST['phone'] ?? $user['phone'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Role</label>
                                        <input type="text"
                                               class="form-control"
                                               value="<?= ucfirst($user['role']) ?>"
                                               disabled>
                                        <small class="text-muted">
                                            Role cannot be changed here.
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Avatar (JPEG/PNG, max 1MB)</label>
                                <input type="file"
                                       name="avatar"
                                       class="form-control-file"
                                       accept="image/jpeg,image/png">
                                <?php if (!empty($user['avatar'])): ?>
                                    <small class="text-muted d-block mt-1">
                                        Current file: <?= e($user['avatar']) ?>
                                    </small>
                                <?php endif; ?>
                            </div>

                            <!-- Status Info -->
                            <div class="callout callout-info">
                                <h5>Account Status</h5>
                                <p>
                                    Status:
                                    <?php if ($user['is_active']): ?>
                                        <span class="badge badge-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Inactive</span>
                                    <?php endif; ?>
                                    &nbsp;|&nbsp;
                                    Member since:
                                    <?= formatDate($user['created_at']) ?>
                                </p>
                            </div>

                        </div>

                        <div class="card-footer">
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-save mr-1"></i>
                                Save Changes
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
