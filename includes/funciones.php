<?php
function iniciar_sesion_segura() {
    if (session_status() === PHP_SESSION_NONE) {
        $https = isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off";
        session_set_cookie_params(array(
            "lifetime" => 0,
            "path" => "/",
            "secure" => $https,
            "httponly" => true,
            "samesite" => "Lax"
        ));
        session_start();
    }
}

function verificar_sesion() {
    iniciar_sesion_segura();
    if (!isset($_SESSION["empleado_id"])) {
        $directorio = trim(dirname($_SERVER["SCRIPT_NAME"] ?? ""), "/");
        $niveles = $directorio === "" ? 0 : substr_count($directorio, "/");
        header("Location: " . str_repeat("../", $niveles) . "iniciar-sesion.php");
        exit();
    }
}

function verificar_sesion_api() {
    iniciar_sesion_segura();
    if (!isset($_SESSION["empleado_id"]) && !isset($_SESSION["id_empleado"])) {
        http_response_code(401);
        header("Content-Type: application/json; charset=utf-8");
        echo json_encode(array("error" => "Sesión requerida."), JSON_UNESCAPED_UNICODE);
        exit();
    }
}

function obtener_token_csrf() {
    iniciar_sesion_segura();
    if (empty($_SESSION["csrf_token"])) {
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
    }
    return $_SESSION["csrf_token"];
}

function validar_token_csrf($token) {
    iniciar_sesion_segura();
    return is_string($token) && !empty($_SESSION["csrf_token"]) && hash_equals($_SESSION["csrf_token"], $token); // si se setea el token y es correcto, devuelve true, sino false
}

function cerrar_sesion() {
    iniciar_sesion_segura();
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $parametros = session_get_cookie_params();
        setcookie(session_name(), "", time() - 42000, $parametros["path"], $parametros["domain"] ?? "", $parametros["secure"], $parametros["httponly"]);
    }
    session_destroy();
}

function obtenerRolUsuario() {
    return $_SESSION['empleado_rol'] ?? $_SESSION['rol'] ?? null;
}

function obtenerIdUsuario() {
    return $_SESSION['id_empleado'] ?? $_SESSION['id_usuario'] ?? null;
}

function tieneRol($rolesPermitidos) {
    $rolActual = strtolower(trim((string)obtenerRolUsuario()));
    $rolesNormalizados = array_map(function($r) {
        return strtolower(trim((string)$r));
    }, (array)$rolesPermitidos);

    return in_array($rolActual, $rolesNormalizados, true);
}

function requerirRol($rolesPermitidos) {
    if (!tieneRol($rolesPermitidos)) {
        if (function_exists('responder')) {
            responder(array("error" => "No tienes permisos suficientes para realizar esta acción."), 403);
        } else {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(array("error" => "No tienes permisos suficientes para realizar esta acción."), JSON_UNESCAPED_UNICODE);
            exit();
        }
    }
}