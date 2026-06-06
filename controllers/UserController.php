<?php
// ============================================================
// controllers/UserController.php
// Manages system users.
// Access is strictly limited to Admins.
// ============================================================

require_once __DIR__ . '/../models/BaseModel.php';
require_once __DIR__ . '/../models/UserModel.php';

// Ensure only Admins can access user management features.
Auth::requireRole('admin');

$action = $action ?? 'list';

match($action) {
    'list'   => userList(),
    'create' => userCreate(),
    'edit'   => userEdit(),
    'toggle' => userToggle(),
    'delete' => userDelete(),
    default  => userList()
};


// ============================================================
// userList()
// Displays a paginated and filterable list of all users.
// ============================================================
function userList(): void
{
    $userModel = new UserModel();

    // Read search and filter parameters from the URL.
    $search = sanitizeString($_GET['search'] ?? '');
    $role   = sanitizeString($_GET['role']   ?? '');

    // Setup Pagination.
    $currentPage = max(1, (int) ($_GET['page_num'] ?? 1));
    $totalItems  = $userModel->countAll($role, $search);
    $paginator   = new Paginator($totalItems, ITEMS_PER_PAGE, $currentPage);
    
    // Preserve filters in pagination links.
    $paginator->setExtraParams(['role' => $role, 'search' => $search]);

    // Fetch the users for the current page.
    $users = $userModel->getAllPaginated(
        $paginator->offset(),
        $paginator->perPage(),
        $role,
        $search
    );

    $pageTitle   = 'Manage Users';
    $currentPage_nav = 'users';

    require_once __DIR__ . '/../views/users/list.php';
}


// ============================================================
// userCreate()
// Allows an Admin to create a new user account.
// ============================================================
function userCreate(): void
{
    $userModel = new UserModel();

    if (isGet()) {
        $pageTitle       = 'Create User';
        $currentPage_nav = 'users';
        require_once __DIR__ . '/../views/users/create.php';
        return;
    }

    if (isPost()) {

        // Validate CSRF token.
        if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
            flashMessage('Invalid request.', 'error');
            redirect('users', 'create');
        }

        // Read and sanitize input data.
        $name     = sanitizeString($_POST['name']     ?? '');
        $email    = sanitizeEmail($_POST['email']     ?? '');
        $password = $_POST['password']                ?? '';
        $role     = sanitizeString($_POST['role']     ?? 'patient');
        $phone    = sanitizeString($_POST['phone']    ?? '');

        // Validate user inputs.
        $errors = [];

        if (empty($name)) {
            $errors[] = 'Name is required.';
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Valid email is required.';
        }

        if (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters.';
        }

        if (!in_array($role, ['admin', 'doctor', 'patient'], true)) {
            $errors[] = 'Invalid role selected.';
        }

        // Check if the email is already in use.
        if ($userModel->emailExists($email)) {
            $errors[] = 'Email already exists.';
        }

        if (!empty($errors)) {
            flashMessage(implode(' ', $errors), 'error');
            redirect('users', 'create');
        }

        // Create the user record in the database.
        $userId = $userModel->create([
            'name'     => $name,
            'email'    => $email,
            'password' => $password,
            'role'     => $role,
            'phone'    => $phone ?: null
        ]);

        if (!$userId) {
            flashMessage('Failed to create user. Try again.', 'error');
            redirect('users', 'create');
        }

        flashMessage("User '{$name}' created successfully.", 'success');
        redirect('users');
    }
}


// ============================================================
// userEdit()
// Allows an Admin to edit an existing user's details.
// ============================================================
function userEdit(): void
{
    $userModel = new UserModel();
    $id        = (int) ($_GET['id'] ?? 0);

    if (!$id) {
        flashMessage('Invalid user ID.', 'error');
        redirect('users');
    }

    $user = $userModel->findById($id);

    if (!$user) {
        flashMessage('User not found.', 'error');
        redirect('users');
    }

    if (isGet()) {
        $pageTitle       = 'Edit User';
        $currentPage_nav = 'users';
        require_once __DIR__ . '/../views/users/edit.php';
        return;
    }

    if (isPost()) {

        // Validate CSRF token.
        if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
            flashMessage('Invalid request.', 'error');
            redirect('users', 'edit');
        }

        $name  = sanitizeString($_POST['name']  ?? '');
        $phone = sanitizeString($_POST['phone'] ?? '');
        $email = sanitizeEmail($_POST['email']  ?? '');

        // Validate user inputs.
        $errors = [];

        if (empty($name)) {
            $errors[] = 'Name is required.';
        }

        // Ensure email uniqueness, excluding the current user's email.
        if ($userModel->emailExists($email, $id)) {
            $errors[] = 'Email already used by another user.';
        }

        if (!empty($errors)) {
            flashMessage(implode(' ', $errors), 'error');
            redirect('users', 'edit', $id);
        }

        // Handle optional avatar upload safely.
        $avatarPath = $user['avatar'];
        $uploadResult = handleImageUpload(
            'avatar',
            AVATAR_PATH,
            'avatars',
            MAX_AVATAR_SIZE,
            'avatar_' . $id,
            $user['avatar'] ?? null
        );

        if ($uploadResult['error']) {
            flashMessage($uploadResult['error'], 'error');
            redirect('users', 'edit', $id);
        }

        if ($uploadResult['path']) {
            $avatarPath = $uploadResult['path'];
        }

        // Update the user record.
        $updated = $userModel->update($id, [
            'name'   => $name,
            'phone'  => $phone ?: null,
            'avatar' => $avatarPath
        ]);

        if ($updated) {
            flashMessage('User updated successfully.', 'success');
        } else {
            flashMessage('No changes were made.', 'info');
        }

        redirect('users');
    }
}


// ============================================================
// userToggle()
// Activates or deactivates a user account.
// ============================================================
function userToggle(): void
{
    // Restrict to POST requests for state-changing actions.
    if (!isPost()) {
        redirect('users');
    }

    // Validate CSRF token.
    if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
        flashMessage('Invalid request.', 'error');
        redirect('users');
    }

    $id        = (int) ($_POST['id'] ?? 0);
    $userModel = new UserModel();

    // Prevent the Admin from deactivating their own account.
    if ($id === Auth::id()) {
        flashMessage('You cannot deactivate your own account.', 'error');
        redirect('users');
    }

    $user = $userModel->findById($id);

    if (!$user) {
        flashMessage('User not found.', 'error');
        redirect('users');
    }

    // Toggle the active status in the database.
    $userModel->toggleActive($id);

    $status = $user['is_active'] ? 'deactivated' : 'activated';
    flashMessage("User account {$status} successfully.", 'success');
    redirect('users');
}


// ============================================================
// userDelete()
// Deletes a user account completely.
// ============================================================
function userDelete(): void
{
    // Restrict to POST requests for state-changing actions.
    if (!isPost()) {
        redirect('users');
    }

    // Validate CSRF token.
    if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
        flashMessage('Invalid request.', 'error');
        redirect('users');
    }

    $id        = (int) ($_POST['id'] ?? 0);
    $userModel = new UserModel();

    // Prevent the Admin from deleting their own account.
    if ($id === Auth::id()) {
        flashMessage('You cannot delete your own account.', 'error');
        redirect('users');
    }

    $user = $userModel->findById($id);

    if (!$user) {
        flashMessage('User not found.', 'error');
        redirect('users');
    }

    // Delete the user from the database.
    $userModel->delete($id);
    flashMessage("User '{$user['name']}' deleted successfully.", 'success');
    redirect('users');
}
