<?php

/**
 * Controlador principal que maneja toda la lógica de autenticación centralizada
 * del ecosistema Reglado. Incluye registro, inicio de sesión, recuperación de 
 * contraseña y actualización de perfiles.
 */
class AuthController
{
    /**
     * Registra un nuevo usuario en estado pendiente.
     * Valida los datos, crea el registro temporal y envía el email de verificación.
     */
    public static function register(): void
    {
        $data = self::getJsonInput();
        $username = trim((string) ($data['username'] ?? ''));
        $firstName = trim((string) ($data['first_name'] ?? ''));
        $lastName = trim((string) ($data['last_name'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        $phone = trim((string) ($data['phone'] ?? ''));
        $password = (string) ($data['password'] ?? '');
        $passwordConfirmation = (string) ($data['password_confirmation'] ?? '');
        RateLimiter::enforce('register', Security::getClientIp(), 10, 900);

        if (
            $username === '' ||
            $firstName === '' ||
            $lastName === '' ||
            $email === '' ||
            $password === '' ||
            $passwordConfirmation === ''
        ) {
            Response::json(['error' => 'username, first_name, last_name, email, password and password_confirmation are required'], 422);
        }

        if (!preg_match('/^[a-zA-Z0-9._-]{3,30}$/', $username)) {
            Response::json(['error' => 'invalid username format'], 422);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::json(['error' => 'invalid email'], 422);
        }

        if ($phone !== '' && !preg_match('/^[0-9]{7,15}$/', $phone)) {
            Response::json(['error' => 'invalid phone'], 422);
        }

        if ($password !== $passwordConfirmation) {
            Response::json(['error' => 'passwords do not match'], 422);
        }

        if (!self::isStrongPassword($password)) {
            Response::json(['error' => 'password too weak'], 422);
        }

        $name = trim($firstName . ' ' . $lastName);
        $hash = password_hash($password, PASSWORD_BCRYPT);
        [$plainToken, $tokenHash, $expiresAt] = self::buildVerificationToken();

        // Respuesta genérica común: si email/username ya existen no lo decimos
        // al cliente (impide enumeración). El usuario legítimo conoce su estado
        // y puede usar /resend-verification o /request-password-reset; un
        // atacante no aprende nada.
        $genericResponse = [
            'message' => 'if the data is valid, a verification email has been sent',
        ];

        try {
            if (User::findByEmail($email) || User::findByUsername($username)) {
                SecurityLogger::log('register_conflict', null, ['email' => $email, 'username' => $username]);
                Response::json($genericResponse, 202);
            }

            if (User::findPendingRegistrationConflict($email, $username)) {
                SecurityLogger::log('register_pending_conflict', null, ['email' => $email, 'username' => $username]);
                Response::json($genericResponse, 202);
            }

            $pendingId = User::createPendingRegistration($username, $email, $hash, $name, $firstName, $lastName, $phone, $tokenHash, $expiresAt);

            $verificationUrl = self::buildVerificationUrl($plainToken);
            $sent = MailService::sendVerificationEmail($email, $name, $verificationUrl);

            if (!$sent) {
                User::deletePendingRegistration($pendingId);
                Response::json(['error' => 'verification email could not be sent'], 500);
            }

            Response::json($genericResponse, 201);
        } catch (PDOException $e) {
            if ((int) $e->getCode() === 23000) {
                SecurityLogger::log('register_conflict', null, ['email' => $email, 'username' => $username]);
                Response::json($genericResponse, 202);
            }
            Response::json(['error' => 'could not create user'], 500);
        }
    }

    /**
     * Inicia sesión verificando credenciales y estado del email.
     *
     * Defensa en profundidad contra fuerza bruta:
     *   1. Throttling por IP+email (5/15 min): limita la tasa de intentos
     *      desde una misma IP contra una misma cuenta.
     *   2. Throttling global por email (20/15 min): corta ataques distribuidos
     *      que rotan IPs contra un mismo email.
     *   3. Account lockout (5 fallos/30 min): bloquea temporalmente la cuenta
     *      tras 5 credenciales inválidas consecutivas, independientemente de
     *      la IP. Se resetea al entrar correctamente.
     *
     * Si las credenciales son válidas, emite un token JWT con la identidad del usuario.
     */
    public static function login(): void
    {
        $data = self::getJsonInput();
        $email = trim((string) ($data['email'] ?? ''));
        $password = (string) ($data['password'] ?? '');
        $normalizedEmail = strtolower($email);

        RateLimiter::enforce('login', Security::getClientIp() . '|' . $normalizedEmail, 5, 900);
        RateLimiter::enforce('login_email', $normalizedEmail, 20, 900);
        RateLimiter::checkFailureLockout('login_lockout', $normalizedEmail, 5, 1800);

        if ($email === '' || $password === '') {
            Response::json(['error' => 'email and password are required'], 422);
        }

        $user = User::findByEmail($email);

        if (!$user || !password_verify($password, $user['password'])) {
            RateLimiter::recordFailure('login_lockout', $normalizedEmail, 1800);
            SecurityLogger::log('login_failed', $user ? (int) $user['id'] : null, ['email' => $email]);
            Response::json(['error' => 'invalid credentials'], 401);
        }

        if ((int) ($user['is_email_verified'] ?? 0) !== 1) {
            SecurityLogger::log('login_blocked_unverified', (int) $user['id'], ['email' => $email]);
            Response::json(['error' => 'email not verified'], 403);
        }

        if (!empty($user['banned_at'])) {
            SecurityLogger::log('login_blocked_banned', (int) $user['id'], ['email' => $email]);
            Response::json(['error' => 'account banned'], 403);
        }

        RateLimiter::resetFailure('login_lockout', $normalizedEmail);
        $token = JwtService::generate($user);
        SecurityLogger::log('login_success', (int) $user['id']);

        Response::json([
            'token' => $token,
            'user' => [
                'id' => (int) $user['id'],
                'username' => $user['username'] ?? null,
                'first_name' => $user['first_name'] ?? null,
                'last_name' => $user['last_name'] ?? null,
                'phone' => $user['phone'] ?? null,
                'name' => $user['name'],
                'role' => $user['role'],
                'email' => $user['email'],
            ],
        ]);
    }

    public static function verifyEmail(): void
    {
        $token = trim((string) ($_GET['token'] ?? ''));
        if ($token === '') {
            Response::json(['error' => 'verification token is required'], 422);
        }

        $tokenHash = hash('sha256', $token);
        $user = User::findByVerificationHash($tokenHash);

        try {
            if ($user) {
                User::markEmailAsVerified((int) $user['id']);
                $freshUser = User::findByEmail((string) $user['email']);
                if (!$freshUser) {
                    Response::json(['error' => 'could not load verified user'], 500);
                }
            } else {
                $pending = User::findPendingRegistrationByTokenHash($tokenHash);
                if (!$pending) {
                    Response::json(['error' => 'invalid or expired verification token'], 400);
                }

                if (User::findByEmail((string) $pending['email']) || User::findByUsername((string) $pending['username'])) {
                    Response::json(['error' => 'email or username already exists'], 409);
                }

                $freshUser = User::createUserFromPendingRegistration((int) $pending['id']);

                // Notion es un espejo operativo: si falla, no se bloquea el alta local.
                try {
                    NotionService::syncUserCreated($freshUser);
                } catch (Throwable $syncError) {
                    error_log('[AuthController] Notion sync failed: ' . $syncError->getMessage());
                }
            }

            $jwt = JwtService::generate($freshUser);
            $redirectBase = getenv('EMAIL_VERIFY_REDIRECT_URL') ?: '';

            if ($redirectBase !== '' && Security::isAllowedAbsoluteUrl($redirectBase, 'REDIRECT_ALLOWED_ORIGINS')) {
                $separator = str_contains($redirectBase, '?') ? '&' : '?';
                $location = $redirectBase . $separator . 'token=' . urlencode($jwt);
                header('Location: ' . $location, true, 302);
                exit;
            }

            Response::json([
                'message' => 'email verified',
                'token' => $jwt,
                'user' => [
                    'id' => (int) $freshUser['id'],
                    'username' => $freshUser['username'] ?? null,
                    'first_name' => $freshUser['first_name'] ?? null,
                    'last_name' => $freshUser['last_name'] ?? null,
                    'phone' => $freshUser['phone'] ?? null,
                    'name' => $freshUser['name'],
                    'role' => $freshUser['role'],
                    'email' => $freshUser['email'],
                ],
            ]);
        } catch (Throwable $e) {
            Response::json(['error' => 'could not verify email'], 500);
        }
    }

    public static function resendVerification(): void
    {
        $data = self::getJsonInput();
        $email = trim((string) ($data['email'] ?? ''));
        RateLimiter::enforce('resend_verification', Security::getClientIp() . '|' . strtolower($email), 5, 900);

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::json(['error' => 'valid email is required'], 422);
        }

        $user = User::findByEmail($email);
        if (!$user) {
            $pending = User::findPendingRegistrationByEmail($email);
            if ($pending) {
                [$plainToken, $tokenHash, $expiresAt] = self::buildVerificationToken();
                User::refreshPendingRegistrationToken((int) $pending['id'], $tokenHash, $expiresAt);
                $url = self::buildVerificationUrl($plainToken);
                $sent = MailService::sendVerificationEmail((string) $pending['email'], (string) $pending['name'], $url);

                if (!$sent) {
                    Response::json(['error' => 'could not send verification email'], 500);
                }

                Response::json(['message' => 'verification email sent']);
            }

            Response::json(['message' => 'if the account exists, a verification email was sent']);
        }

        if ((int) ($user['is_email_verified'] ?? 0) === 1) {
            Response::json(['message' => 'email is already verified']);
        }

        [$plainToken, $tokenHash, $expiresAt] = self::buildVerificationToken();
        User::createVerificationToken((int) $user['id'], $tokenHash, $expiresAt);
        $url = self::buildVerificationUrl($plainToken);
        $sent = MailService::sendVerificationEmail((string) $user['email'], (string) $user['name'], $url);

        if (!$sent) {
            Response::json(['error' => 'could not send verification email'], 500);
        }

        Response::json(['message' => 'verification email sent']);
    }

    /**
     * Solicita el envío de un correo para recuperar la contraseña.
     * Genera un token temporal y envía las instrucciones por email.
     */
    public static function requestPasswordReset(): void
    {
        $data = self::getJsonInput();
        $email = trim((string) ($data['email'] ?? ''));
        RateLimiter::enforce('request_password_reset', Security::getClientIp() . '|' . strtolower($email), 5, 900);

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::json(['error' => 'valid email is required'], 422);
        }

        $user = User::findByEmail($email);
        if (!$user) {
            SecurityLogger::log('password_reset_requested', null, ['email' => $email, 'result' => 'not_found']);
            Response::json(['message' => 'if the account exists, a recovery email was sent']);
        }

        [$plainToken, $tokenHash, $expiresAt] = self::buildVerificationToken();

        try {
            User::createPasswordResetToken((int) $user['id'], $tokenHash, $expiresAt);
            $resetUrl = self::buildPasswordResetUrl($plainToken);
            $sent = MailService::sendPasswordResetEmail((string) $user['email'], (string) $user['name'], $resetUrl);

            if (!$sent) {
                Response::json(['error' => 'could not send password reset email'], 500);
            }

            SecurityLogger::log('password_reset_requested', (int) $user['id']);
            Response::json(['message' => 'if the account exists, a recovery email was sent']);
        } catch (Throwable $e) {
            Response::json(['error' => 'could not request password reset'], 500);
        }
    }

    /**
     * Permite restablecer la contraseña validando el token enviado por correo.
     * Si es válido, actualiza la contraseña del usuario.
     */
    public static function resetPassword(): void
    {
        $data = self::getJsonInput();
        $token = trim((string) ($data['token'] ?? ''));
        $newPassword = (string) ($data['new_password'] ?? '');
        $newPasswordConfirmation = (string) ($data['new_password_confirmation'] ?? '');

        if ($token === '' || $newPassword === '' || $newPasswordConfirmation === '') {
            Response::json(['error' => 'token, new_password and new_password_confirmation are required'], 422);
        }

        if ($newPassword !== $newPasswordConfirmation) {
            Response::json(['error' => 'new passwords do not match'], 422);
        }

        if (!self::isStrongPassword($newPassword)) {
            Response::json(['error' => 'password too weak'], 422);
        }

        $tokenHash = hash('sha256', $token);
        $user = User::findByPasswordResetHash($tokenHash);

        if (!$user) {
            SecurityLogger::log('password_reset_failed', null, ['reason' => 'invalid_token']);
            Response::json(['error' => 'invalid or expired reset token'], 400);
        }

        if (password_verify($newPassword, (string) $user['password'])) {
            Response::json(['error' => 'new password must be different from current password'], 422);
        }

        try {
            User::updatePasswordHash((int) $user['id'], password_hash($newPassword, PASSWORD_BCRYPT));
            User::markPasswordResetAsUsed((int) $user['reset_id']);
            SecurityLogger::log('password_reset_completed', (int) $user['id']);
            Response::json(['message' => 'password updated']);
        } catch (Throwable $e) {
            Response::json(['error' => 'could not reset password'], 500);
        }
    }

    public static function updateUsername(): void
    {
        $session = AuthMiddleware::handle();
        $userId = (int) ($session['sub'] ?? 0);
        $data = self::getJsonInput();
        $username = trim((string) ($data['username'] ?? ''));

        if (!preg_match('/^[a-zA-Z0-9._-]{3,30}$/', $username)) {
            Response::json(['error' => 'invalid username format'], 422);
        }

        try {
            User::updateUsername($userId, $username);
            self::respondWithFreshSession($userId, 'username updated');
        } catch (PDOException $e) {
            if ((int) $e->getCode() === 23000) {
                Response::json(['error' => 'username already exists'], 409);
            }
            Response::json(['error' => 'could not update username'], 500);
        }
    }

    public static function updateName(): void
    {
        $session = AuthMiddleware::handle();
        $userId = (int) ($session['sub'] ?? 0);
        $data = self::getJsonInput();
        $firstName = trim((string) ($data['first_name'] ?? ''));
        $lastName = trim((string) ($data['last_name'] ?? ''));

        if ($firstName === '' || $lastName === '') {
            Response::json(['error' => 'first_name and last_name are required'], 422);
        }

        try {
            User::updateName($userId, $firstName, $lastName);
            self::respondWithFreshSession($userId, 'name updated');
        } catch (Throwable $e) {
            Response::json(['error' => 'could not update name'], 500);
        }
    }

    public static function updatePhone(): void
    {
        $session = AuthMiddleware::handle();
        $userId = (int) ($session['sub'] ?? 0);
        $data = self::getJsonInput();
        $phone = trim((string) ($data['phone'] ?? ''));

        if ($phone !== '' && !preg_match('/^[0-9]{7,15}$/', $phone)) {
            Response::json(['error' => 'invalid phone'], 422);
        }

        try {
            User::updatePhone($userId, $phone);
            self::respondWithFreshSession($userId, 'phone updated');
        } catch (Throwable $e) {
            Response::json(['error' => 'could not update phone'], 500);
        }
    }

    /**
     * Solicita cambio del email asociado a la cuenta. Exige re-autenticación
     * con la contraseña actual: sin esto, un JWT robado bastaría para tomar
     * la cuenta (cambio de email -> reset de password -> takeover total).
     *
     * Las respuestas son intencionalmente genéricas para no permitir que un
     * usuario logueado enumere otros emails registrados en el sistema.
     */
    public static function requestEmailChange(): void
    {
        $session = AuthMiddleware::handle();
        $userId = (int) ($session['sub'] ?? 0);
        $data = self::getJsonInput();
        $newEmail = trim((string) ($data['new_email'] ?? ''));
        $currentPassword = (string) ($data['current_password'] ?? '');
        RateLimiter::enforce('request_email_change', Security::getClientIp() . '|' . $userId, 5, 900);

        if ($currentPassword === '') {
            Response::json(['error' => 'current_password is required'], 422);
        }

        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            Response::json(['error' => 'invalid email'], 422);
        }

        $currentUser = User::findById($userId);
        if (!$currentUser) {
            Response::json(['error' => 'user not found'], 404);
        }

        if (!password_verify($currentPassword, (string) $currentUser['password'])) {
            SecurityLogger::log('email_change_bad_password', $userId);
            Response::json(['error' => 'current password is incorrect'], 401);
        }

        if (strcasecmp((string) $currentUser['email'], $newEmail) === 0) {
            Response::json(['error' => 'new email must be different from current email'], 422);
        }

        // Mensaje único: si el email ya está en uso por otro, devolvemos lo
        // mismo que si todo fue OK. El destinatario legítimo recibirá (o no)
        // el correo; el atacante no puede saber qué emails están registrados.
        $genericResponse = ['message' => 'if the email is available, a confirmation has been sent'];

        if (User::findByEmail($newEmail)) {
            SecurityLogger::log('email_change_target_exists', $userId, ['new_email' => $newEmail]);
            Response::json($genericResponse);
        }

        [$plainToken, $tokenHash, $expiresAt] = self::buildVerificationToken();

        try {
            User::createEmailChangeToken($userId, $newEmail, $tokenHash, $expiresAt);
            $confirmationUrl = self::buildEmailChangeUrl($plainToken);
            $sent = MailService::sendEmailChangeConfirmation($newEmail, (string) $currentUser['name'], $confirmationUrl);

            if (!$sent) {
                Response::json(['error' => 'could not send email change confirmation'], 500);
            }

            SecurityLogger::log('email_change_requested', $userId, ['new_email' => $newEmail]);
            Response::json($genericResponse);
        } catch (Throwable $e) {
            Response::json(['error' => 'could not request email change'], 500);
        }
    }

