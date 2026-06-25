<?php
/* ==========================================================
   SOC 2 IMPLEMENTATION
   Developer : Dhanushree N

   Module:
   Session Security

   Features:
   ✓ Automatic Session Timeout
   ✓ Session Activity Tracking

   SOC 2 Category:
   CC6 - Logical Access Controls
========================================================== */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ==========================================================
   SOC 2 IMPLEMENTATION
   Authentication Verification
   Prevent unauthorized access to protected pages
========================================================== */

if (!isset($_SESSION['user_id'])) {

    header("Location: login.php");
    exit();

}

/* ==========================================================
   Session Timeout (30 minutes)
========================================================== */

$sessionTimeout = 1800; // 30 minutes

if (isset($_SESSION['last_activity'])) {

    if ((time() - $_SESSION['last_activity']) > $sessionTimeout) {

        session_unset();
        session_destroy();

        header("Location: login.php?timeout=1");
        exit();
    }
}

/* ==========================================================
   Update Last Activity Time
========================================================== */

$_SESSION['last_activity'] = time();