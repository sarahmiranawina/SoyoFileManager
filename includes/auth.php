<?php

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function generatePasswordHash($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}
?>
