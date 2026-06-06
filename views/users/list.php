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
                <h1>Manage Users</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item">
                        <a href="<?= BASE_URL ?>/index.php?page=dashboard">
                            Dashboard
                        </a>
                    </li>
                    <li class="breadcrumb-item active">Users</li>
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
                    <i class="fas fa-users mr-2"></i>
                    All Users
                </h3>
                <div class="card-tools">
                    <a href="<?= BASE_URL ?>/index.php?page=users&action=create"
                       class="btn btn-sm btn-success">
                        <i class="fas fa-plus mr-1"></i>
                        Add New User
                    </a>
                </div>
            </div>

            <!-- Search & Filter -->
            <div class="card-body border-bottom">
                <form method="GET"
                      action="<?= BASE_URL ?>/index.php">
                    <input type="hidden" name="page"   value="users">
                    <input type="hidden" name="action" value="list">
                    <div class="row">
                        <div class="col-md-5">
                            <div class="input-group">
                                <input type="text"
                                       name="search"
                                       class="form-control"
                                       placeholder="Search by name or email..."
                                       value="<?= e($search ?? '') ?>">
                                <div class="input-group-append">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select name="role"
                                    class="form-control"
                                    onchange="this.form.submit()">
                                <option value="">All Roles</option>
                                <option value="admin"
                                    <?= ($role ?? '') === 'admin' ? 'selected' : '' ?>>
                                    Admin
                                </option>
                                <option value="doctor"
                                    <?= ($role ?? '') === 'doctor' ? 'selected' : '' ?>>
                                    Doctor
                                </option>
                                <option value="patient"
                                    <?= ($role ?? '') === 'patient' ? 'selected' : '' ?>>
                                    Patient
                                </option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <a href="<?= BASE_URL ?>/index.php?page=users"
                               class="btn btn-secondary btn-block">
                                Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Table -->
            <div class="card-body p-0">
                <table class="table table-striped table-hover">
                    <thead class="thead-dark">
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="8"
                                    class="text-center text-muted py-4">
                                    No users found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td><?= $u['id'] ?></td>
                                    <td>
                                        <strong><?= e($u['name']) ?></strong>
                                    </td>
                                    <td><?= e($u['email']) ?></td>
                                    <td>
                                        <?php
                                        $roleClass = match($u['role']) {
                                            'admin'   => 'badge-danger',
                                            'doctor'  => 'badge-info',
                                            'patient' => 'badge-success',
                                            default   => 'badge-secondary'
                                        };
                                        ?>
                                        <span class="badge <?= $roleClass ?>">
                                            <?= ucfirst($u['role']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= e($u['phone'] ?? '—') ?>
                                    </td>
                                    <td>
                                        <?php if ($u['is_active']): ?>
                                            <span class="badge badge-success">
                                                Active
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">
                                                Inactive
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= formatDate($u['created_at']) ?>
                                    </td>
                                    <td>
                                        <!-- Edit -->
                                        <a href="<?= BASE_URL ?>/index.php?page=users&action=edit&id=<?= $u['id'] ?>"
                                           class="btn btn-xs btn-info">
                                            <i class="fas fa-edit"></i>
                                        </a>

                                        <!-- Toggle Active -->
                                        <?php if ($u['id'] !== Auth::id()): ?>
                                            <form method="POST"
                                                  action="<?= BASE_URL ?>/index.php?page=users&action=toggle"
                                                  style="display:inline;">
                                                <?= CSRF::tokenInput() ?>
                                                <input type="hidden"
                                                       name="id"
                                                       value="<?= $u['id'] ?>">
                                                <button type="submit"
                                                        class="btn btn-xs <?= $u['is_active'] ? 'btn-warning' : 'btn-success' ?>"
                                                        onclick="return confirm('<?= $u['is_active'] ? 'Deactivate' : 'Activate' ?> this user?')">
                                                    <i class="fas fa-<?= $u['is_active'] ? 'ban' : 'check' ?>"></i>
                                                </button>
                                            </form>

                                            <!-- Delete -->
                                            <form method="POST"
                                                  action="<?= BASE_URL ?>/index.php?page=users&action=delete"
                                                  style="display:inline;">
                                                <?= CSRF::tokenInput() ?>
                                                <input type="hidden"
                                                       name="id"
                                                       value="<?= $u['id'] ?>">
                                                <button type="submit"
                                                        class="btn btn-xs btn-danger"
                                                        onclick="return confirm('Delete this user permanently?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
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
                    <div class="row">
                        <div class="col-sm-5">
                            <div class="dataTables_info">
                                Showing
                                <?= $paginator->offset() + 1 ?>
                                to
                                <?= min($paginator->offset() + $paginator->perPage(), $totalItems) ?>
                                of <?= $totalItems ?> users
                            </div>
                        </div>
                        <div class="col-sm-7">
                            <ul class="pagination pagination-sm float-right">

                                <?php if ($paginator->hasPrev()): ?>
                                    <li class="page-item">
                                        <a class="page-link"
                                           href="<?= $paginator->pageUrl($paginator->prevPage(), 'users') ?>">
                                            &laquo;
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <?php foreach ($paginator->pages() as $num): ?>
                                    <li class="page-item <?= $num === $paginator->currentPage() ? 'active' : '' ?>">
                                        <a class="page-link"
                                           href="<?= $paginator->pageUrl($num, 'users') ?>">
                                            <?= $num ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>

                                <?php if ($paginator->hasNext()): ?>
                                    <li class="page-item">
                                        <a class="page-link"
                                           href="<?= $paginator->pageUrl($paginator->nextPage(), 'users') ?>">
                                            &raquo;
                                        </a>
                                    </li>
                                <?php endif; ?>

                            </ul>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../../views/partials/footer.php'; ?>