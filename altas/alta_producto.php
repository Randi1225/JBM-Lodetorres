<?php
include_once("../includes/conexionBD.php");
include_once("../includes/funciones.php");
verificar_sesion();

$nombre_empleado = $_SESSION['empleado_nombre'] ?? 'Empleado';
$mensaje = "";
$categorias = array();
$consultaCategorias = mysqli_query($conexion, "SELECT id_categoria, titulo FROM Categoria ORDER BY titulo ASC");
if ($consultaCategorias) {
    while ($row = mysqli_fetch_assoc($consultaCategorias)) {
        $categorias[] = $row;
    }
}

$insumos = array();
$consultaInsumos = mysqli_query($conexion, "SELECT id_insumo, nombre FROM Insumo WHERE activo = TRUE ORDER BY nombre ASC");
if ($consultaInsumos) {
    while ($row = mysqli_fetch_assoc($consultaInsumos)) {
        $insumos[] = $row;
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = trim($_POST["nombre"] ?? "");
    $precio = filter_var($_POST["precio"] ?? null, FILTER_VALIDATE_FLOAT);
    $id_categoria = filter_var($_POST["id_categoria"] ?? null, FILTER_VALIDATE_INT);
    $idsInsumos = $_POST["id_insumo"] ?? array();
    $cantidades = $_POST["cantidad"] ?? array();
    $componentes = array();
    $idsUsados = array();
    $componentesValidos = is_array($idsInsumos) && is_array($cantidades) && count($idsInsumos) === count($cantidades) && count($idsInsumos) > 0;

    if ($componentesValidos) {
        foreach ($idsInsumos as $indice => $valorId) {
            $idInsumo = filter_var($valorId, FILTER_VALIDATE_INT);
            $cantidad = filter_var($cantidades[$indice], FILTER_VALIDATE_FLOAT);
            if ($idInsumo === false || $idInsumo <= 0 || $cantidad === false || $cantidad <= 0 || isset($idsUsados[$idInsumo])) {
                $componentesValidos = false;
                break;
            }
            $idsUsados[$idInsumo] = true;
            $componentes[] = array("id_insumo" => $idInsumo, "cantidad" => $cantidad);
        }
    }

    if ($nombre === "") {
        $mensaje = "El nombre del producto es obligatorio.";
    } elseif ($precio === false || $precio < 0 || $id_categoria === false || $id_categoria <= 0) {
        $mensaje = "El precio o la categoría no son válidos.";
    } elseif (!$componentesValidos) {
        $mensaje = "Debe indicar al menos un insumo válido y no repetirlo.";
    } else {
        mysqli_begin_transaction($conexion);
        $correcto = false;
        $sql = "INSERT INTO Producto (nombre, precio, id_categoria) VALUES (?, ?, ?)";
        $stmtProducto = mysqli_prepare($conexion, $sql);
        if ($stmtProducto) {
            mysqli_stmt_bind_param($stmtProducto, "sdi", $nombre, $precio, $id_categoria);
            $correcto = mysqli_stmt_execute($stmtProducto);
            $idProducto = mysqli_insert_id($conexion);
            mysqli_stmt_close($stmtProducto);
        }

        if ($correcto) {
            $stmtComponente = mysqli_prepare($conexion, "INSERT INTO Compone (id_producto, id_insumo, cantidad) VALUES (?, ?, ?)");
            $correcto = $stmtComponente !== false;
            if ($correcto) {
                foreach ($componentes as $componente) {
                    $idInsumo = $componente["id_insumo"];
                    $cantidad = $componente["cantidad"];
                    mysqli_stmt_bind_param($stmtComponente, "iid", $idProducto, $idInsumo, $cantidad);
                    if (!mysqli_stmt_execute($stmtComponente)) {
                        $correcto = false;
                        break;
                    }
                }
                mysqli_stmt_close($stmtComponente);
            }
        }

        if ($correcto) {
            mysqli_commit($conexion);
            header("Location: ../productos.php");
            exit();
        }

        mysqli_rollback($conexion);
        $mensaje = "No se pudo registrar el producto y sus insumos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nuevo Producto</title>
    <link rel="stylesheet" href="../includes/style.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
</head>
<body>

    <div class="contenido-principal" style="padding: 0;">
        <div class="barra-superior">
            <h1>
                <a href="../productos.php" style="color: var(--accent-green); text-decoration: none;"><i class="ri-arrow-left-line"></i></a>
                Lo de Torres - Nuevo Producto
            </h1>
            <div class="billetera-usuario" style="color: var(--accent-green);">
                <i class="ri-user-3-fill"></i>
                <span><?= htmlspecialchars($nombre_empleado, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </div>
    </div>

    <div class="contenedor-insumos">
        <h2 style="color: var(--color-text-main); margin-bottom: 20px; font-size: 24px;">Registrar Nuevo Producto</h2>

        <?php if ($mensaje !== ""): ?>
            <div style="background: rgba(255, 74, 90, 0.15); border: 1px solid var(--accent-red); color: var(--accent-red); padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px;">
                <?php echo htmlspecialchars($mensaje, ENT_QUOTES, "UTF-8"); ?>
            </div>
        <?php endif; ?>

        <form action="alta_producto.php" method="POST" style="background: #1e293b; padding: 25px; border-radius: 8px; border: 1px solid #334155; display: flex; flex-direction: column; gap: 16px; max-width: 600px;">
            
            <div style="display: flex; flex-direction: column; gap: 6px;">
                <label for="nombre" style="color: var(--color-text-body); font-weight: 500; font-size: 14px;">Nombre *</label>
                <input type="text" id="nombre" name="nombre" maxlength="100" required
                       value="<?php echo htmlspecialchars($_POST["nombre"] ?? "", ENT_QUOTES, "UTF-8"); ?>"
                       style="padding: 10px 15px; background: #0f172a; border: 1px solid #334155; border-radius: 6px; color: #fff; font-size: 14px; outline: none;">
            </div>

            <div style="display: flex; flex-direction: column; gap: 6px;">
                <label for="precio" style="color: var(--color-text-body); font-weight: 500; font-size: 14px;">Precio *</label>
                <input type="number" id="precio" name="precio" min="0" step="0.01" required
                       value="<?php echo htmlspecialchars($_POST["precio"] ?? "", ENT_QUOTES, "UTF-8"); ?>"
                       style="padding: 10px 15px; background: #0f172a; border: 1px solid #334155; border-radius: 6px; color: #fff; font-size: 14px; outline: none;">
            </div>

            <div style="display: flex; flex-direction: column; gap: 6px;">
                <label for="id_categoria" style="color: var(--color-text-body); font-weight: 500; font-size: 14px;">Categoría *</label>
                <select id="id_categoria" name="id_categoria" required
                        style="padding: 10px 15px; background: #0f172a; border: 1px solid #334155; border-radius: 6px; color: #fff; font-size: 14px; outline: none;">
                    <option value="">Seleccione una opción...</option>
                    <?php foreach ($categorias as $categoria): ?>
                        <option value="<?php echo $categoria["id_categoria"]; ?>" <?php echo ((int)$categoria["id_categoria"] === (int)($_POST["id_categoria"] ?? 0)) ? "selected" : ""; ?>>
                            <?php echo htmlspecialchars($categoria["titulo"], ENT_QUOTES, "UTF-8"); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <h3 style="color: var(--color-text-main); font-size: 1.1rem; margin-top: 10px; margin-bottom: 0;">Insumos del producto</h3>
            
            <div id="componentes" style="display: flex; flex-direction: column; gap: 10px;">
                <div class="componente" style="display: flex; gap: 10px; align-items: center;">
                    <select name="id_insumo[]" required style="flex: 2; padding: 10px 15px; background: #0f172a; border: 1px solid #334155; border-radius: 6px; color: #fff; font-size: 14px; outline: none;">
                        <option value="">Seleccione un insumo...</option>
                        <?php foreach ($insumos as $insumo): ?>
                            <option value="<?php echo $insumo["id_insumo"]; ?>"><?php echo htmlspecialchars($insumo["nombre"], ENT_QUOTES, "UTF-8"); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" name="cantidad[]" min="0.01" step="0.01" placeholder="Cantidad" required style="flex: 1; padding: 10px 15px; background: #0f172a; border: 1px solid #334155; border-radius: 6px; color: #fff; font-size: 14px; outline: none;">
                    <button type="button" onclick="quitar(this)" style="background: rgba(255, 74, 90, 0.2); color: var(--accent-red); border: 1px solid var(--accent-red); padding: 10px 12px; border-radius: 6px; cursor: pointer; font-weight: bold;">Quitar</button>
                </div>
            </div>

            <div>
                <button type="button" onclick="agregar()" style="background: #334155; color: #fff; border: none; padding: 8px 14px; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 13px;">+ Agregar insumo</button>
            </div>

            <div style="display: flex; gap: 12px; align-items: center; margin-top: 10px;">
                <button type="submit" class="btn-nuevo" style="border: none; cursor: pointer; font-size: 14px;">Guardar producto</button>
                <a href="../productos.php" style="color: var(--color-text-muted); text-decoration: none; font-size: 14px; font-weight: 500;">Volver al listado</a>
            </div>

        </form>
    </div>

    <template id="plantilla">
        <div class="componente" style="display: flex; gap: 10px; align-items: center;">
            <select name="id_insumo[]" required style="flex: 2; padding: 10px 15px; background: #0f172a; border: 1px solid #334155; border-radius: 6px; color: #fff; font-size: 14px; outline: none;">
                <option value="">Seleccione un insumo...</option>
                <?php foreach ($insumos as $insumo): ?>
                    <option value="<?php echo $insumo["id_insumo"]; ?>"><?php echo htmlspecialchars($insumo["nombre"], ENT_QUOTES, "UTF-8"); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="number" name="cantidad[]" min="0.01" step="0.01" placeholder="Cantidad" required style="flex: 1; padding: 10px 15px; background: #0f172a; border: 1px solid #334155; border-radius: 6px; color: #fff; font-size: 14px; outline: none;">
            <button type="button" onclick="quitar(this)" style="background: rgba(255, 74, 90, 0.2); color: var(--accent-red); border: 1px solid var(--accent-red); padding: 10px 12px; border-radius: 6px; cursor: pointer; font-weight: bold;">Quitar</button>
        </div>
    </template>

    <script>
        function agregar() { 
            document.getElementById("componentes").appendChild(document.getElementById("plantilla").content.cloneNode(true)); 
        }
        function quitar(boton) { 
            if (document.querySelectorAll("#componentes .componente").length > 1) {
                boton.parentElement.remove(); 
            }
        }
    </script>
</body>
</html>