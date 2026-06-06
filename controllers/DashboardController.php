<?php
// ============================================================
// controllers/DashboardController.php
// Routes each user role to their specific dashboard with appropriate data.
// ============================================================

require_once __DIR__ . '/../models/BaseModel.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/DoctorModel.php';
require_once __DIR__ . '/../models/AppointmentModel.php';
require_once __DIR__ . '/../models/PrescriptionModel.php';

// All roles require the user to be logged in.
Auth::requireAuth();

// Route to the correct dashboard based on the logged-in user's role.
match(Auth::role()) {
    'admin'   => adminDashboard(),
    'doctor'  => doctorDashboard(),
    'patient' => patientDashboard(),
    default   => redirect('auth', 'login')
};


// ============================================================
// adminDashboard()
// Loads the Admin Dashboard with system-wide statistics.
// ============================================================
function adminDashboard(): void
{
    $userModel        = new UserModel();
    $appointmentModel = new AppointmentModel();

    // Fetch total system statistics for the Admin view.
    $userCounts         = $userModel->countByRole();
    $appointmentCounts  = $appointmentModel->countByStatus();
    $appointmentsToday  = $appointmentModel->countToday();
    $weekStatusCounts   = $appointmentModel->countThisWeekByStatus();
    $recentAppointments = $appointmentModel->getRecentForAdmin(5);

    $pageTitle   = 'Admin Dashboard';
    $currentPage = 'dashboard';

    // Render the Admin Dashboard view.
    require_once __DIR__ . '/../views/dashboard/admin.php';
}


// ============================================================
// doctorDashboard()
// Loads the Doctor Dashboard showing only their own appointments.
// ============================================================
function doctorDashboard(): void
{
    $doctorModel      = new DoctorModel();
    $appointmentModel = new AppointmentModel();

    // Fetch the doctor's specific profile record.
    $doctor = $doctorModel->findByUserId(Auth::id());

    if (!$doctor) {
        flashMessage('Doctor profile not found.', 'error');
        redirect('auth', 'logout');
    }

    $doctorId = $doctor['id'];

    // Fetch appointments scheduled for today.
    $todayAppointments = $appointmentModel->getTodayByDoctor($doctorId);

    // Fetch statistics for the current month.
    $appointmentCounts = $appointmentModel->countThisMonthByDoctor($doctorId);
    $monthTotal        = array_sum($appointmentCounts);

    // Fetch the next 5 upcoming appointments.
    $upcomingAppointments = $appointmentModel->getByDoctor(
        $doctorId, 0, 5,
        ['date_from' => date('Y-m-d')]
    );

    $pageTitle   = 'My Dashboard';
    $currentPage = 'dashboard';

    // Render the Doctor Dashboard view.
    require_once __DIR__ . '/../views/dashboard/doctor.php';
}


// ============================================================
// patientDashboard()
// Loads the Patient Dashboard showing their own appointments and prescriptions.
// ============================================================
function patientDashboard(): void
{
    $appointmentModel  = new AppointmentModel();
    $prescriptionModel = new PrescriptionModel();

    $patientId = Auth::id();

    // Fetch upcoming appointments for the patient.
    $upcomingAppointments = $appointmentModel->getUpcomingByPatient($patientId, 5);
    $nextAppointment      = $appointmentModel->getNextUpcomingByPatient($patientId);

    // Fetch appointment statistics based on status.
    $appointmentCounts = $appointmentModel->countByStatus('patient', $patientId);

    // Fetch total number of prescriptions.
    $prescriptionCount = $prescriptionModel->countByPatient($patientId);

    $pageTitle   = 'My Dashboard';
    $currentPage = 'dashboard';

    // Render the Patient Dashboard view.
    require_once __DIR__ . '/../views/dashboard/patient.php';
}
