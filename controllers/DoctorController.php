<?php
// ============================================================
// controllers/DoctorController.php
// Manages Doctors and Specializations.
//
// Access Control:
//   - Admin: Can access all actions (list, create, edit, specializations).
//   - Doctor: Can only access the 'profile' action to edit their own data.
// ============================================================

require_once __DIR__ . '/../models/BaseModel.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/DoctorModel.php';
require_once __DIR__ . '/../models/SpecializationModel.php';

// Ensure the user is logged in before accessing any action.
Auth::requireAuth();

$action = $action ?? 'list';

// Route the request based on the action parameter.
// Note: 'profile' is strictly for doctors, everything else is for admins.
match($action) {
    'list'            => doctorList(),
    'create'          => doctorCreate(),
    'edit'            => doctorEdit(),
    'profile'         => doctorProfile(),
    'specializations' => specializationList(),
    'addSpec'         => specializationAdd(),
    'deleteSpec'      => specializationDelete(),
    default           => Auth::isDoctor() ? doctorProfile() : doctorList()
};


// ============================================================
// doctorList()
// Displays a paginated list of all doctors. (Admin only)
// ============================================================
function doctorList(): void
{
    // Restrict access to Admins only.
    Auth::requireRole('admin');

    $doctorModel = new DoctorModel();

    $currentPage = max(1, (int) ($_GET['page_num'] ?? 1));
    $totalItems  = $doctorModel->countAll();
    $paginator   = new Paginator($totalItems, ITEMS_PER_PAGE, $currentPage);

    $doctors = $doctorModel->getAllPaginated(
        $paginator->offset(),
        $paginator->perPage()
    );

    $pageTitle       = 'Manage Doctors';
    $currentPage_nav = 'doctors';

    require_once __DIR__ . '/../views/doctors/list.php';
}


// ============================================================
// doctorCreate()
// Creates a new doctor account and profile. (Admin only)
// ============================================================
function doctorCreate(): void
{
    // Restrict access to Admins only.
    Auth::requireRole('admin');

    $userModel   = new UserModel();
    $doctorModel = new DoctorModel();
    $specModel   = new SpecializationModel();

    $specializations = $specModel->getAll();

    if (isGet()) {
        $pageTitle       = 'Add New Doctor';
        $currentPage_nav = 'doctors';
        require_once __DIR__ . '/../views/doctors/create.php';
        return;
    }

    if (isPost()) {

        // Validate CSRF token before processing the form.
        if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
            flashMessage('Invalid request.', 'error');
            redirect('doctors', 'create');
        }

        // Sanitize base user account fields.
        $name     = sanitizeString($_POST['name']     ?? '');
        $email    = sanitizeEmail($_POST['email']     ?? '');
        $password = $_POST['password']                ?? '';
        $phone    = sanitizeString($_POST['phone']    ?? '');

        // Sanitize doctor-specific profile fields.
        $specId  = (int) ($_POST['specialization_id'] ?? 0);
        $fee     = (float) ($_POST['consultation_fee'] ?? 0);
        $bio     = sanitizeString($_POST['bio']        ?? '');
        $days    = $_POST['available_days']            ?? [];

        // Validate user inputs.
        $errors = [];

        if (empty($name))          $errors[] = 'Name is required.';
        if (empty($email))         $errors[] = 'Email is required.';
        if (strlen($password) < 6) $errors[] = 'Password min 6 chars.';
        if (!$specId)              $errors[] = 'Specialization is required.';
        if (empty($days))          $errors[] = 'Select at least one available day.';

        if ($userModel->emailExists($email)) {
            $errors[] = 'Email already exists.';
        }

        if (!empty($errors)) {
            flashMessage(implode(' ', $errors), 'error');
            redirect('doctors', 'create');
        }

        // Step 1: Create the base user account first.
        $userId = $userModel->create([
            'name'     => $name,
            'email'    => $email,
            'password' => $password,
            'role'     => 'doctor',
            'phone'    => $phone ?: null
        ]);

        if (!$userId) {
            flashMessage('Failed to create user account.', 'error');
            redirect('doctors', 'create');
        }

        // Step 2: Handle optional doctor photo upload securely.
        $photoPath    = null;
        $uploadResult = handleImageUpload(
            'doctor_photo',
            DOCTOR_PHOTO_PATH,
            'doctor_photos',
            MAX_DOCTOR_PHOTO_SIZE,
            'doctor_' . $userId
        );

        if ($uploadResult['error']) {
            // Roll back user creation if photo upload fails.
            $userModel->delete($userId);
            flashMessage($uploadResult['error'], 'error');
            redirect('doctors', 'create');
        }

        $photoPath = $uploadResult['path'];

        // Step 3: Create the doctor's specific profile record.
        $doctorId = $doctorModel->create([
            'user_id'           => $userId,
            'specialization_id' => $specId,
            'bio'               => $bio ?: null,
            'consultation_fee'  => $fee,
            'available_days'    => $days
        ]);

        if (!$doctorId) {
            // Roll back everything if doctor creation fails.
            deleteUploadedFile($photoPath);
            $userModel->delete($userId);
            flashMessage('Failed to create doctor profile.', 'error');
            redirect('doctors', 'create');
        }

        // Step 4: Save the photo path to the users table if uploaded.
        if ($photoPath) {
            $doctorModel->updatePhoto($userId, $photoPath);
        }

        flashMessage("Dr. {$name} added successfully.", 'success');
        redirect('doctors');
    }
}