    public static function confirmEmailChange(): void
    {
        $token = trim((string) ($_GET['token'] ?? ''));
        if ($token === '') {
            Response::json(['error' => 'confirmation token is required'], 422);
        }

        $tokenHash = hash('sha256', $token);
        $pending = User::findPendingEmailChange($tokenHash);

        if (!$pending) {
            Response::json(['error' => 'invalid or expired confirmation token'], 400);
        }

        if (User::findByEmail((string) $pending['new_email'])) {
            Response::json(['error' => 'email already exists'], 409);
        }

        try {
            User::applyEmailChange((int) $pending['id'], (int) $pending['user_id'], (string) $pending['new_email']);
            $freshUser = User::findById((int) $pending['user_id']);
            if (!$freshUser) {
                Response::json(['error' => 'could not load updated user'], 500);
            }

            $jwt = JwtService::generate($freshUser);
            $redirectBase = getenv('EMAIL_CHANGE_REDIRECT_URL') ?: '';

            if ($redirectBase !== '' && Security::isAllowedAbsoluteUrl($redirectBase, 'REDIRECT_ALLOWED_ORIGINS')) {
                $separator = str_contains($redirectBase, '?') ? '&' : '?';
                $location = $redirectBase . $separator . 'token=' . urlencode($jwt);
                header('Location: ' . $location, true, 302);
                exit;
            }

            Response::json([
                'message' => 'email updated',
                'token' => $jwt,
                'user' => self::mapUser($freshUser),
            ]);
        } catch (Throwable $e) {
            Response::json(['error' => 'could not confirm email change'], 500);
        }
    }

