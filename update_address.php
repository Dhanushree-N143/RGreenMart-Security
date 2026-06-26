<?php
session_start();
require_once $_SERVER["DOCUMENT_ROOT"] . "/dbconf.php";

$user_id = $_SESSION["user_id"] ?? null;

if (!$user_id) {
    header("Location: login.php");
    exit;
}

$address_id = $_POST["id"] ?? null;
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
    die("Invalid pincode.");
}

// If default, make all others non-default
if ($is_default) {
    $conn->prepare("UPDATE user_addresses SET is_default = 0 WHERE user_id = ?")
         ->execute([$user_id]);
}

$stmt = $conn->prepare("
    UPDATE user_addresses SET
        contact_name = ?,
        contact_mobile = ?,
        address_line1 = ?,
        address_line2 = ?,
        city = ?,
        state = ?,
        pincode = ?,
        landmark = ?,
        is_default = ?
    WHERE id = ? AND user_id = ?
");

$stmt->execute([
    $contact_name,
    $contact_mobile,
    $address_line1,
    $address_line2,
    $city,
    $state,
    $pincode,
    $landmark,
    $is_default,
    $address_id,
    $user_id
]);

header("Location: add_delivery_address.php");
exit;