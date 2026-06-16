<?php
// validation.php

/**
 * Valida texto: letras (incluidos acentos y enie), espacios, guion y apostrofe.
 * Permite apellidos compuestos como "Garcia-Lopez" o "O'Brien".
 *
 * @param string $text
 * @return bool
 */
function validateText($text) {
    return (bool) preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s'\-]+$/u", (string) $text);
}

/**
 * Valida un correo electronico.
 *
 * @param string $email
 * @return bool
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Valida que el valor sea un entero.
 *
 * @param mixed $number
 * @return bool
 */
function validateNumber($number) {
    return filter_var($number, FILTER_VALIDATE_INT) !== false;
}

/**
 * Valida una contrasena: minimo 8 caracteres.
 *
 * @param string $password
 * @return bool
 */
function validatePassword($password) {
    return strlen((string) $password) >= 8;
}

/**
 * Valida un identificador: entero positivo (> 0).
 *
 * @param mixed $v
 * @return bool
 */
function validateId($v) {
    $val = filter_var($v, FILTER_VALIDATE_INT);
    return $val !== false && $val > 0;
}
