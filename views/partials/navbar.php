<?php $currentUser = Auth::currentUser(); ?>
<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button">
                <i class="fas fa-bars"></i>
            </a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
            <a href="<?= BASE_URL ?>/index.php?page=dashboard" class="nav-link">
                Home
            </a>
        </li>
    </ul>
    <ul class="navbar-nav ml-auto">
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#">
                <i class="fas fa-user-circle fa-lg"></i>
                &nbsp;<?= e($currentUser['name'] ?? 'User') ?>
            </a>
            <div class="dropdown-menu dropdown-menu-right">
                <a class="dropdown-item" href="#">
                    <i class="fas fa-user mr-2"></i>
                    <?= e($currentUser['name'] ?? '') ?>
                    &nbsp;
                    <span class="badge badge-secondary">
                        <?= match($currentUser['role'] ?? '') {
                            'admin'   => 'Admin',
                            'doctor'  => 'Doctor',
                            'patient' => 'Patient',
                            default   => ''
                        } ?>
                    </span>
                </a>
                <div class="dropdown-divider"></div>
                <form method="POST"
                      action="<?= BASE_URL ?>/index.php?page=auth&action=logout">
                    <?= CSRF::tokenInput() ?>
                    <button type="submit" class="dropdown-item text-danger">
                        <i class="fas fa-sign-out-alt mr-2"></i>
                        Logout
                    </button>
                </form>
            </div>
        </li>
    </ul>
</nav>