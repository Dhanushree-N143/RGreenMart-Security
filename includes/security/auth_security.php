<?php
/* ==========================================================
   SOC 2 SECURITY IMPLEMENTATION
   Developer : Dhanushree N
   Module    : Authentication Security

   Features Implemented:
   ✓ Brute Force Login Protection
   ✓ Login Attempt Logging
   ✓ Login Audit Logging

   SOC 2 Category:
   CC6 - Logical and Physical Access Controls
========================================================== */

/* ==========================================================
   Returns client's IP Address
========================================================== */
function getClientIP()
{
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
    }

    return $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
}

/* ==========================================================
   Log every login attempt
========================================================== */
function logLoginAttempt(PDO $conn, $email, $success)
{
    $stmt = $conn->prepare("
        INSERT INTO login_attempts
        (email, ip_address, success)
        VALUES
        (:email,:ip,:success)
    ");

    $stmt->execute([
        ':email' => $email,
        ':ip' => getClientIP(),
        ':success' => $success ? 1 : 0
    ]);
}

/* ==========================================================
   Log authentication events for auditing
========================================================== */
function logAudit(PDO $conn, $userId, $email, $action)
{
    $stmt = $conn->prepare("
        INSERT INTO login_audit
        (user_id,email,ip_address,action)
        VALUES
        (:uid,:email,:ip,:action)
    ");

    $stmt->execute([
        ':uid' => $userId,
        ':email' => $email,
        ':ip' => getClientIP(),
        ':action' => $action
    ]);
}

/* ==========================================================
   Check whether account is temporarily locked
========================================================== */
function isAccountLocked(PDO $conn, $email)
{
    $stmt = $conn->prepare("
        SELECT COUNT(*)
        FROM login_attempts
        WHERE email = :email
        AND success = 0
        AND attempt_time >=
            (NOW() - INTERVAL 15 MINUTE)
    ");

    $stmt->execute([
        ':email' => $email
    ]);

    return $stmt->fetchColumn() >= 5;
}

/* ==========================================================
   Remove failed attempts after successful login
========================================================== */
function clearFailedAttempts(PDO $conn, $email)
{
    $stmt = $conn->prepare("
        DELETE FROM login_attempts
        WHERE email = :email
        AND success = 0
    ");

    $stmt->execute([
        ':email' => $email
    ]);
}