    public static function changePassword(): void
    {
        $session = AuthMiddleware::handle();
        $userId = (int) ($session['sub'] ?? 0);
        $data = self::getJsonInput();
        $currentPassword = (string) ($data['current_password'] ?? '');
        $newPassword = (string) ($data['new_password'] ?? '');
        $newPasswordConfirmation = (string) ($data['new_password_confirmation'] ?? '');

        if ($currentPassword === '' || $newPassword === '' || $newPasswordConfirmation === '') {
            Response::json(['error' => 'current_password, new_password and new_password_confirmation are required'], 422);
        }

        if ($newPassword !== $newPasswordConfirmation) {
            Response::json(['error' => 'new passwords do not match'], 422);
        }

        if (!self::isStrongPassword($newPassword)) {
            Response::json(['error' => 'password too weak'], 422);
        }

        $user = User::findById($userId);
        if (!$user || !password_verify($currentPassword, (string) $user['password'])) {
            Response::json(['error' => 'current password is incorrect'], 401);
        }

        if (password_verify($newPassword, (string) $user['password'])) {
            Response::json(['error' => 'new password must be different from current password'], 422);
        }

        try {
            User::updatePasswordHash($userId, password_hash($newPassword, PASSWORD_BCRYPT));
            SecurityLogger::log('password_changed', $userId);
            self::respondWithFreshSession($userId, 'password updated');
        } catch (Throwable $e) {
            Response::json(['error' => 'could not update password'], 500);
        }
    }

