<?php
// classes/UserManager.php
class UserManager {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    public function register($data) {
        $errors = [];
        
        // التحقق من البيانات
        if (empty($data['username']) || strlen($data['username']) < 3) {
            $errors[] = 'اسم المستخدم يجب أن يكون 3 أحرف على الأقل';
        }
        
        if (!validateEmail($data['email'])) {
            $errors[] = 'البريد الإلكتروني غير صحيح';
        }
        
        if (empty($data['password']) || strlen($data['password']) < 6) {
            $errors[] = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل';
        }
        
        if ($data['password'] !== $data['confirm_password']) {
            $errors[] = 'كلمات المرور غير متطابقة';
        }
        
        if (empty($data['full_name'])) {
            $errors[] = 'الاسم الكامل مطلوب';
        }
        
        // التحقق من وجود المستخدم
        if ($this->getUserByUsername($data['username'])) {
            $errors[] = 'اسم المستخدم موجود بالفعل';
        }
        
        if ($this->getUserByEmail($data['email'])) {
            $errors[] = 'البريد الإلكتروني مسجل بالفعل';
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // إنشاء المستخدم
        $activation_token = generateToken();
        $hashed_password = hashPassword($data['password']);
        
        
        
        $sql = "INSERT INTO users (username, email, password, full_name, activation_token) VALUES (?, ?, ?, ?, ?)";
        $result = $this->db->query($sql, [
            sanitize($data['username']),
            sanitize($data['email']),
            $hashed_password,
            sanitize($data['full_name']),
            $activation_token
        ]);
        
        if ($result) {
            // إرسال بريد التفعيل
            // $this->sendActivationEmail($data['email'], $activation_token);
            // return ['success' => true, 'message' => 'تم إنشاء الحساب بنجاح. يرجى فحص بريدك الإلكتروني لتفعيل الحساب'];
            
                $email_sent = $this->sendActivationEmail($data['email'], $activation_token);
    
    if ($email_sent) {
        return ['success' => true, 'message' => 'تم إنشاء الحساب بنجاح. يرجى فحص بريدك الإلكتروني لتفعيل الحساب'];
    } else {
        // حفظ المستخدم مع السماح بإعادة إرسال البريد
        return [
            'success' => true, 
            'message' => 'تم إنشاء الحساب، ولكن لم يتم إرسال بريد التفعيل',
            'resend_activation' => true,
            'user_id' => $this->db->lastInsertId()
        ];
    }


        }
        
        return ['success' => false, 'errors' => ['حدث خطأ أثناء إنشاء الحساب']];
    }
    
       
    public function getUserByActivationToken($token) {
    return $this->db->fetchOne("SELECT * FROM users WHERE activation_token = ?", [$token]);
    }

    public function login($username, $password) {
        $user = $this->getUserByUsername($username) ?: $this->getUserByEmail($username);
        
        if (!$user) {
            return ['success' => false, 'error' => 'اسم المستخدم أو البريد الإلكتروني غير صحيح'];
        }
        
        if (!$user['is_active']) {
            return ['success' => false, 'error' => 'الحساب غير مفعل. يرجى تفعيل حسابك أولاً'];
        }
        
        if (!verifyPassword($password, $user['password'])) {
            return ['success' => false, 'error' => 'كلمة المرور غير صحيحة'];
        }
        
        // تسجيل الدخول
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'];
        
        return ['success' => true, 'user' => $user];
    }
    
    public function activateAccount($token) {
        $user = $this->db->fetchOne("SELECT * FROM users WHERE activation_token = ?", [$token]);
        
        if (!$user) {
            return ['success' => false, 'error' => 'رمز التفعيل غير صحيح'];
        }
        
        if ($user['is_active']) {
            return ['success' => false, 'error' => 'الحساب مفعل بالفعل'];
        }
        
        $result = $this->db->query("UPDATE users SET is_active = 1, activation_token = NULL WHERE id = ?", [$user['id']]);
        
        if ($result) {
            return ['success' => true, 'message' => 'تم تفعيل الحساب بنجاح'];
        }
        
        return ['success' => false, 'error' => 'حدث خطأ أثناء تفعيل الحساب'];
    }
    
