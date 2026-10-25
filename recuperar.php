<?php
require_once 'includes/conexionBD.php';
$mensaje = '';
$tipo_alerta = '';
$script_consola = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo'] ?? '');

    if (!empty($correo) && filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $token = bin2hex(random_bytes(32));
        $expiracion = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $sql = "UPDATE empleados 
                SET token_recuperacion = ?, token_expiracion = ? 
                WHERE correo = ?";
                
        $stmt = mysqli_prepare($conexion, $sql);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sss", $token, $expiracion, $correo);
            mysqli_stmt_execute($stmt);
            
            if (mysqli_stmt_affected_rows($stmt) > 0) {
                $protocolo = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                $host_actual = $_SERVER['HTTP_HOST'];
                $url_base = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
                $enlace_recuperacion = "{$protocolo}://{$host_actual}{$url_base}/restablecer.php?token=" . urlencode($token);

                $cuerpo_mail = "=== SISTEMA LO DE TORRES ===\n";
                $cuerpo_mail .= "Solicitud de restablecimiento de contraseña.\n";
                $cuerpo_mail .= "Para el correo: {$correo}\n";
                $cuerpo_mail .= "Enlace seguro: {$enlace_recuperacion}\n";
                $cuerpo_mail .= "Este enlace vencerá en 1 hora.";

                $script_consola = "<script>console.log(" . json_encode($cuerpo_mail) . ");</script>";
            }

            $mensaje = "Si el correo ingresado existe en el sistema, se han enviado las instrucciones de recuperación.";
            $tipo_alerta = "exito";
            mysqli_stmt_close($stmt);
        } else {
            $mensaje = "Ocurrió un error interno al procesar la solicitud.";
            $tipo_alerta = "error";
        }
    } else {
        $mensaje = "Por favor, ingresá un correo electrónico válido.";
        $tipo_alerta = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lo de Torres - Recuperar Contraseña</title>
    <link rel="stylesheet" href="includes/style.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
</head>
<body style="display: flex; align-items: center; justify-content: center; min-height: 100vh;">

    <div class="tarjeta-login" style="width: 100%; max-width: 420px; padding: 30px; box-sizing: border-box;">
        <h2 style="text-align: center; margin-bottom: 5px;">Lo de Torres</h2>
        <p style="color: var(--color-text-muted); font-size: 0.9rem; margin-bottom: 20px; text-align: center;">Recuperación de Contraseña</p>

        <?php if (!empty($mensaje)): ?>
            <div style="padding: 10px 14px; border-radius: 8px; font-size: 0.85rem; margin-bottom: 16px; text-align: center; border: 1px solid <?= $tipo_alerta === 'exito' ? 'var(--accent-green)' : 'var(--accent-red)' ?>; background: <?= $tipo_alerta === 'exito' ? 'rgba(74, 222, 128, 0.1)' : 'rgba(255, 74, 90, 0.1)' ?>; color: <?= $tipo_alerta === 'exito' ? 'var(--accent-green)' : 'var(--accent-red)' ?>;">
                <?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form action="recuperar.php" method="POST">
            <div class="grupo-input" style="margin-bottom: 16px;">
                <label for="correo" style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: var(--color-text-main);">Correo Electrónico</label>
                <input type="email" name="correo" id="correo" required autocomplete="off" placeholder="ejemplo@correo.com" style="width: 100%; padding: 10px 15px; background: var(--bg-input); border: 1px solid var(--border-subtle); border-radius: 6px; color: #fff; font-size: 14px; outline: none;">
            </div>
            
            <button type="submit" class="btn-publish" style="width: 100%; padding: 12px; cursor: pointer; font-size: 14px; font-weight: bold; border-radius: 6px; border: none; background-color: var(--accent-blue); color: white; transition: background 0.2s;">Enviar Instrucciones</button>
        </form>

        <div style="text-align: center; margin-top: 20px;">
            <a href="iniciar-sesion.php" style="font-size: 0.85rem; color: var(--accent-cyan); text-decoration: none;">Volver al Inicio de Sesión</a>
        </div>
    </div>

    <?= $script_consola ?>
</body>
</html>