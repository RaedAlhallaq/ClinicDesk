<?php
// ============================================================
// models/DoctorModel.php
// كل العمليات على جدول doctors
// مع JOIN على users و specializations
// ============================================================

require_once __DIR__ . '/BaseModel.php';

class DoctorModel extends BaseModel
{
    // --------------------------------------------------------
    // findById(): جلب طبيب بالـ doctor.id مع كل بياناته
    //
    // تُرجع: بيانات الطبيب كاملة أو null
    // --------------------------------------------------------
    public function findById(int $doctorId): ?array
    {
        $doctor = $this->fetchOne(
            "SELECT  d.id,
                     d.user_id,
                     d.specialization_id,
                     d.bio,
                     d.consultation_fee,
                     d.available_days,
                     u.name,
                     u.email,
                     u.phone,
                     u.avatar,
                     u.is_active,
                     s.name  AS specialization_name
             FROM    doctors         d
             JOIN    users           u ON d.user_id           = u.id
             JOIN    specializations s ON d.specialization_id = s.id
             WHERE   d.id = ?",
            "i",
            [$doctorId]
        );

        if ($doctor) {
            // حوّل available_days من string لمصفوفة
            $doctor['available_days_array'] = $this->parseDays(
                $doctor['available_days']
            );
        }

        return $doctor;
    }


    // --------------------------------------------------------
    // findByUserId(): جلب طبيب بالـ user.id
    //
    // تُستخدم في:
    // - بعد تسجيل الدخول: Auth::id() → findByUserId()
    // - Doctor يعدّل ملفه الشخصي
    // --------------------------------------------------------
    public function findByUserId(int $userId): ?array
    {
        $doctor = $this->fetchOne(
            "SELECT  d.id,
                     d.user_id,
                     d.specialization_id,
                     d.bio,
                     d.consultation_fee,
                     d.available_days,
                     u.name,
                     u.email,
                     u.phone,
                     u.avatar,
                     u.is_active,
                     s.name  AS specialization_name
             FROM    doctors         d
             JOIN    users           u ON d.user_id           = u.id
             JOIN    specializations s ON d.specialization_id = s.id
             WHERE   d.user_id = ?",
            "i",
            [$userId]
        );

        if ($doctor) {
            $doctor['available_days_array'] = $this->parseDays(
                $doctor['available_days']
            );
        }

        return $doctor;
    }


    // --------------------------------------------------------
    // getAll(): جلب كل الأطباء (للـ dropdown في حجز الموعد)
    //
    // تُستخدم في:
    // - نموذج حجز موعد: قائمة الأطباء المتاحين
    // - تقارير Admin: فلترة بالطبيب
    //
    // تُرجع: مصفوفة مبسطة (id, name, specialization, fee)
    // --------------------------------------------------------
    public function getAll(): array
    {
        return $this->fetchAll(
            "SELECT  d.id,
                     d.consultation_fee,
                     d.available_days,
                     u.name,
                     s.name AS specialization_name
             FROM    doctors         d
             JOIN    users           u ON d.user_id           = u.id
             JOIN    specializations s ON d.specialization_id = s.id
             WHERE   u.is_active = 1
             ORDER BY u.name ASC"
        );
    }


    // --------------------------------------------------------
    // getAllPaginated(): قائمة الأطباء مع Pagination للـ Admin
    //
    // تُستخدم في:
    // - لوحة Admin: صفحة إدارة الأطباء
    // --------------------------------------------------------
    public function getAllPaginated(int $offset, int $limit): array
    {
        return $this->fetchAll(
            "SELECT  d.id,
                     d.consultation_fee,
                     d.available_days,
                     d.specialization_id,
                     u.name,
                     u.email,
                     u.phone,
                     u.is_active,
                     u.avatar,
                     s.name AS specialization_name
             FROM    doctors         d
             JOIN    users           u ON d.user_id           = u.id
             JOIN    specializations s ON d.specialization_id = s.id
             ORDER BY u.name ASC
             LIMIT   ? OFFSET ?",
            "ii",
            [$limit, $offset]
        );
    }


    // --------------------------------------------------------
    // countAll(): عدد الأطباء الإجمالي (للـ Paginator)
    // --------------------------------------------------------
    public function countAll(): int
    {
        return (int) $this->fetchColumn(
            "SELECT COUNT(*) FROM doctors"
        );
    }


    // --------------------------------------------------------
    // create(): إنشاء سجل طبيب جديد في جدول doctors
    //
    // ملاحظة: المستخدم يُنشأ أولًا في UserModel::create()
    // ثم هنا نضيف سجل الطبيب المرتبط به
    //
    // $data يجب أن يحتوي على:
    // user_id, specialization_id, bio, consultation_fee
    // available_days (مصفوفة أو string)
    //
    // تُرجع: ID الطبيب الجديد أو 0 إذا فشل
    // --------------------------------------------------------
    public function create(array $data): int
    {
        // إذا available_days مصفوفة → حوّلها لـ string
        $availableDays = is_array($data['available_days'])
            ? implode(',', $data['available_days'])
            : $data['available_days'];

        $result = $this->execute(
            "INSERT INTO doctors
                (user_id, specialization_id, bio,
                 consultation_fee, available_days)
             VALUES
                (?,       ?,                 ?,
                 ?,                ?)       ",
            "iisds",
            [
                $data['user_id'],
                $data['specialization_id'],
                $data['bio']              ?? null,
                $data['consultation_fee'] ?? 0.00,
                $availableDays
            ]
        );

        return $result ? $this->lastInsertId() : 0;
    }