    /**
     * Obtiene y devuelve los datos del perfil del usuario logueado
     * mediante la información contenida en el token JWT.
     */
    public static function me(): void
    {
        $session = AuthMiddleware::handle();
        $userId = (int) ($session['sub'] ?? 0);
        $user = User::findById($userId);

        if (!$user) {
            Response::json(['error' => 'user not found'], 404);
        }

        Response::json(['user' => self::mapUser($user)]);
    }

    public static function adminUsers(): void
    {
        self::requireAdmin();

        $users = array_map(
            static function (array $user): array {
                return [
                    'id' => (int) $user['id'],
                    'username' => $user['username'] ?? null,
                    'first_name' => $user['first_name'] ?? null,
                    'last_name' => $user['last_name'] ?? null,
                    'phone' => $user['phone'] ?? null,
                    'name' => $user['name'] ?? null,
                    'role' => $user['role'] ?? null,
                    'email' => $user['email'] ?? null,
                    'is_email_verified' => (int) ($user['is_email_verified'] ?? 0),
                    'email_verified_at' => $user['email_verified_at'] ?? null,
                    'created_at' => $user['created_at'] ?? null,
                ];
            },
            User::listAll()
        );

        Response::json(['users' => $users]);
    }

    public static function adminUpdateRole(): void
    {
        $session = self::requireAdmin();
        $adminId = (int) ($session['sub'] ?? 0);

        $data = self::getJsonInput();
        $userId = (int) ($data['user_id'] ?? 0);
        $role = trim((string) ($data['role'] ?? ''));
        $currentPassword = (string) ($data['current_password'] ?? '');

        if ($userId <= 0 || $role === '') {
            Response::json(['error' => 'user_id and role are required'], 422);
        }

        if ($currentPassword === '') {
            Response::json(['error' => 'current_password is required'], 422);
        }

        if (!in_array($role, ['user', 'real', 'admin'])) {
            Response::json(['error' => 'invalid role'], 422);
        }

        // Reautenticación con la contraseña del admin actual: si su JWT estuviera
        // comprometido, el atacante todavía no podría escalar privilegios sin
        // conocer también la contraseña.
        $adminUser = User::findById($adminId);
        if (!$adminUser || !password_verify($currentPassword, (string) $adminUser['password'])) {
            SecurityLogger::log('admin_role_change_bad_password', $adminId);
            Response::json(['error' => 'current password is incorrect'], 401);
        }

        $targetUser = User::findById($userId);
        if (!$targetUser) {
            Response::json(['error' => 'user not found'], 404);
        }

        // Salvaguarda contra lockout administrativo: si bajamos al último admin
        // se quedaría el sistema sin nadie capaz de gestionar roles.
        $isDemotingAdmin = ($targetUser['role'] ?? '') === 'admin' && $role !== 'admin';
        if ($isDemotingAdmin && User::countAdmins() <= 1) {
            Response::json(['error' => 'cannot demote the last remaining admin'], 409);
        }

        try {
            User::updateRole($userId, $role);
            SecurityLogger::log('admin_updated_user_role', $userId, ['new_role' => $role, 'by_admin' => $adminId]);

            $freshUser = User::findById($userId);
            if ($freshUser) {
                try {
                    NotionService::syncUserUpdated($freshUser);
                } catch (Throwable $syncError) {
                    error_log('[AuthController] Notion sync for updated user failed: ' . $syncError->getMessage());
                }
            }

            Response::json(['message' => 'role updated']);
        } catch (Throwable $e) {
            Response::json(['error' => 'could not update role'], 500);
        }
    }