// ============================================================
// doctorEdit()
// Allows an Admin to edit an existing doctor's data. (Admin only)
// ============================================================
function doctorEdit(): void
{
    // Restrict access to Admins only.
    Auth::requireRole('admin');

    $doctorModel = new DoctorModel();
    $specModel   = new SpecializationModel();

    $id = (int) ($_GET['id'] ?? 0);

    if (!$id) {
        flashMessage('Invalid doctor ID.', 'error');
        redirect('doctors');
    }

    $doctor = $doctorModel->findById($id);

    if (!$doctor) {
        flashMessage('Doctor not found.', 'error');
        redirect('doctors');
    }

    $specializations = $specModel->getAll();

    if (isGet()) {
        $pageTitle       = 'Edit Doctor';
        $currentPage_nav = 'doctors';
        require_once __DIR__ . '/../views/doctors/edit.php';
        return;
    }

    if (isPost()) {

        // Validate CSRF token before processing the form.
        if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
            flashMessage('Invalid request.', 'error');
            redirect('doctors', 'edit', $id);
        }

        $specId = (int)   ($_POST['specialization_id'] ?? 0);
        $fee    = (float) ($_POST['consultation_fee']   ?? 0);
        $bio    = sanitizeString($_POST['bio']          ?? '');
        $days   = $_POST['available_days']              ?? [];

        $errors = [];
        if (!$specId)     $errors[] = 'Specialization is required.';
        if (empty($days)) $errors[] = 'Select at least one available day.';

        if (!empty($errors)) {
            flashMessage(implode(' ', $errors), 'error');
            redirect('doctors', 'edit', $id);
        }

        // Update the doctor's specific profile record.
        $doctorModel->update($id, [
            'specialization_id' => $specId,
            'bio'               => $bio ?: null,
            'consultation_fee'  => $fee,
            'available_days'    => $days
        ]);

        // Handle optional new photo upload securely.
        $uploadResult = handleImageUpload(
            'doctor_photo',
            DOCTOR_PHOTO_PATH,
            'doctor_photos',
            MAX_DOCTOR_PHOTO_SIZE,
            'doctor_' . $doctor['user_id'],
            $doctor['avatar'] ?? null
        );

        if ($uploadResult['error']) {
            flashMessage($uploadResult['error'], 'error');
            redirect('doctors', 'edit', $id);
        }

        if ($uploadResult['path']) {
            $doctorModel->updatePhoto((int) $doctor['user_id'], $uploadResult['path']);
        }

        flashMessage('Doctor updated successfully.', 'success');
        redirect('doctors');
    }
}


