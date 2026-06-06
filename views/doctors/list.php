<?php
Auth::requireRole('admin');
require_once __DIR__ . '/../../views/partials/header.php';
require_once __DIR__ . '/../../views/partials/navbar.php';
require_once __DIR__ . '/../../views/partials/sidebar.php';
?>

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6"><h1>Manage Doctors</h1></div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item">
                        <a href="<?= BASE_URL ?>/index.php?page=dashboard">Dashboard</a>
                    </li>
                    <li class="breadcrumb-item active">Doctors</li>
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
                    <i class="fas fa-user-md mr-2"></i>All Doctors
                </h3>
                <div class="card-tools">
                    <a href="<?= BASE_URL ?>/index.php?page=doctors&action=create"
                       class="btn btn-sm btn-success">
                        <i class="fas fa-plus mr-1"></i>Add New Doctor
                    </a>
                </div>
            </div>

            <div class="card-body p-0">
                <table class="table table-striped table-hover">
                    <thead class="thead-dark">
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Specialization</th>
                            <th>Fee</th>
                            <th>Available Days</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($doctors)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    No doctors found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($doctors as $doc): ?>
                                <tr>
                                    <td><?= $doc['id'] ?></td>
                                    <td>
                                        <strong><?= e($doc['name']) ?></strong><br>
                                        <small class="text-muted">
                                            <?= e($doc['email']) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge badge-info">
                                            <?= e($doc['specialization_name']) ?>
                                        </span>
                                    </td>
                                    <td>$<?= number_format($doc['consultation_fee'], 2) ?></td>
                                    <td>
                                        <small><?= e($doc['available_days']) ?></small>
                                    </td>
                                    <td>
                                        <?php if ($doc['is_active']): ?>
                                            <span class="badge badge-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?= BASE_URL ?>/index.php?page=doctors&action=edit&id=<?= $doc['id'] ?>"
                                           class="btn btn-xs btn-warning">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($paginator->totalPages() > 1): ?>
                <div class="card-footer">
                    <ul class="pagination pagination-sm float-right">
                        <?php if ($paginator->hasPrev()): ?>
                            <li class="page-item">
                                <a class="page-link"
                                   href="<?= $paginator->pageUrl($paginator->prevPage(), 'doctors') ?>">
                                    &laquo;
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php foreach ($paginator->pages() as $num): ?>
                            <li class="page-item <?= $num === $paginator->currentPage() ? 'active' : '' ?>">
                                <a class="page-link"
                                   href="<?= $paginator->pageUrl($num, 'doctors') ?>">
                                    <?= $num ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                        <?php if ($paginator->hasNext()): ?>
                            <li class="page-item">
                                <a class="page-link"
                                   href="<?= $paginator->pageUrl($paginator->nextPage(), 'doctors') ?>">
                                    &raquo;
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            <?php endif; ?>

        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../../views/partials/footer.php'; ?>