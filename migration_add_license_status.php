<?php
$conn = new mysqli('localhost', 'root', '', 'saev5');
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$sql = "ALTER TABLE `setting` 
    ADD `license_status` ENUM('unverified','active','suspended','expired','invalid') NOT NULL DEFAULT 'unverified' AFTER `license_key`,
    ADD `license_school_name` VARCHAR(255) NULL DEFAULT NULL AFTER `license_status`,
    ADD `license_npsn` VARCHAR(50) NULL DEFAULT NULL AFTER `license_school_name`,
    ADD `license_expired_at` DATE NULL DEFAULT NULL AFTER `license_npsn`;";

if ($conn->query($sql) === TRUE) {
    echo "Table 'setting' altered successfully.";
} else {
    echo "Error altering table: " . $conn->error;
}
$conn->close();