// ============================================================
// doctorProfile()
// Allows a logged-in Doctor to edit their own profile. (Doctor only)
// ============================================================
function doctorProfile(): void
{
    // Restrict access to Doctors only.
    Auth::requireRole('doctor');

    $doctorModel = new DoctorModel();
    $userModel   = new UserModel();

    // Fetch the current doctor's record using the session user ID.
    $doctor = $doctorModel->findByUserId(Auth::id());

    if (!$doctor) {
        flashMessage('Doctor profile not found. Please contact admin.', 'error');
        redirect('dashboard');
    }

    if (isGet()) {
        $pageTitle       = 'My Profile';
        $currentPage_nav = 'profile';
        require_once __DIR__ . '/../views/doctors/profile.php';
        return;
    }

    if (isPost()) {

        // Validate CSRF token before processing the form.
        if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
            flashMessage('Invalid request.', 'error');
            redirect('doctors', 'profile');
        }

        // Read and sanitize user inputs.
        $name  = sanitizeString($_POST['name']  ?? '');
        $email = sanitizeEmail($_POST['email']  ?? '');
        $phone = sanitizeString($_POST['phone'] ?? '');
        $bio   = sanitizeString($_POST['bio']   ?? '');
        $fee   = (float) ($_POST['consultation_fee'] ?? 0);
        $days  = $_POST['available_days'] ?? [];

        // Validate user inputs.
        $errors = [];

        if (empty($name))  $errors[] = 'Name is required.';
        if (empty($email)) $errors[] = 'Valid email is required.';
        if (empty($days))  $errors[] = 'Select at least one available day.';
        if ($fee < 0)      $errors[] = 'Consultation fee cannot be negative.';

        // Ensure the email is unique, excluding the doctor's current email.
        if (!empty($email) && $userModel->emailExists($email, Auth::id())) {
            $errors[] = 'This email is already used by another account.';
        }

        if (!empty($errors)) {
            flashMessage(implode(' ', $errors), 'error');
            redirect('doctors', 'profile');
        }

        // Update the doctor's profile and user account data together.
        $updated = $doctorModel->updateProfile(
            $doctor['id'],
            $doctor['user_id'],
            [
                'name'             => $name,
                'email'            => $email,
                'phone'            => $phone ?: null,
                'bio'              => $bio ?: null,
                'consultation_fee' => $fee,
                'available_days'   => $days,
            ]
        );

        if (!$updated) {
            flashMessage('Failed to update profile. Please try again.', 'error');
            redirect('doctors', 'profile');
        }

        // Handle optional new profile photo upload securely.
        $uploadResult = handleImageUpload(
            'doctor_photo',
            DOCTOR_PHOTO_PATH,
            'doctor_photos',
            MAX_DOCTOR_PHOTO_SIZE,
            'doctor_' . $doctor['user_id'],
            $doctor['avatar'] ?? null
        );

        if ($uploadResult['error']) {
            // Warn if the photo failed but the text data was saved.
            flashMessage(
                'Profile updated, but photo upload failed: ' . $uploadResult['error'],
                'warning'
            );
            redirect('doctors', 'profile');
        }

        // Save the new photo path if one was uploaded successfully.
        if ($uploadResult['path']) {
            $doctorModel->updatePhoto($doctor['user_id'], $uploadResult['path']);
        }

        flashMessage('Profile updated successfully.', 'success');
        redirect('doctors', 'profile');
    }
}


// ============================================================
// specializationList()
// Displays a list of all medical specializations. (Admin only)
// ============================================================
function specializationList(): void
{
    Auth::requireRole('admin');

    $specModel       = new SpecializationModel();
    $specializations = $specModel->getAll();

    $pageTitle       = 'Specializations';
    $currentPage_nav = 'specializations';

    require_once __DIR__ . '/../views/doctors/specializations.php';
}


// ============================================================
// specializationAdd()
// Adds a new medical specialization. (Admin only)
// ============================================================
function specializationAdd(): void
{
    Auth::requireRole('admin');

    if (!isPost()) {
        redirect('doctors', 'specializations');
    }

    // Validate CSRF token.
    if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
        flashMessage('Invalid request.', 'error');
        redirect('doctors', 'specializations');
    }

    $name      = sanitizeString($_POST['spec_name'] ?? '');
    $specModel = new SpecializationModel();

    if (empty($name)) {
        flashMessage('Specialization name is required.', 'error');
        redirect('doctors', 'specializations');
    }

    // Prevent duplicate specialization names.
    if ($specModel->nameExists($name)) {
        flashMessage("Specialization '{$name}' already exists.", 'error');
        redirect('doctors', 'specializations');
    }

    $specModel->create($name);
    flashMessage("Specialization '{$name}' added.", 'success');
    redirect('doctors', 'specializations');
}


// ============================================================
// specializationDelete()
// Deletes a medical specialization if no doctors use it. (Admin only)
// ============================================================
function specializationDelete(): void
{
    Auth::requireRole('admin');

    if (!isPost()) {
        redirect('doctors', 'specializations');
    }

    // Validate CSRF token.
    if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
        flashMessage('Invalid request.', 'error');
        redirect('doctors', 'specializations');
    }

    $id        = (int) ($_POST['spec_id'] ?? 0);
    $specModel = new SpecializationModel();

    // Ensure no doctors are currently assigned to this specialization before deleting.
    if (!$specModel->isSafeToDelete($id)) {
        $count = $specModel->getDoctorCount($id);
        flashMessage(
            "Cannot delete — {$count} doctor(s) use this specialization.",
            'error'
        );
        redirect('doctors', 'specializations');
    }

    $specModel->delete($id);
    flashMessage('Specialization deleted.', 'success');
    redirect('doctors', 'specializations');
}
