<?php
// ============================================================
// models/SpecializationModel.php
// كل العمليات على جدول specializations
// ============================================================

require_once __DIR__ . '/BaseModel.php';

class SpecializationModel extends BaseModel
{
    // --------------------------------------------------------
    // getAll(): جلب كل التخصصات
    //
    // تُستخدم في:
    // - dropdown عند إنشاء/تعديل طبيب
    // - صفحة إدارة التخصصات في Admin panel
    //
    // تُرجع: مصفوفة كل التخصصات مرتبة أبجديًا
    // --------------------------------------------------------
    public function getAll(): array
    {
        return $this->fetchAll(
            "SELECT   id, name
             FROM     specializations
             ORDER BY name ASC"
        );
    }


    // --------------------------------------------------------
    // findById(): جلب تخصص واحد بالـ ID
    //
    // تُستخدم للتحقق من وجود التخصص قبل التعديل
    // --------------------------------------------------------
    public function findById(int $id): ?array
    {
        return $this->fetchOne(
            "SELECT id, name
             FROM   specializations
             WHERE  id = ?",
            "i",
            [$id]
        );
    }


    // --------------------------------------------------------
    // nameExists(): هل اسم التخصص موجود بالفعل؟
    //
    // تُستخدم قبل إنشاء تخصص جديد
    // لمنع التكرار
    //
    // $excludeId → لتجاهل التخصص الحالي عند التعديل
    // --------------------------------------------------------
    public function nameExists(string $name, int $excludeId = 0): bool
    {
        $count = $this->fetchColumn(
            "SELECT COUNT(*)
             FROM   specializations
             WHERE  name = ?
             AND    id   != ?",
            "si",
            [$name, $excludeId]
        );

        return (int) $count > 0;
    }


    // --------------------------------------------------------
    // create(): إضافة تخصص جديد
    //
    // تُرجع: ID التخصص الجديد أو 0 إذا فشل
    // --------------------------------------------------------
    public function create(string $name): int
    {
        $result = $this->execute(
            "INSERT INTO specializations (name)
             VALUES                      (?)",
            "s",
            [$name]
        );

        return $result ? $this->lastInsertId() : 0;
    }


    // --------------------------------------------------------
    // update(): تعديل اسم تخصص
    //
    // تُرجع: true إذا نجح، false إذا فشل
    // --------------------------------------------------------
    public function update(int $id, string $name): bool
    {
        return (bool) $this->execute(
            "UPDATE specializations
             SET    name = ?
             WHERE  id   = ?",
            "si",
            [$name, $id]
        );
    }


    // --------------------------------------------------------
    // isSafeToDelete(): هل يمكن حذف هذا التخصص بأمان؟
    //
    // يتحقق: هل يوجد أطباء مرتبطون بهذا التخصص؟
    //
    // تُستخدم قبل delete() دائمًا:
    // if ($model->isSafeToDelete($id)) {
    //     $model->delete($id);
    // } else {
    //     flashMessage('لا يمكن الحذف — يوجد أطباء مرتبطون', 'error');
    // }
    //
    // تُرجع: true = آمن للحذف، false = غير آمن
    // --------------------------------------------------------
    public function isSafeToDelete(int $id): bool
    {
        $count = $this->fetchColumn(
            "SELECT COUNT(*)
             FROM   doctors
             WHERE  specialization_id = ?",
            "i",
            [$id]
        );

        // إذا العدد = 0 → لا أطباء مرتبطون → آمن للحذف
        return (int) $count === 0;
    }


    // --------------------------------------------------------
    // getDoctorCount(): عدد الأطباء في هذا التخصص
    //
    // تُستخدم لعرض رسالة واضحة:
    // "لا يمكن الحذف — يوجد 5 أطباء في هذا التخصص"
    // --------------------------------------------------------
    public function getDoctorCount(int $id): int
    {
        return (int) $this->fetchColumn(
            "SELECT COUNT(*)
             FROM   doctors
             WHERE  specialization_id = ?",
            "i",
            [$id]
        );
    }


    // --------------------------------------------------------
    // delete(): حذف تخصص
    //
    // ⚠️  استدعِ isSafeToDelete() قبل هذه الدالة دائمًا
    //
    // تُرجع: true إذا نجح، false إذا فشل
    // --------------------------------------------------------
    public function delete(int $id): bool
    {
        return (bool) $this->execute(
            "DELETE FROM specializations
             WHERE       id = ?",
            "i",
            [$id]
        );
    }
}