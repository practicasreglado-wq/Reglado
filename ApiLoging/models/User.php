<?php

class User
{
    public static function findById(int $id): ?array
    {
        $db = Database::connect();
        $stmt = $db->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public static function findByEmail(string $email): ?array
    {
        $db = Database::connect();
        $stmt = $db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public static function findByUsername(string $username): ?array
    {
        $db = Database::connect();
        $stmt = $db->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public static function create(
        string $username,
        string $email,
        string $passwordHash,
        string $name,
        string $firstName,
        string $lastName,
        string $phone,
        string $role = 'user'
    ): int
    {
        $db = Database::connect();
        $stmt = $db->prepare(
            'INSERT INTO users(username, email, password, name, first_name, last_name, phone, role, is_email_verified)
             VALUES(?, ?, ?, ?, ?, ?, ?, ?, 0)'
        );
        $stmt->execute([$username, $email, $passwordHash, $name, $firstName, $lastName, $phone, $role]);

        return (int) $db->lastInsertId();
    }

    public static function createPendingRegistration(
        string $username,
        string $email,
        string $passwordHash,
        string $name,
        string $firstName,
        string $lastName,
        string $phone,
        string $tokenHash,
        string $expiresAt
    ): int {
        $db = Database::connect();
        $cleanup = $db->prepare('DELETE FROM pending_registrations WHERE email = ? OR username = ?');
        $cleanup->execute([$email, $username]);

        $stmt = $db->prepare(
            'INSERT INTO pending_registrations(username, email, password_hash, name, first_name, last_name, phone, token_hash, expires_at)
             VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$username, $email, $passwordHash, $name, $firstName, $lastName, $phone !== '' ? $phone : null, $tokenHash, $expiresAt]);

        return (int) $db->lastInsertId();
    }

    public static function deletePendingRegistration(int $pendingId): void
    {
        $db = Database::connect();
        $stmt = $db->prepare('DELETE FROM pending_registrations WHERE id = ?');
        $stmt->execute([$pendingId]);
    }

    public static function findPendingRegistrationConflict(string $email, string $username): ?array
    {
        $db = Database::connect();
        $stmt = $db->prepare(
            'SELECT id, email, username
             FROM pending_registrations
             WHERE used_at IS NULL
               AND expires_at > NOW()
               AND (email = ? OR username = ?)
             LIMIT 1'
        );
        $stmt->execute([$email, $username]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function findPendingRegistrationByEmail(string $email): ?array
    {
        $db = Database::connect();
        $stmt = $db->prepare(
            'SELECT *
             FROM pending_registrations
             WHERE email = ?
               AND used_at IS NULL
               AND expires_at > NOW()
             LIMIT 1'
        );
        $stmt->execute([$email]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function refreshPendingRegistrationToken(int $pendingId, string $tokenHash, string $expiresAt): void
    {
        $db = Database::connect();
        $stmt = $db->prepare(
            'UPDATE pending_registrations
             SET token_hash = ?, expires_at = ?, used_at = NULL
             WHERE id = ?'
        );
        $stmt->execute([$tokenHash, $expiresAt, $pendingId]);
    }

    public static function findPendingRegistrationByTokenHash(string $tokenHash): ?array
    {
        $db = Database::connect();
        $stmt = $db->prepare(
            'SELECT *
             FROM pending_registrations
             WHERE token_hash = ?
               AND used_at IS NULL
               AND expires_at > NOW()
             LIMIT 1'
        );
        $stmt->execute([$tokenHash]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function createUserFromPendingRegistration(int $pendingId): array
    {
        $db = Database::connect();
        $db->beginTransaction();

        try {
            $select = $db->prepare('SELECT * FROM pending_registrations WHERE id = ? AND used_at IS NULL LIMIT 1');
            $select->execute([$pendingId]);
            $pending = $select->fetch();

            if (!$pending) {
                throw new RuntimeException('pending registration not found');
            }

            $insert = $db->prepare(
                'INSERT INTO users(username, email, password, name, first_name, last_name, phone, role, is_email_verified, email_verified_at)
                 VALUES(?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())'
            );
            $insert->execute([
                $pending['username'],
                $pending['email'],
                $pending['password_hash'],
                $pending['name'],
                $pending['first_name'],
                $pending['last_name'],
                $pending['phone'],
                'user',
            ]);

            $userId = (int) $db->lastInsertId();

            $deletePending = $db->prepare('DELETE FROM pending_registrations WHERE id = ?');
            $deletePending->execute([$pendingId]);

            $user = $db->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
            $user->execute([$userId]);
            $freshUser = $user->fetch();

            $db->commit();

            if (!$freshUser) {
                throw new RuntimeException('created user not found');
            }

            return $freshUser;
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public static function updateUsername(int $userId, string $username): void
    {
        $db = Database::connect();
        $stmt = $db->prepare('UPDATE users SET username = ? WHERE id = ?');
        $stmt->execute([$username, $userId]);
    }

    public static function updateRole(int $userId, string $role): void
    {
        $db = Database::connect();
        $stmt = $db->prepare('UPDATE users SET role = ? WHERE id = ?');
        $stmt->execute([$role, $userId]);
    }

    public static function banUser(int $userId, int $adminId): void
    {
        $db = Database::connect();
        $stmt = $db->prepare(
            'UPDATE users SET banned_at = NOW(), banned_by = ?, sessions_invalidated_at = NOW(), current_session_id = NULL WHERE id = ?'
        );
        $stmt->execute([$adminId, $userId]);
    }

    public static function unbanUser(int $userId): void
    {
        // Nota: sessions_invalidated_at NO se limpia a propósito. Los JWTs que
        // el usuario tuviera antes del ban deben seguir siendo inválidos.
        $db = Database::connect();
        $stmt = $db->prepare('UPDATE users SET banned_at = NULL, banned_by = NULL WHERE id = ?');
        $stmt->execute([$userId]);
    }

    public static function invalidateSessions(int $userId): void
    {
        $db = Database::connect();
        $stmt = $db->prepare('UPDATE users SET sessions_invalidated_at = NOW() WHERE id = ?');
        $stmt->execute([$userId]);
    }

    /**
     * Genera un nuevo session id para el usuario y lo persiste. Devuelve el
     * sid para incluirlo en el JWT recién emitido. La sesión anterior queda
     * invalidada en el middleware por no coincidir con el sid guardado.
     */
    public static function rotateSession(int $userId): string
    {
        $sid = bin2hex(random_bytes(32));
        $db = Database::connect();
        $stmt = $db->prepare('UPDATE users SET current_session_id = ? WHERE id = ?');
        $stmt->execute([$sid, $userId]);
        return $sid;
    }

    /**
     * Elimina la sesión activa del usuario. Los JWTs existentes dejarán de
     * validar en el middleware al no coincidir su sid con NULL.
     */
    public static function clearSession(int $userId): void
    {
        $db = Database::connect();
        $stmt = $db->prepare('UPDATE users SET current_session_id = NULL WHERE id = ?');
        $stmt->execute([$userId]);
    }

    public static function updateName(int $userId, string $firstName, string $lastName): void
    {
        $db = Database::connect();
        $fullName = trim($firstName . ' ' . $lastName);
        $stmt = $db->prepare('UPDATE users SET first_name = ?, last_name = ?, name = ? WHERE id = ?');
        $stmt->execute([$firstName, $lastName, $fullName, $userId]);
    }

    public static function updatePhone(int $userId, string $phone): void
    {
        $db = Database::connect();
        $stmt = $db->prepare('UPDATE users SET phone = ? WHERE id = ?');
        $stmt->execute([$phone, $userId]);
    }

    public static function updatePasswordHash(int $userId, string $passwordHash): void
    {
        // password_changed_at se actualiza junto al hash: el middleware lo usa
        // para invalidar JWTs emitidos antes del cambio (ver AuthMiddleware).
        $db = Database::connect();
        $stmt = $db->prepare('UPDATE users SET password = ?, password_changed_at = NOW() WHERE id = ?');
        $stmt->execute([$passwordHash, $userId]);
    }

    /**
     * Devuelve el estado de seguridad del usuario: timestamps de último cambio
     * de contraseña, ban activo, invalidación masiva de sesiones y session id
     * actual. Lo usa el middleware para decidir si un JWT sigue siendo válido.
     *
     * @return array{password_changed_at: ?int, banned_at: ?int, sessions_invalidated_at: ?int, current_session_id: ?string}
     */
    public static function getSecurityState(int $userId): array
    {
        $db = Database::connect();
        $stmt = $db->prepare('SELECT password_changed_at, banned_at, sessions_invalidated_at, current_session_id FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $row = $stmt->fetch();

        $toTs = static function ($value): ?int {
            if (empty($value)) return null;
            $ts = strtotime((string) $value);
            return $ts !== false ? $ts : null;
        };

        return [
            'password_changed_at' => $toTs($row['password_changed_at'] ?? null),
            'banned_at' => $toTs($row['banned_at'] ?? null),
            'sessions_invalidated_at' => $toTs($row['sessions_invalidated_at'] ?? null),
            'current_session_id' => $row['current_session_id'] ?? null,
        ];
    }

    /**
     * Cuenta cuántos usuarios tienen rol admin. Se usa para impedir que un
     * admin se rebaje a sí mismo (o a otro) y deje el sistema sin admins.
     */
    public static function countAdmins(): int
    {
        $db = Database::connect();
        $stmt = $db->query("SELECT COUNT(*) AS n FROM users WHERE role = 'admin'");
        $row = $stmt->fetch();

        return (int) ($row['n'] ?? 0);
    }

    public static function createVerificationToken(int $userId, string $tokenHash, string $expiresAt): void
    {
        $db = Database::connect();
        $delete = $db->prepare('DELETE FROM email_verification_tokens WHERE user_id = ?');
        $delete->execute([$userId]);

        $stmt = $db->prepare(
            'INSERT INTO email_verification_tokens(user_id, token_hash, expires_at) VALUES(?, ?, ?)'
        );
        $stmt->execute([$userId, $tokenHash, $expiresAt]);
    }

    public static function createPasswordResetToken(int $userId, string $tokenHash, string $expiresAt): void
    {
        $db = Database::connect();
        $delete = $db->prepare('DELETE FROM password_reset_tokens WHERE user_id = ?');
        $delete->execute([$userId]);

        $stmt = $db->prepare(
            'INSERT INTO password_reset_tokens(user_id, token_hash, expires_at) VALUES(?, ?, ?)'
        );
        $stmt->execute([$userId, $tokenHash, $expiresAt]);
    }

    public static function findByVerificationHash(string $tokenHash): ?array
    {
        $db = Database::connect();
        $stmt = $db->prepare(
            'SELECT u.*
             FROM email_verification_tokens evt
             INNER JOIN users u ON u.id = evt.user_id
             WHERE evt.token_hash = ?
               AND evt.used_at IS NULL
               AND evt.expires_at > NOW()
             LIMIT 1'
        );
        $stmt->execute([$tokenHash]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public static function findByPasswordResetHash(string $tokenHash): ?array
    {
        $db = Database::connect();
        $stmt = $db->prepare(
            'SELECT prt.id as reset_id, u.*
             FROM password_reset_tokens prt
             INNER JOIN users u ON u.id = prt.user_id
             WHERE prt.token_hash = ?
               AND prt.used_at IS NULL
               AND prt.expires_at > NOW()
             LIMIT 1'
        );
        $stmt->execute([$tokenHash]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public static function createEmailChangeToken(int $userId, string $newEmail, string $tokenHash, string $expiresAt): void
    {
        $db = Database::connect();
        $delete = $db->prepare('DELETE FROM email_change_tokens WHERE user_id = ?');
        $delete->execute([$userId]);

        $stmt = $db->prepare(
            'INSERT INTO email_change_tokens(user_id, new_email, token_hash, expires_at) VALUES(?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $newEmail, $tokenHash, $expiresAt]);
    }

    public static function findPendingEmailChange(string $tokenHash): ?array
    {
        $db = Database::connect();
        $stmt = $db->prepare(
            'SELECT ect.id, ect.user_id, ect.new_email, u.name
             FROM email_change_tokens ect
             INNER JOIN users u ON u.id = ect.user_id
             WHERE ect.token_hash = ?
               AND ect.used_at IS NULL
               AND ect.expires_at > NOW()
             LIMIT 1'
        );
        $stmt->execute([$tokenHash]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public static function applyEmailChange(int $tokenId, int $userId, string $newEmail): void
    {
        $db = Database::connect();
        $db->beginTransaction();

        try {
            $updateUser = $db->prepare(
                'UPDATE users SET email = ?, is_email_verified = 1, email_verified_at = NOW() WHERE id = ?'
            );
            $updateUser->execute([$newEmail, $userId]);

            $markToken = $db->prepare('UPDATE email_change_tokens SET used_at = NOW() WHERE id = ?');
            $markToken->execute([$tokenId]);

            $db->commit();
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public static function markPasswordResetAsUsed(int $resetId): void
    {
        $db = Database::connect();
        $stmt = $db->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE id = ?');
        $stmt->execute([$resetId]);
    }

    public static function markEmailAsVerified(int $userId): void
    {
        $db = Database::connect();
        $db->beginTransaction();

        try {
            $updateUser = $db->prepare(
                'UPDATE users SET is_email_verified = 1, email_verified_at = NOW() WHERE id = ?'
            );
            $updateUser->execute([$userId]);

            $updateToken = $db->prepare(
                'UPDATE email_verification_tokens SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL'
            );
            $updateToken->execute([$userId]);

            $db->commit();
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public static function listAll(): array
    {
        $db = Database::connect();
        $stmt = $db->query(
            'SELECT id, username, email, name, first_name, last_name, phone, role, is_email_verified, email_verified_at, banned_at, banned_by, created_at
             FROM users
             ORDER BY created_at DESC, id DESC'
        );

        return $stmt->fetchAll() ?: [];
    }

    /**
     * Resuelve varios usuarios por id en una sola consulta. Devuelve los que
     * encuentre, sin error si alguno no existe. Lista vacía → []. Pensado
     * para JOINs cross-service: el caller (inmobiliaria) tiene N user_ids
     * en sus tablas locales y necesita resolverlos a info de perfil sin
     * hacer N round-trips HTTP.
     *
     * @param int[] $ids
     * @return array<int, array<string, mixed>>
     */
    public static function findManyByIds(array $ids): array
    {
        $clean = array_values(array_unique(array_filter(array_map('intval', $ids), static fn(int $id): bool => $id > 0)));
        if ($clean === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($clean), '?'));
        $db = Database::connect();
        $stmt = $db->prepare(
            'SELECT id, username, email, name, first_name, last_name, phone, role, is_email_verified, email_verified_at, banned_at, banned_by, created_at
             FROM users
             WHERE id IN (' . $placeholders . ')'
        );
        $stmt->execute($clean);

        return $stmt->fetchAll() ?: [];
    }

    /**
     * Igual que findManyByIds() pero por email. Hace LOWER() en ambos lados
     * para que el lookup sea case-insensitive (los emails se guardan en
     * minúsculas pero algún caller podría mandar "Foo@Bar.com").
     *
     * @param string[] $emails
     * @return array<int, array<string, mixed>>
     */
    public static function findManyByEmails(array $emails): array
    {
        $clean = [];
        foreach ($emails as $email) {
            $normalized = strtolower(trim((string) $email));
            if ($normalized !== '' && filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
                $clean[$normalized] = true;
            }
        }
        $clean = array_keys($clean);

        if ($clean === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($clean), '?'));
        $db = Database::connect();
        $stmt = $db->prepare(
            'SELECT id, username, email, name, first_name, last_name, phone, role, is_email_verified, email_verified_at, banned_at, banned_by, created_at
             FROM users
             WHERE LOWER(email) IN (' . $placeholders . ')'
        );
        $stmt->execute($clean);

        return $stmt->fetchAll() ?: [];
    }

    /**
     * Devuelve el country_code del último login legítimo (status neutral o
     * confirmed). Los pending y rejected se excluyen a propósito: así una
     * alerta no respondida o una rechazada no envenena la referencia del
     * siguiente login.
     */
    public static function getLastLegitLoginCountry(int $userId): ?string
    {
        $db = Database::connect();
        $stmt = $db->prepare(
            "SELECT country_code FROM login_locations
             WHERE user_id = ?
               AND status IN ('neutral', 'confirmed')
               AND country_code IS NOT NULL
             ORDER BY created_at DESC, id DESC
             LIMIT 1"
        );
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        return $row ? (string) $row['country_code'] : null;
    }

    /**
     * Inserta un registro de login_locations. status puede ser 'neutral' o
     * 'pending'. En pending, se persisten token_hash y token_expires_at.
     * Devuelve el id del registro creado.
     */
    public static function recordLoginLocation(
        int $userId,
        string $ip,
        ?string $countryCode,
        ?string $countryName,
        string $userAgent,
        string $status,
        ?string $tokenHash = null,
        ?string $tokenExpiresAt = null
    ): int {
        $db = Database::connect();
        $stmt = $db->prepare(
            'INSERT INTO login_locations
               (user_id, ip, country_code, country_name, user_agent, status, token_hash, token_expires_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $userId, $ip, $countryCode, $countryName,
            mb_substr($userAgent, 0, 512),
            $status, $tokenHash, $tokenExpiresAt,
        ]);
        return (int) $db->lastInsertId();
    }

    public static function findLoginLocationByTokenHash(string $tokenHash): ?array
    {
        $db = Database::connect();
        $stmt = $db->prepare('SELECT * FROM login_locations WHERE token_hash = ? LIMIT 1');
        $stmt->execute([$tokenHash]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function updateLoginLocationStatus(int $locationId, string $status): void
    {
        $db = Database::connect();
        $stmt = $db->prepare(
            'UPDATE login_locations SET status = ?, token_used_at = NOW() WHERE id = ?'
        );
        $stmt->execute([$status, $locationId]);
    }

    public static function setRequirePasswordReset(int $userId, bool $required): void
    {
        $db = Database::connect();
        $stmt = $db->prepare('UPDATE users SET require_password_reset = ? WHERE id = ?');
        $stmt->execute([$required ? 1 : 0, $userId]);
    }
}