    // --------------------------------------------------------
    // update(): تعديل بيانات طبيب
    //
    // تُستخدم في:
    // - Admin يعدّل بيانات طبيب
    // - Doctor يعدّل ملفه الشخصي
    // --------------------------------------------------------
    public function update(int $doctorId, array $data): bool
    {
        $availableDays = is_array($data['available_days'])
            ? implode(',', $data['available_days'])
            : $data['available_days'];

        return (bool) $this->execute(
            "UPDATE doctors
             SET    specialization_id = ?,
                    bio               = ?,
                    consultation_fee  = ?,
                    available_days    = ?
             WHERE  id                = ?",
            "isdsi",
            [
                $data['specialization_id'],
                $data['bio']              ?? null,
                $data['consultation_fee'] ?? 0.00,
                $availableDays,
                $doctorId
            ]
        );
    }


    // --------------------------------------------------------
    // updatePhoto(): تحديث صورة الطبيب
    //
    // الصورة تُحفظ في جدول users (عمود avatar)
    // لذلك نحدّث جدول users وليس doctors
    // --------------------------------------------------------
    public function updatePhoto(int $userId, string $photoPath): bool
    {
        return (bool) $this->execute(
            "UPDATE users
             SET    avatar = ?
             WHERE  id     = ?",
            "si",
            [$photoPath, $userId]
        );
    }


    // --------------------------------------------------------
    // getAvailableDays(): أيام إتاحة طبيب معين
    //
    // تُستخدم في:
    // - نموذج حجز الموعد للتحقق من اليوم
    // - عرض أيام الإتاحة للمريض
    //
    // تُرجع: مصفوفة أيام مثل ['Sun', 'Mon', 'Wed']
    // --------------------------------------------------------
    public function getAvailableDays(int $doctorId): array
    {
        $result = $this->fetchColumn(
            "SELECT available_days
             FROM   doctors
             WHERE  id = ?",
            "i",
            [$doctorId]
        );

        if (!$result) {
            return [];
        }

        return $this->parseDays($result);
    }


    // --------------------------------------------------------
    // parseDays(): تحويل string الأيام لمصفوفة
    //
    // private → دالة داخلية مساعدة
    // تُستخدم فقط داخل هذا الكلاس
    //
    // المدخل:  "Sun,Mon,Tue,Wed,Thu"
    // المخرج:  ['Sun', 'Mon', 'Tue', 'Wed', 'Thu']
    // --------------------------------------------------------
    private function parseDays(string $days): array
    {
        if (empty($days)) {
            return [];
        }

        // explode(',', ...) → يقسم الـ string على الفاصلة
        // array_map('trim', ...) → يزيل مسافات من كل عنصر
        // array_filter(...) → يحذف العناصر الفارغة
        return array_filter(
            array_map('trim', explode(',', $days))
        );
    }


    // --------------------------------------------------------
    // isAvailableOnDay(): هل الطبيب متاح في يوم معين؟
    //
    // تُستخدم في:
    // - التحقق من صحة الموعد عند الحجز
    //
    // $dayName → اسم اليوم بالإنجليزية: 'Sun','Mon',...
    // --------------------------------------------------------
    public function isAvailableOnDay(int $doctorId, string $dayName): bool
    {
        $availableDays = $this->getAvailableDays($doctorId);
        return in_array($dayName, $availableDays, true);
    }


    // --------------------------------------------------------
    // updateProfile(): تحديث الملف الشخصي للطبيب (self-edit)
    //
    // Update doctor's own profile — used when a doctor edits
    // their own profile page (not admin editing).
    //
    // يحدّث جدولين في استعلامين منفصلين:
    // Updates two tables in two separate prepared statements:
    //   1. doctors → bio, consultation_fee, available_days
    //   2. users   → name, email, phone
    //
    // $doctorId → doctors.id
    // $userId   → users.id (مرتبط بهذا الطبيب)
    // $data     → مصفوفة البيانات المعدّلة
    //
    // تُرجع: true إذا نجح كلا التحديثين
    // --------------------------------------------------------
    public function updateProfile(int $doctorId, int $userId, array $data): bool
    {
        // حوّل available_days من مصفوفة لـ string — convert array to CSV string
        $availableDays = is_array($data['available_days'])
            ? implode(',', $data['available_days'])
            : ($data['available_days'] ?? '');

        // تحديث جدول doctors — update doctors table
        $doctorUpdated = (bool) $this->execute(
            "UPDATE doctors
             SET    bio               = ?,
                    consultation_fee  = ?,
                    available_days    = ?
             WHERE  id                = ?",
            "sdsi",
            [
                $data['bio']              ?? null,
                $data['consultation_fee'] ?? 0.00,
                $availableDays,
                $doctorId
            ]
        );

        // تحديث جدول users — update users table
        $userUpdated = (bool) $this->execute(
            "UPDATE users
             SET    name  = ?,
                    email = ?,
                    phone = ?
             WHERE  id    = ?",
            "sssi",
            [
                $data['name'],
                $data['email'],
                $data['phone'] ?? null,
                $userId
            ]
        );

        return $doctorUpdated && $userUpdated;
    }
}