    public function resetPassword($email) {
        $user = $this->getUserByEmail($email);
        
        if (!$user) {
            return ['success' => false, 'error' => 'البريد الإلكتروني غير مسجل'];
        }
        
        $reset_token = generateToken();
        $result = $this->db->query("UPDATE users SET reset_token = ? WHERE id = ?", [$reset_token, $user['id']]);
        
        if ($result) {
            $this->sendResetEmail($email, $reset_token);
            return ['success' => true, 'message' => 'تم إرسال رابط إعادة تعيين كلمة المرور إلى بريدك الإلكتروني'];
        }
        
        return ['success' => false, 'error' => 'حدث خطأ أثناء إرسال البريد'];
    }
    
    public function updatePassword($token, $new_password) {
        $user = $this->db->fetchOne("SELECT * FROM users WHERE reset_token = ?", [$token]);
        
        if (!$user) {
            return ['success' => false, 'error' => 'رمز إعادة التعيين غير صحيح'];
        }
        
        if (strlen($new_password) < 6) {
            return ['success' => false, 'error' => 'كلمة المرور يجب أن تكون 6 أحرف على الأقل'];
        }
        
        $hashed_password = hashPassword($new_password);
        $result = $this->db->query("UPDATE users SET password = ?, reset_token = NULL WHERE id = ?", 
            [$hashed_password, $user['id']]);
        
        if ($result) {
            return ['success' => true, 'message' => 'تم تحديث كلمة المرور بنجاح'];
        }
        
        return ['success' => false, 'error' => 'حدث خطأ أثناء تحديث كلمة المرور'];
    }
    
    public function getUserById($id) {
        return $this->db->fetchOne("SELECT * FROM users WHERE id = ?", [$id]);
    }
    
    public function getUserByUsername($username) {
        return $this->db->fetchOne("SELECT * FROM users WHERE username = ?", [$username]);
    }
    
    public function getUserByEmail($email) {
        return $this->db->fetchOne("SELECT * FROM users WHERE email = ?", [$email]);
    }
    
