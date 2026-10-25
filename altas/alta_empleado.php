<?php
include_once("../includes/conexionBD.php");
include_once("../includes/funciones.php");
verificar_sesion();

$nombre_empleado = $_SESSION['empleado_nombre'] ?? 'Empleado';
$mensaje = "";
$roles = array();
$consultaRoles = mysqli_query($conexion, "SELECT id_rol, nombre FROM Rol ORDER BY nombre ASC");
if ($consultaRoles) {
    while ($row = mysqli_fetch_assoc($consultaRoles)) {
        $roles[] = $row;
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = trim($_POST["nombre"] ?? "");
    $apellido = trim($_POST["apellido"] ?? "");
    $correo = trim($_POST["correo"] ?? "");
    $contrasena = $_POST["contrasena"] ?? "";
    $id_rol = filter_var($_POST["id_rol"] ?? null, FILTER_VALIDATE_INT);

    if ($nombre === "" || $apellido === "" || $correo === "") {
        $mensaje = "Todos los campos obligatorios deben ser completados.";
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL) || strlen($correo) > 100) {
        $mensaje = "Ingrese un correo electrónico válido (máximo 100 caracteres).";
    } elseif (strlen($contrasena) < 8) {
        $mensaje = "La contraseña debe tener al menos 8 caracteres.";
    } elseif ($id_rol === false || $id_rol <= 0) {
        $mensaje = "Debe seleccionar un rol válido.";
    } else {
        $hash = password_hash($contrasena, PASSWORD_DEFAULT);
        $sql = "INSERT INTO empleados (nombre, apellido, correo, contraseña, activo, id_rol)
                VALUES (?, ?, ?, ?, TRUE, ?)";
        $stmt = mysqli_prepare($conexion, $sql);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ssssi", $nombre, $apellido, $correo, $hash, $id_rol);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                header("Location: ../empleados.php");
                exit();
            }

            $mensaje = "No se pudo registrar el empleado. Verifique que el correo no esté repetido.";
            error_log("Error MySQL INSERT empleado: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
        } else {
            $mensaje = "Error al preparar la consulta.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Nuevo Empleado</title>
    <link rel="stylesheet" href="../includes/style.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
</head>
<body>

    <div class="contenido-principal" style="padding: 0;">
        <div class="barra-superior">
            <h1>
                <a href="../empleados.php" style="color: var(--accent-green); text-decoration: none;"><i class="ri-arrow-left-line"></i></a>
                Lo de Torres - Nuevo Empleado
            </h1>
            <div class="billetera-usuario" style="color: var(--accent-green);">
                <i class="ri-user-3-fill"></i>
                <span><?= htmlspecialchars($nombre_empleado, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </div>
    </div>

    <div class="contenedor-insumos">
        <h2 style="color: var(--color-text-main); margin-bottom: 20px; font-size: 24px;">Registrar Nuevo Empleado</h2>

        <?php if ($mensaje !== ""): ?>
            <div style="background: rgba(255, 74, 90, 0.15); border: 1px solid var(--accent-red); color: var(--accent-red); padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px;">
                <?php echo htmlspecialchars($mensaje, ENT_QUOTES, "UTF-8"); ?>
            </div>
        <?php endif; ?>

        <form action="alta_empleado.php" method="POST" style="background: #1e293b; padding: 25px; border-radius: 8px; border: 1px solid #334155; display: flex; flex-direction: column; gap: 16px; max-width: 600px;">
            
            <div style="display: flex; flex-direction: column; gap: 6px;">
                <label for="nombre" style="color: var(--color-text-body); font-weight: 500; font-size: 14px;">Nombre *</label>
                <input type="text" id="nombre" name="nombre" maxlength="100" required
                       value="<?php echo htmlspecialchars($_POST["nombre"] ?? "", ENT_QUOTES, "UTF-8"); ?>"
                       style="padding: 10px 15px; background: #0f172a; border: 1px solid #334155; border-radius: 6px; color: #fff; font-size: 14px; outline: none;">
            </div>

            <div style="display: flex; flex-direction: column; gap: 6px;">
                <label for="apellido" style="color: var(--color-text-body); font-weight: 500; font-size: 14px;">Apellido *</label>
                <input type="text" id="apellido" name="apellido" maxlength="100" required
                       value="<?php echo htmlspecialchars($_POST["apellido"] ?? "", ENT_QUOTES, "UTF-8"); ?>"
                       style="padding: 10px 15px; background: #0f172a; border: 1px solid #334155; border-radius: 6px; color: #fff; font-size: 14px; outline: none;">
            </div>

            <div style="display: flex; flex-direction: column; gap: 6px;">
                <label for="correo" style="color: var(--color-text-body); font-weight: 500; font-size: 14px;">Correo electrónico *</label>
                <input type="email" id="correo" name="correo" maxlength="100" required
                       value="<?php echo htmlspecialchars($_POST["correo"] ?? "", ENT_QUOTES, "UTF-8"); ?>"
                       style="padding: 10px 15px; background: #0f172a; border: 1px solid #334155; border-radius: 6px; color: #fff; font-size: 14px; outline: none;">
            </div>

            <div style="display: flex; flex-direction: column; gap: 6px;">
                <label for="contrasena" style="color: var(--color-text-body); font-weight: 500; font-size: 14px;">Contraseña *</label>
                <input type="password" id="contrasena" name="contrasena" minlength="8" required
                       style="padding: 10px 15px; background: #0f172a; border: 1px solid #334155; border-radius: 6px; color: #fff; font-size: 14px; outline: none;">
            </div>

            <div style="display: flex; flex-direction: column; gap: 6px;">
                <label for="id_rol" style="color: var(--color-text-body); font-weight: 500; font-size: 14px;">Rol *</label>
                <select id="id_rol" name="id_rol" required
                        style="padding: 10px 15px; background: #0f172a; border: 1px solid #334155; border-radius: 6px; color: #fff; font-size: 14px; outline: none;">
                    <option value="">Seleccione una opción...</option>
                    <?php foreach ($roles as $rol): ?>
                        <option value="<?php echo $rol["id_rol"]; ?>"
                            <?php echo ((int)($rol["id_rol"]) === (int)($_POST["id_rol"] ?? 0)) ? "selected" : ""; ?>>
                            <?php echo htmlspecialchars($rol["nombre"], ENT_QUOTES, "UTF-8"); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display: flex; gap: 12px; align-items: center; margin-top: 10px;">
                <button type="submit" class="btn-nuevo" style="border: none; cursor: pointer; font-size: 14px;">Guardar empleado</button>
                <a href="../empleados.php" style="color: var(--color-text-muted); text-decoration: none; font-size: 14px; font-weight: 500;">Volver al listado</a>
            </div>

        </form>
    </div>

</body>
</html>