    public static function adminSyncNotion(): void
    {
        $session = self::requireAdmin();
        $adminId = (int) ($session['sub'] ?? 0);

        // Endpoint pesado (clear + re-sync de toda la BBDD contra una API
        // externa con su propio rate limit). Throttle por admin para evitar
        // que un admin malicioso o un script accidental disparen tormentas.
        RateLimiter::enforce('admin_sync_notion', (string) $adminId, 5, 3600);

        $users = User::listAll();

        try {
            $clearError = NotionService::clearDatabase();
            if ($clearError !== '') {
                error_log('[AuthController] adminSyncNotion clear error: ' . $clearError);
                Response::json(['error' => 'could not synchronize notion'], 500);
            }

            $syncedCount = 0;
            foreach ($users as $user) {
                if (NotionService::syncUserCreated($user)) {
                    $syncedCount++;
                }
            }

            SecurityLogger::log('admin_synced_notion', $adminId, ['synced' => $syncedCount]);

            Response::json([
                'message' => 'Notion synchronized successfully',
                'synced_count' => $syncedCount,
                'total_users' => count($users)
            ]);
        } catch (Throwable $e) {
            // Detalles solo en log del servidor; al cliente, mensaje genérico.
            error_log('[AuthController] adminSyncNotion Error: ' . $e->getMessage());
            Response::json(['error' => 'could not synchronize notion'], 500);
        }
    }

