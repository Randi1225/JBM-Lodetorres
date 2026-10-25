<?php
include_once("../includes/conexionBD.php");
include_once("../includes/funciones.php");
verificar_sesion();

$mensaje = "";
$tipoMensaje = "";
$nombre_empleado = $_SESSION['empleado_nombre'] ?? 'Empleado';

$medidas = array();
$consultaMedidas = mysqli_query($conexion, "SELECT id_medida, unidad FROM medida ORDER BY unidad ASC");
if ($consultaMedidas) {
    while ($row = mysqli_fetch_assoc($consultaMedidas)) {
        $medidas[] = $row;
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = trim($_POST["nombre"] ?? '');
    $cantidad_actual = filter_var($_POST["cantidad_actual"] ?? 0, FILTER_VALIDATE_FLOAT);
    $stock_minimo = filter_var($_POST["stock_minimo"] ?? 0, FILTER_VALIDATE_FLOAT);
    $costo = filter_var($_POST["costo"] ?? 0, FILTER_VALIDATE_FLOAT);
    $id_medida = filter_var($_POST["id_medida"] ?? 0, FILTER_VALIDATE_INT);

    if (empty($nombre)) {
        $mensaje = "El nombre del insumo es obligatorio.";
        $tipoMensaje = "error";
    } elseif ($cantidad_actual === false || $stock_minimo === false || $costo === false || $id_medida === false) {
        $mensaje = "Los valores numéricos ingresados no son válidos.";
        $tipoMensaje = "error";
    } else {
        $sql = "INSERT INTO Insumo (nombre, cantidad_actual, stock_minimo, costo, id_medida) 
                VALUES (?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($conexion, $sql);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sdddi", $nombre, $cantidad_actual, $stock_minimo, $costo, $id_medida);
            if (mysqli_stmt_execute($stmt)) {
                $mensaje = "¡Insumo registrado con éxito!";
                $tipoMensaje = "exito";
            } else {
                $mensaje = "Error al guardar el insumo en la base de datos.";
                $tipoMensaje = "error";
                error_log("Error MySQL INSERT: " . mysqli_stmt_error($stmt));
            }
            mysqli_stmt_close($stmt);
        } else {
            $mensaje = "Error al preparar la consulta.";
            $tipoMensaje = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar Nuevo Insumo</title>
    <link rel="stylesheet" href="../includes/style.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        .form-wrapper {
            max-width: 600px;
            margin: 30px auto;
            padding: 25px;
            background: #1e293b;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .form-wrapper h2 {
            color: #f8fafc;
            margin-bottom: 20px;
            font-size: 22px;
            border-bottom: 1px solid #334155;
            padding-bottom: 10px;
        }
        .campo {
            margin-bottom: 15px;
        }
        .campo label {
            display: block;
            color: #94a3b8;
            margin-bottom: 6px;
            font-size: 14px;
            font-weight: 500;
        }
        .campo input, .campo select {
            width: 100%;
            padding: 10px 12px;
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 6px;
            color: #fff;
            font-size: 14px;
            box-sizing: border-box;
        }
        .campo input:focus, .campo select:focus {
            border-color: #4ade80;
            outline: none;
        }
        .acciones-form {
            display: flex;
            gap: 10px;
            margin-top: 25px;
        }
        .btn-guardar {
            background: #4ade80;
            color: #0f172a;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-guardar:hover {
            background: #22c55e;
        }
        .btn-volver {
            background: #334155;
            color: #fff;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            transition: background 0.2s;
            display: inline-flex;
            align-items: center;
        }
        .btn-volver:hover {
            background: #475569;
        }
        .alerta {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 500;
        }
        .alerta.error {
            background: rgba(239, 68, 68, 0.15);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        .alerta.exito {
            background: rgba(74, 222, 128, 0.15);
            color: #4ade80;
            border: 1px solid rgba(74, 222, 128, 0.3);
        }
    </style>
</head>
<body>

    <!-- Barra Superior -->
    <div class="contenido-principal" style="padding: 0;">
        <div class="barra-superior" style="display: flex; justify-content: space-between; align-items: center; padding: 15px 25px; background: #0f172a; border-bottom: 1px solid #1e293b;">
            <h1 style="color: #fff; font-size: 20px; display: flex; align-items: center; gap: 10px; margin: 0;">
                <a href="../insumos.php" style="color: #4ade80; text-decoration: none;"><i class="ri-arrow-left-line"></i></a>
                Lo de Torres - Alta de Insumo
            </h1>
            <div class="usuario-superior-derecha" style="display: flex; align-items: center; gap: 8px; color: #4ade80; font-weight: bold;">
                <i class="ri-user-3-fill"></i>
                <span><?= htmlspecialchars($nombre_empleado, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </div>
    </div>

    <!-- Contenedor del Formulario -->
    <div class="form-wrapper">
        <h2>Registrar Nuevo Insumo</h2>

        <?php if ($mensaje !== ""): ?>
            <div class="alerta <?= $tipoMensaje ?>">
                <?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form action="alta_insumo.php" method="POST">
            
            <div class="campo">
                <label for="nombre">Nombre del Insumo *</label>
                <input type="text" id="nombre" name="nombre" required maxlength="100" autocomplete="off" value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>">
            </div>

            <div class="campo">
                <label for="id_medida">Unidad de Medida *</label>
                <select id="id_medida" name="id_medida" required>
                    <option value="">Seleccione una opción...</option>
                    <?php foreach ($medidas as $medida): ?>
                        <option value="<?= $medida['id_medida']; ?>"
                            <?= (isset($_POST['id_medida']) && $_POST['id_medida'] == $medida['id_medida']) ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($medida['unidad'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="campo">
                <label for="cantidad_actual">Cantidad Inicial</label>
                <input type="number" id="cantidad_actual" name="cantidad_actual" step="0.01" min="0" value="<?= htmlspecialchars($_POST['cantidad_actual'] ?? '0') ?>">
            </div>

            <div class="campo">
                <label for="stock_minimo">Stock Mínimo (Aviso)</label>
                <input type="number" id="stock_minimo" name="stock_minimo" step="0.01" min="0" value="<?= htmlspecialchars($_POST['stock_minimo'] ?? '0') ?>">
            </div>

            <div class="campo">
                <label for="costo">Costo ($)</label>
                <input type="number" id="costo" name="costo" step="0.01" min="0" value="<?= htmlspecialchars($_POST['costo'] ?? '0') ?>">
            </div>

            <div class="acciones-form">
                <button type="submit" class="btn-guardar">Guardar Insumo</button>
                <a href="../insumos.php" class="btn-volver">Volver al Listado</a>
            </div>

        </form>
    </div>

</body>
</html>