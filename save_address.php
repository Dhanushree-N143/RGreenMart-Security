<?php
session_start();
require_once $_SERVER["DOCUMENT_ROOT"] . "/dbconf.php";

/* ================================================================
   Detect if this is an Admin JSON API call
   (Content-Type: application/json  OR  ?admin=1 in query string)
================================================================ */
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
$isAdminApi  = (strpos($contentType, 'application/json') !== false)
            || isset($_GET['admin']);

if ($isAdminApi) {

    /* ─────────────────────────────────────────────
       ADMIN JSON API  (used by admin_create_order)
    ───────────────────────────────────────────── */
    header("Content-Type: application/json");

    // Admin auth check
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        echo json_encode(["success" => false, "message" => "Unauthorized"]);
        exit;
    }

    $data    = json_decode(file_get_contents("php://input"), true);
    $user_id = intval($data['user_id'] ?? 0);
    $id      = intval($data['id']      ?? 0);

    if (!$user_id) {
        echo json_encode(["success" => false, "message" => "user_id required"]);
        exit;
    }

    // Validate required fields
    $required = ['contact_name','contact_mobile','address_line1','city','state','pincode'];
    foreach ($required as $field) {
        if (empty(trim($data[$field] ?? ''))) {
            echo json_encode(["success" => false, "message" => "Field '{$field}' is required"]);
            exit;
        }
    }

    $mobile  = preg_replace('/\D/', '', $data['contact_mobile']);
    $pincode = preg_replace('/\D/', '', $data['pincode']);

    if (strlen($mobile) !== 10) {
        echo json_encode(["success" => false, "message" => "Mobile must be 10 digits"]);
        exit;
    }
    if (strlen($pincode) !== 6) {
        echo json_encode(["success" => false, "message" => "Pincode must be 6 digits"]);
        exit;
    }

    $params = [
    strip_tags(trim($data['contact_name'] ?? '')),
    $mobile,
    strip_tags(trim($data['address_line1'] ?? '')),
    strip_tags(trim($data['address_line2'] ?? '')),
    strip_tags(trim($data['city'] ?? '')),
    strip_tags(trim($data['state'] ?? '')),
    $pincode,
    strip_tags(trim($data['landmark'] ?? ''))
];

    if ($id > 0) {
        // EDIT: verify address belongs to this user
        $check = $conn->prepare("SELECT id FROM user_addresses WHERE id = ? AND user_id = ?");
        $check->execute([$id, $user_id]);
        if (!$check->fetch()) {
            echo json_encode(["success" => false, "message" => "Address not found for this user"]);
            exit;
        }

        $stmt = $conn->prepare("
            UPDATE user_addresses SET
                contact_name   = ?,
                contact_mobile = ?,
                address_line1  = ?,
                address_line2  = ?,
                city           = ?,
                state          = ?,
                pincode        = ?,
                landmark       = ?
            WHERE id = ? AND user_id = ?
        ");
        $params[] = $id;
        $params[] = $user_id;
        $stmt->execute($params);
        echo json_encode(["success" => true, "message" => "Address updated", "id" => $id]);

    } else {
        // ADD new address for this user
        $stmt = $conn->prepare("
            INSERT INTO user_addresses
                (user_id, contact_name, contact_mobile, address_line1, address_line2,
                 city, state, pincode, landmark)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        array_unshift($params, $user_id);
        $stmt->execute($params);
        $newId = $conn->lastInsertId();
        echo json_encode(["success" => true, "message" => "Address added", "id" => $newId]);
    }
    exit;
}

/* ================================================================
   USER FORM POST  (original behaviour — unchanged)
================================================================ */
$user_id = $_SESSION["user_id"] ?? null;
if (!$user_id) {
    header("Location: login.php");
    exit;
}

$is_default = isset($_POST['is_default']) ? 1 : 0;

// Sanitize input
$contact_name   = strip_tags(trim($_POST['contact_name'] ?? ''));
$contact_mobile = trim($_POST['contact_mobile'] ?? '');
$address_line1  = strip_tags(trim($_POST['address_line1'] ?? ''));
$address_line2  = strip_tags(trim($_POST['address_line2'] ?? ''));
$city           = strip_tags(trim($_POST['city'] ?? ''));
$state          = strip_tags(trim($_POST['state'] ?? ''));
$landmark       = strip_tags(trim($_POST['landmark'] ?? ''));
$pincode        = trim($_POST['pincode'] ?? '');

if ($is_default) {
    $conn->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?")
         ->execute([$user_id]);
}

// Contact name validation
if (!preg_match("/^[a-zA-Z ]{2,50}$/", $contact_name)) {
    die("Invalid contact name.");
}

// Mobile validation
if (!preg_match('/^[6-9][0-9]{9}$/', $contact_mobile)) {
    die("Invalid mobile number.");
}

// Address validation
if (strlen($address_line1) < 5 || strlen($address_line1) > 100) {
    die("Invalid address.");
}

// City validation
if (!preg_match("/^[a-zA-Z ]{2,40}$/", $city)) {
    die("Invalid city.");
}

// State validation
if (!preg_match("/^[a-zA-Z ]{2,40}$/", $state)) {
    die("Invalid state.");
}

// Pincode validation
if (!preg_match('/^\d{6}$/', $pincode)) {
    die("Invalid pincode. Only 6-digit numbers allowed.");
}

$stmt = $conn->prepare("
    INSERT INTO user_addresses (
        user_id, contact_name, contact_mobile, address_line1, address_line2,
        city, state, pincode, landmark, is_default
    ) VALUES (?,?,?,?,?,?,?,?,?,?)
");

$stmt->execute([
    $user_id,
    $contact_name,
    $contact_mobile,
    $address_line1,
    $address_line2,
    $city,
    $state,
    $pincode,
    $landmark,
    $is_default
]);
header("Location: add_delivery_address.php");
exit;