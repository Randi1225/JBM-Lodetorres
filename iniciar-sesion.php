<?php
session_start();
include_once("includes/conexionBD.php");
include_once("includes/funciones.php");

$mensaje = "";
$intentos = 0;

$ip = $_SERVER['REMOTE_ADDR'] ?? '';
if (empty($ip) || $ip === '::1') {
    $ip = '127.0.0.1';
}

$correo_post = trim($_POST["correo"] ?? "");
$sql_recheck = "SELECT intentos FROM inic_ses WHERE ip = ? OR usuario = ? LIMIT 1";
$stmt_recheck = mysqli_prepare($conexion, $sql_recheck);
if ($stmt_recheck) {
    mysqli_stmt_bind_param($stmt_recheck, "ss", $ip, $correo_post);
    mysqli_stmt_execute($stmt_recheck);
    $res_recheck = mysqli_stmt_get_result($stmt_recheck);
    if ($reg_recheck = mysqli_fetch_assoc($res_recheck)) {
        $intentos = (int)$reg_recheck['intentos'];
    }
    mysqli_stmt_close($stmt_recheck);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $correo = trim($_POST["correo"] ?? "");
    $password = $_POST["contraseña"] ?? "";
    $captcha_input = trim($_POST["captcha_input"] ?? "");

    if ($intentos >= 3) {
        $captcha_real = $_SESSION['captcha_texto'] ?? '';
        if (empty($captcha_input) || strtoupper($captcha_input) !== strtoupper($captcha_real)) {
            $mensaje = "El código de seguridad (CAPTCHA) es incorrecto.";
        }
    }

    if ($mensaje === "") {
        if ($correo === "" || $password === "") {
            $mensaje = "Por favor, completa todos los campos.";
        } else {
            $sql = "SELECT id_empleado, nombre, apellido, correo, activo, contraseña, id_rol FROM empleados WHERE correo = ? LIMIT 1";
            $stmt = mysqli_prepare($conexion, $sql);

            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "s", $correo);
                mysqli_stmt_execute($stmt);
                $resultado = mysqli_stmt_get_result($stmt);
                $empleado = mysqli_fetch_assoc($resultado);
                mysqli_stmt_close($stmt);

                if ($empleado && password_verify($password, $empleado["contraseña"])) {
                    if (isset($empleado["activo"]) && $empleado["activo"] == 0) {
                        $mensaje = "Tu cuenta está desactivada. Contacta al administrador.";
                    } else {
                        $sql_del = "DELETE FROM inic_ses WHERE ip = ? OR usuario = ?";
                        $stmt_del = mysqli_prepare($conexion, $sql_del);
                        if ($stmt_del) {
                            mysqli_stmt_bind_param($stmt_del, "ss", $ip, $correo);
                            mysqli_stmt_execute($stmt_del);
                            mysqli_stmt_close($stmt_del);
                        }

                        unset($_SESSION['captcha_texto']);
                        unset($_SESSION['captcha_creado_en']);

                        $_SESSION["empleado_id"] = $empleado["id_empleado"];
                        $_SESSION["empleado_nombre"] = $empleado["nombre"];
                        $_SESSION["empleado_rol"] = $empleado["id_rol"];
                        
                        header("Location: dashboard.php");
                        exit();
                    }
                } else {
                    $mensaje = "Correo electrónico o contraseña incorrectos.";

                    $sql_check_exist = "SELECT id FROM inic_ses WHERE ip = ? LIMIT 1";
                    $stmt_chk = mysqli_prepare($conexion, $sql_check_exist);
                    mysqli_stmt_bind_param($stmt_chk, "s", $ip);
                    mysqli_stmt_execute($stmt_chk);
                    $res_chk = mysqli_stmt_get_result($stmt_chk);
                    
                    if (mysqli_fetch_assoc($res_chk)) {
                        $sql_update = "UPDATE inic_ses SET intentos = intentos + 1, ultimo_intento = NOW(), usuario = ? WHERE ip = ?";
                        $stmt_upd = mysqli_prepare($conexion, $sql_update);
                        mysqli_stmt_bind_param($stmt_upd, "ss", $correo, $ip);
                        mysqli_stmt_execute($stmt_upd);
                        mysqli_stmt_close($stmt_upd);
                    } else {
                        $sql_insert = "INSERT INTO inic_ses (ip, usuario, intentos, ultimo_intento) VALUES (?, ?, 1, NOW())";
                        $stmt_ins = mysqli_prepare($conexion, $sql_insert);
                        mysqli_stmt_bind_param($stmt_ins, "ss", $ip, $correo);
                        mysqli_stmt_execute($stmt_ins);
                        mysqli_stmt_close($stmt_ins);
                    }
                    mysqli_stmt_close($stmt_chk);

                    $intentos++;
                }
            } else {
                $mensaje = "Error en el sistema. Inténtalo más tarde.";
            }
        }
    }
}