    public static function logout(): void
    {
        $token = AuthMiddleware::extractBearerToken();

        if ($token === null) {
            Response::json(['error' => 'unauthorized'], 401);
        }

        // Verificación de firma ANTES de tocar la BBDD: impide que un atacante
        // sature `revoked_tokens` enviando bearers basura (DoS lento que
        // degrada cada request autenticada por el SELECT del middleware).
        try {
            $decoded = JwtService::verify($token);
        } catch (Throwable $e) {
            SecurityLogger::log('logout_invalid_token', null);
            Response::json(['error' => 'invalid token'], 401);
        }

        // Rate limit por IP también (defensa adicional aunque el JWT sea válido).
        RateLimiter::enforce('logout', Security::getClientIp(), 30, 60);

        try {
            // Solo se persiste el hash del token: si la BBDD se filtra, los
            // JWTs (incluso revocados) no quedan expuestos en plano.
            $tokenHash = hash('sha256', $token);
            $db = Database::connect();
            $stmt = $db->prepare('INSERT INTO revoked_tokens(token_hash) VALUES(?)');
            $stmt->execute([$tokenHash]);
            SecurityLogger::log('logout', isset($decoded['sub']) ? (int) $decoded['sub'] : null);
            Response::json(['message' => 'logged out']);
        } catch (Throwable $e) {
            Response::json(['error' => 'could not logout'], 500);
        }
    }

