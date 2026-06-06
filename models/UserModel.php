<?php
// ============================================================
// models/UserModel.php
// كل العمليات على جدول users
// يرث من BaseModel: $db, fetchAll, fetchOne, fetchColumn...
// ============================================================

require_once __DIR__ . '/BaseModel.php';

class UserModel extends BaseModel
{
    // --------------------------------------------------------
    // اسم الجدول كـ constant
    // إذا غيّرت اسم الجدول → تغيير في مكان واحد فقط
    // --------------------------------------------------------
    private const TABLE = 'users';


    // --------------------------------------------------------
    // findById(): البحث عن مستخدم بالـ ID
    //
    // تُستخدم في:
    // - تحميل بيانات المستخدم لصفحة التعديل
    // - التحقق من ملكية resource
    //
    // تُرجع: مصفوفة بيانات المستخدم أو null
    // --------------------------------------------------------
    public function findById(int $id): ?array
    {
        return $this->fetchOne(
            "SELECT id, name, email, role, phone,
                    avatar, is_active, created_at
             FROM   users
             WHERE  id = ?",
            "i",
            [$id]
        );
    }


    // --------------------------------------------------------
    // findByEmail(): البحث عن مستخدم بالبريد الإلكتروني
    //
    // تُستخدم في:
    // - تسجيل الدخول (AuthController)
    // - التحقق من عدم تكرار البريد عند الإنشاء
    //
    // ملاحظة: نجلب password هنا لأن AuthController
    // يحتاجه لـ password_verify()
    // في findById نخفيه لأنه غير مطلوب في باقي الحالات
    // --------------------------------------------------------
    public function findByEmail(string $email): ?array
    {
        return $this->fetchOne(
            "SELECT id, name, email, password, role,
                    phone, avatar, is_active
             FROM   users
             WHERE  email = ?",
            "s",
            [$email]
        );
    }


    // --------------------------------------------------------
    // create(): إنشاء مستخدم جديد
    //
    // تُستخدم في:
    // - Admin ينشئ حساب جديد (UserController)
    //
    // $data يجب أن يحتوي على:
    // name, email, password (نص عادي - سنشفّره هنا)
    // role, phone (اختياري)
    //
    // تُرجع: ID المستخدم الجديد أو 0 إذا فشل
    // --------------------------------------------------------
    public function create(array $data): int
    {
        // شفّر كلمة المرور هنا داخل Model
        // لا تشفّرها في Controller
        // Model هو المسؤول عن كيفية حفظ البيانات
        $hashedPassword = password_hash(
            $data['password'],
            PASSWORD_BCRYPT
        );

        $result = $this->execute(
            "INSERT INTO users (name, email, password, role, phone)
             VALUES            (?,    ?,     ?,        ?,    ?   )",
            "sssss",
            [
                $data['name'],
                $data['email'],
                $hashedPassword,
                $data['role']  ?? 'patient',
                $data['phone'] ?? null
            ]
        );

        // إذا نجح INSERT → أعد الـ ID الجديد
        // إذا فشل (مثل تكرار email) → أعد 0
        return $result ? $this->lastInsertId() : 0;
    }


    // --------------------------------------------------------
    // update(): تعديل بيانات مستخدم
    //
    // تُستخدم في:
    // - Admin يعدّل بيانات مستخدم
    // - المستخدم يعدّل ملفه الشخصي
    //
    // $data يمكن أن يحتوي على:
    // name, phone, avatar (اختياري)
    //
    // تُرجع: true إذا نجح، false إذا فشل
    // --------------------------------------------------------
    public function update(int $id, array $data): bool
    {
        return (bool) $this->execute(
            "UPDATE users
             SET    name   = ?,
                    phone  = ?,
                    avatar = ?
             WHERE  id     = ?",
            "sssi",
            [
                $data['name'],
                $data['phone']  ?? null,
                $data['avatar'] ?? null,
                $id
            ]
        );
    }


    // --------------------------------------------------------
    // updatePassword(): تغيير كلمة المرور فقط
    //
    // دالة منفصلة لتغيير كلمة المرور
    // لأنها عملية حساسة تختلف عن باقي التعديلات
    //
    // $newPassword → كلمة المرور الجديدة (نص عادي)
    //               سنشفّرها هنا
    // --------------------------------------------------------
    public function updatePassword(int $id, string $newPassword): bool
    {
        $hashedPassword = password_hash(
            $newPassword,
            PASSWORD_BCRYPT
        );

        return (bool) $this->execute(
            "UPDATE users
             SET    password = ?
             WHERE  id       = ?",
            "si",
            [$hashedPassword, $id]
        );
    }


    // --------------------------------------------------------
    // updateAvatar(): تحديث صورة المستخدم فقط
    //
    // دالة منفصلة لأن رفع الصورة
    // يحدث بشكل مستقل عن باقي البيانات
    // --------------------------------------------------------
    public function updateAvatar(int $id, string $avatarPath): bool
    {
        return (bool) $this->execute(
            "UPDATE users
             SET    avatar = ?
             WHERE  id     = ?",
            "si",
            [$avatarPath, $id]
        );
    }