    public function updateProfile($user_id, $data) {
        $errors = [];
        
        if (empty($data['full_name'])) {
            $errors[] = 'الاسم الكامل مطلوب';
        }
        
        if (!validateEmail($data['email'])) {
            $errors[] = 'البريد الإلكتروني غير صحيح';
        }
        
        // التحقق من وجود البريد الإلكتروني
        $existing = $this->db->fetchOne("SELECT id FROM users WHERE email = ? AND id != ?", 
            [$data['email'], $user_id]);
        
        if ($existing) {
            $errors[] = 'البريد الإلكتروني مستخدم بالفعل';
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        $sql = "UPDATE users SET full_name = ?, email = ? WHERE id = ?";
        $result = $this->db->query($sql, [
            sanitize($data['full_name']),
            sanitize($data['email']),
            $user_id
        ]);
        
        if ($result) {
            return ['success' => true, 'message' => 'تم تحديث البيانات بنجاح'];
        }
        
        return ['success' => false, 'errors' => ['حدث خطأ أثناء تحديث البيانات']];
    }
    
    public function changePassword($user_id, $old_password, $new_password) {
        $user = $this->getUserById($user_id);
        
        if (!verifyPassword($old_password, $user['password'])) {
            return ['success' => false, 'error' => 'كلمة المرور الحالية غير صحيحة'];
        }
        
        if (strlen($new_password) < 6) {
            return ['success' => false, 'error' => 'كلمة المرور الجديدة يجب أن تكون 6 أحرف على الأقل'];
        }
        
        $hashed_password = hashPassword($new_password);
        $result = $this->db->query("UPDATE users SET password = ? WHERE id = ?", 
            [$hashed_password, $user_id]);
        
        if ($result) {
            return ['success' => true, 'message' => 'تم تغيير كلمة المرور بنجاح'];
        }
        
        return ['success' => false, 'error' => 'حدث خطأ أثناء تغيير كلمة المرور'];
    }
    
    private function sendActivationEmail($email, $token) {
        $activation_link = SITE_URL . "/activate.php?token=" . $token;
        $subject = "تفعيل حساب - " . SITE_NAME;
        $message = "
        <html>
        <body dir='rtl' style='font-family: Tajawal, Arial, sans-serif;'>
            <h2>مرحباً بك في " . SITE_NAME . "</h2>
            <p>شكراً لك لتسجيلك معنا. يرجى الضغط على الرابط التالي لتفعيل حسابك:</p>
            <p><a href='$activation_link' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>تفعيل الحساب</a></p>
            <p>أو انسخ الرابط التالي في المتصفح:</p>
            <p>$activation_link</p>
            <p>مع تحيات فريق " . SITE_NAME . "</p>
        </body>
        </html>";
        
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= 'From: ' . SITE_NAME . ' <' . ADMIN_EMAIL . '>' . "\r\n";
        
    if (mail($email, $subject, $message, $headers)) {
        return true;
    } else {
        error_log("Failed to send activation email to: $email");
        return false;
    }

    }
    
    private function sendResetEmail($email, $token) {
        $reset_link = SITE_URL . "/reset-password.php?token=" . $token;
        $subject = "إعادة تعيين كلمة المرور - " . SITE_NAME;
        $message = "
        <html>
        <body dir='rtl' style='font-family: Tajawal, Arial, sans-serif;'>
            <h2>إعادة تعيين كلمة المرور</h2>
            <p>تم طلب إعادة تعيين كلمة المرور لحسابك. يرجى الضغط على الرابط التالي:</p>
            <p><a href='$reset_link' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>إعادة تعيين كلمة المرور</a></p>
            <p>أو انسخ الرابط التالي في المتصفح:</p>
            <p>$reset_link</p>
            <p>إذا لم تطلب إعادة تعيين كلمة المرور، يرجى تجاهل هذا البريد.</p>
            <p>مع تحيات فريق " . SITE_NAME . "</p>
        </body>
        </html>";
        
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= 'From: ' . SITE_NAME . ' <' . ADMIN_EMAIL . '>' . "\r\n";
        
        mail($email, $subject, $message, $headers);
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function isAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
    
    public function logout() {
        session_destroy();
        return true;
    }
    
    public function getAllUsers() {
        return $this->db->fetchAll("SELECT id, username, email, full_name, is_active, role, created_at FROM users ORDER BY created_at DESC");
    }
    
    public function toggleUserStatus($user_id) {
        $user = $this->getUserById($user_id);
        if (!$user) return false;
        
        $new_status = $user['is_active'] ? 0 : 1;
        return $this->db->query("UPDATE users SET is_active = ? WHERE id = ?", [$new_status, $user_id]);
    }
    
    public function deleteUser($user_id) {
        // حذف المواقع والبيانات المرتبطة أولاً
        $websites = $this->db->fetchAll("SELECT id FROM websites WHERE user_id = ?", [$user_id]);
        
        $this->db->beginTransaction();
        
        try {
            foreach ($websites as $website) {
                // حذف البيانات المرتبطة بكل موقع
                $this->db->query("DELETE FROM heatmap_data WHERE website_id = ?", [$website['id']]);
                $this->db->query("DELETE FROM conversions WHERE website_id = ?", [$website['id']]);
                $this->db->query("DELETE FROM conversion_goals WHERE website_id = ?", [$website['id']]);
                $this->db->query("DELETE FROM session_recordings WHERE website_id = ?", [$website['id']]);
                $this->db->query("DELETE FROM custom_events WHERE website_id = ?", [$website['id']]);
                $this->db->query("DELETE FROM clicks WHERE website_id = ?", [$website['id']]);
                $this->db->query("DELETE FROM page_views WHERE website_id = ?", [$website['id']]);
                $this->db->query("DELETE FROM sessions WHERE website_id = ?", [$website['id']]);
            }
            
            // حذف المواقع
            $this->db->query("DELETE FROM websites WHERE user_id = ?", [$user_id]);
            
            // حذف المستخدم
            $this->db->query("DELETE FROM users WHERE id = ?", [$user_id]);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            return false;
        }
    }
}
?>