    private static function getJsonInput(): array
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw ?: '{}', true);

        return is_array($data) ? $data : [];
    }

    private static function buildVerificationToken(): array
    {
        $ttlSeconds = (int) (getenv('EMAIL_VERIFICATION_TTL_SECONDS') ?: 86400);
        $plainToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $plainToken);
        $expiresAt = date('Y-m-d H:i:s', time() + $ttlSeconds);

        return [$plainToken, $tokenHash, $expiresAt];
    }

    private static function buildVerificationUrl(string $plainToken): string
    {
        return self::buildUrlFromEnv(
            'EMAIL_VERIFY_URL_BASE',
            'http://localhost:8000/auth/verify-email',
            $plainToken
        );
    }

    private static function buildEmailChangeUrl(string $plainToken): string
    {
        return self::buildUrlFromEnv(
            'EMAIL_CHANGE_VERIFY_URL_BASE',
            'http://localhost:8000/auth/confirm-email-change',
            $plainToken
        );
    }

    private static function buildPasswordResetUrl(string $plainToken): string
    {
        return self::buildUrlFromEnv(
            'PASSWORD_RESET_URL_BASE',
            'http://localhost:5173/restablecer-contrasena',
            $plainToken
        );
    }

    /**
     * Construye una URL pública con un token. En `local` permite el fallback
     * a `http://localhost...` para no romper la DX, pero en cualquier otro
     * entorno (staging, production) exige que la env var esté definida: así
     * un despliegue mal configurado no envía emails con enlaces a localhost
     * que pueden parecer phishing o llevar al sitio equivocado.
     */
    private static function buildUrlFromEnv(string $envKey, string $localFallback, string $plainToken): string
    {
        $base = (string) (getenv($envKey) ?: '');
        $appEnv = strtolower((string) (getenv('APP_ENV') ?: 'local'));

        if ($base === '') {
            if ($appEnv !== 'local') {
                error_log('AuthController: ' . $envKey . ' no definida en APP_ENV=' . $appEnv);
                Response::json(['error' => 'service misconfigured'], 500);
            }
            $base = $localFallback;
        }

        $separator = str_contains($base, '?') ? '&' : '?';
        return $base . $separator . 'token=' . urlencode($plainToken);
    }

    private static function respondWithFreshSession(int $userId, string $message): void
    {
        $user = User::findById($userId);
        if (!$user) {
            Response::json(['error' => 'user not found'], 404);
        }

        // Cada cambio de perfil devuelve un JWT nuevo para no dejar datos obsoletos en cliente.
        $token = JwtService::generate($user);

        Response::json([
            'message' => $message,
            'token' => $token,
            'user' => self::mapUser($user),
        ]);
    }

    private static function mapUser(array $user): array
    {
        return [
            'id' => (int) $user['id'],
            'username' => $user['username'] ?? null,
            'first_name' => $user['first_name'] ?? null,
            'last_name' => $user['last_name'] ?? null,
            'phone' => $user['phone'] ?? null,
            'name' => $user['name'] ?? null,
            'role' => $user['role'] ?? null,
            'email' => $user['email'] ?? null,
        ];
    }

    private static function requireAdmin(): array
    {
        $session = AuthMiddleware::handle();

        if (($session['role'] ?? null) !== 'admin') {
            Response::json(['error' => 'forbidden'], 403);
        }

        return $session;
    }

    /**
     * Valida que la contraseña cumpla los requisitos mínimos de seguridad:
     * al menos 8 caracteres, una letra mayúscula y un número.
     */
    private static function isStrongPassword(string $password): bool
    {
        return strlen($password) >= 8
            && preg_match('/[A-Z]/', $password) === 1
            && preg_match('/[0-9]/', $password) === 1;
    }
}
