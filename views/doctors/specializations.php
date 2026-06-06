<?php
Auth::requireRole('admin');
require_once __DIR__ . '/../../views/partials/header.php';
require_once __DIR__ . '/../../views/partials/navbar.php';
require_once __DIR__ . '/../../views/partials/sidebar.php';
?>

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6"><h1>Specializations</h1></div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item">
                        <a href="<?= BASE_URL ?>/index.php?page=dashboard">Dashboard</a>
                    </li>
                    <li class="breadcrumb-item active">Specializations</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">

        <?php require_once __DIR__ . '/../../views/partials/alerts.php'; ?>

        <div class="row">

            <!-- Add New Specialization -->
            <div class="col-md-4">
                <div class="card card-success">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-plus mr-2"></i>
                            Add Specialization
                        </h3>
                    </div>
                    <form method="POST"
                          action="<?= BASE_URL ?>/index.php?page=doctors&action=addSpec">
                        <?= CSRF::tokenInput() ?>
                        <div class="card-body">
                            <div class="form-group">
                                <label>Specialization Name *</label>
                                <input type="text"
                                       name="spec_name"
                                       class="form-control"
                                       placeholder="e.g. Cardiology"
                                       required>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-success btn-block">
                                <i class="fas fa-save mr-1"></i>
                                Add Specialization
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Specializations List -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-list mr-2"></i>
                            All Specializations
                            <span class="badge badge-primary ml-2">
                                <?= count($specializations) ?>
                            </span>
                        </h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped">
                            <thead class="thead-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($specializations)): ?>
                                    <tr>
                                        <td colspan="3"
                                            class="text-center text-muted py-4">
                                            No specializations found.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($specializations as $spec): ?>
                                        <tr>
                                            <td><?= $spec['id'] ?></td>
                                            <td>
                                                <strong><?= e($spec['name']) ?></strong>
                                            </td>
                                            <td>
                                                <form method="POST"
                                                      action="<?= BASE_URL ?>/index.php?page=doctors&action=deleteSpec"
                                                      style="display:inline;">
                                                    <?= CSRF::tokenInput() ?>
                                                    <input type="hidden"
                                                           name="spec_id"
                                                           value="<?= $spec['id'] ?>">
                                                    <button type="submit"
                                                            class="btn btn-xs btn-danger"
                                                            onclick="return confirm('Delete this specialization?')">
                                                        <i class="fas fa-trash"></i>
                                                        Delete
                                                    </button>
                                                </form>
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