    // --------------------------------------------------------
    // toggleActive(): تفعيل/تعطيل حساب
    //
    // يقلب قيمة is_active:
    // 1 → 0 (تعطيل)
    // 0 → 1 (تفعيل)
    //
    // استخدام NOT في SQL أنظف من قراءة القيمة وعكسها في PHP
    // --------------------------------------------------------
    public function toggleActive(int $id): bool
    {
        return (bool) $this->execute(
            "UPDATE users
             SET    is_active = NOT is_active
             WHERE  id        = ?",
            "i",
            [$id]
        );
    }


    // --------------------------------------------------------
    // getAllPaginated(): جلب المستخدمين مع Pagination وفلترة
    //
    // تُستخدم في:
    // - لوحة Admin لعرض قائمة المستخدمين
    //
    // $offset → من Paginator::offset()
    // $limit  → ITEMS_PER_PAGE من config
    // $role   → فلترة بالدور (اختياري)
    // $search → بحث باسم أو بريد (اختياري)
    //
    // تُرجع: مصفوفة المستخدمين
    // --------------------------------------------------------
    public function getAllPaginated(
        int    $offset,
        int    $limit,
        string $role   = '',
        string $search = ''
    ): array {
        // نبني WHERE clause ديناميكيًا
        $conditions = [];
        $params     = [];
        $types      = '';

        // فلترة بالدور إذا تم تمريره
        if (!empty($role)) {
            $conditions[] = "role = ?";
            $params[]     = $role;
            $types       .= 's';
        }

        // فلترة بالبحث إذا تم تمريره
        if (!empty($search)) {
            $conditions[] = "(name LIKE ? OR email LIKE ?)";
            $params[]     = "%{$search}%";
            $params[]     = "%{$search}%";
            $types       .= 'ss';
        }

        // بناء جملة WHERE
        $where = '';
        if (!empty($conditions)) {
            $where = "WHERE " . implode(" AND ", $conditions);
        }

        // أضف LIMIT و OFFSET للـ params
        $params[] = $limit;
        $params[] = $offset;
        $types   .= 'ii';

        return $this->fetchAll(
            "SELECT   id, name, email, role,
                      phone, is_active, created_at
             FROM     users
             {$where}
             ORDER BY created_at DESC
             LIMIT    ? OFFSET ?",
            $types,
            $params
        );
    }


    // --------------------------------------------------------
    // countAll(): عدد المستخدمين الإجمالي
    //
    // تُستخدم مع Paginator لحساب عدد الصفحات
    // نفس فلاتر getAllPaginated لكن بدون LIMIT/OFFSET
    // --------------------------------------------------------
    public function countAll(
        string $role   = '',
        string $search = ''
    ): int {
        $conditions = [];
        $params     = [];
        $types      = '';

        if (!empty($role)) {
            $conditions[] = "role = ?";
            $params[]     = $role;
            $types       .= 's';
        }

        if (!empty($search)) {
            $conditions[] = "(name LIKE ? OR email LIKE ?)";
            $params[]     = "%{$search}%";
            $params[]     = "%{$search}%";
            $types       .= 'ss';
        }

        $where = '';
        if (!empty($conditions)) {
            $where = "WHERE " . implode(" AND ", $conditions);
        }

        return (int) $this->fetchColumn(
            "SELECT COUNT(*) FROM users {$where}",
            $types,
            $params
        );
    }


    // --------------------------------------------------------
    // countByRole(): عدد المستخدمين لكل دور
    //
    // تُستخدم في:
    // - لوحة تحكم Admin (إحصائيات)
    //
    // تُرجع مصفوفة مثل:
    // ['admin' => 1, 'doctor' => 5, 'patient' => 23]
    // --------------------------------------------------------
    public function countByRole(): array
    {
        $rows = $this->fetchAll(
            "SELECT   role, COUNT(*) as total
             FROM     users
             GROUP BY role"
        );

        // حوّل النتيجة لمصفوفة role → total
        $counts = [
            'admin'   => 0,
            'doctor'  => 0,
            'patient' => 0
        ];

        foreach ($rows as $row) {
            $counts[$row['role']] = (int) $row['total'];
        }

        return $counts;
    }


    // --------------------------------------------------------
    // emailExists(): هل البريد مستخدم بالفعل؟
    //
    // تُستخدم عند إنشاء مستخدم جديد
    // للتحقق قبل INSERT لإظهار رسالة خطأ واضحة
    //
    // $excludeId → لتجاهل المستخدم الحالي عند التعديل
    // مثال: عند تعديل الـ email، تجاهل الـ email الحالي
    // --------------------------------------------------------
    public function emailExists(string $email, int $excludeId = 0): bool
    {
        $count = $this->fetchColumn(
            "SELECT COUNT(*)
             FROM   users
             WHERE  email = ?
             AND    id    != ?",
            "si",
            [$email, $excludeId]
        );

        return (int) $count > 0;
    }



// --------------------------------------------------------
    // delete(): حذف مستخدم بالـ ID
    //
    // ON DELETE CASCADE → يحذف doctor/appointments تلقائيًا
    // تُستخدم في: Admin panel + تنظيف الاختبارات
    // --------------------------------------------------------
    public function delete(int $id): bool
    {
        return (bool) $this->execute(
            "DELETE FROM users WHERE id = ?",
            "i",
            [$id]
        );
    }

    }