if ($intentos >= 3 && !isset($_SESSION['captcha_texto'])) {
    $_SESSION['captcha_texto'] = substr(str_shuffle("ABCDEFGHJKLMNPQRSTUVWXYZ23456789"), 0, 6);
    $_SESSION['captcha_creado_en'] = time();
}

$sql_clean = "DELETE FROM inic_ses WHERE ultimo_intento < NOW() - INTERVAL 30 MINUTE";
mysqli_query($conexion, $sql_clean);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lo de Torres - Iniciar Sesión</title>   
    <link rel="stylesheet" href="includes/iniciar-secion.css">
</head>
<body>
    <div class="contenedor-login">
        <div class="tarjeta-login">
            <h2>Iniciar Sesión</h2>

            <?php if ($mensaje !== ''): ?>
                <p style="color: var(--accent-red); text-align: center; margin-bottom: 15px; font-size: 14px;"><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>

            <form action="iniciar-sesion.php" method="POST">
                <div class="grupo-input">
                    <label for="correo">Correo Electrónico</label>
                    <input
                        type="email"
                        id="correo"
                        name="correo"
                        placeholder="ejemplo@correo.com"
                        required
                    >
                </div>

                <div class="grupo-input">
                    <label for="contraseña">Contraseña</label>
                    <input
                        type="password"
                        id="contraseña"
                        name="contraseña"
                        placeholder="Tu contraseña"
                        required
                    >
                </div>

                <?php if ($intentos >= 3): ?>
                <div style="margin-bottom:15px; background:rgba(255, 74, 90, 0.1); color:var(--accent-red); padding:12px; border-radius:8px; border:1px solid rgba(255, 74, 90, 0.3); box-sizing:border-box;">
                    <p style="margin:0 0 8px 0; font-size:13px; font-weight:bold; text-align:left;">Escriba el código de seguridad:</p>
                    <div style="display:flex; gap:10px; align-items:center;">
                        <div style="font-size:16px; font-weight:bold; background:#ffffff; color:#333; padding:7px 12px; border-radius:6px; border:1px solid #ccc; user-select:none; min-width:80px; text-align:center; letter-spacing: 2px;">
                            <?php echo htmlspecialchars($_SESSION['captcha_texto'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                        <input type="text" name="captcha_input" placeholder="Código" required autocomplete="off" style="flex:1; padding:9px 12px; font-size:14px; background:var(--bg-input); border:1px solid var(--border-subtle); color:#fff; border-radius:6px; box-sizing:border-box; text-transform:uppercase; outline:none;">
                    </div>
                </div>
                <?php endif; ?>

                <button type="submit" class="boton-login">Entrar</button>

                <div style="text-align: center; margin-top: 15px;">
                    <a href="recuperar.php" style="color: var(--color-text-muted); font-size: 13px; text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='var(--accent-cyan)'" onmouseout="this.style.color='var(--color-text-muted)'">¿Olvidaste tu contraseña?</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>