<?php
include_once("../includes/conexionBD.php");
include_once("../includes/funciones.php");
verificar_sesion();

$nombre_empleado = $_SESSION['empleado_nombre'] ?? 'Empleado';
$id_empleado_sesion = $_SESSION['empleado_id'] ?? $_SESSION['id_empleado'] ?? $_SESSION['id_usuario'] ?? null;
$mensaje = "";

// 1. Obtener el turno de caja abierto actualmente
$sqlTurnoAbierto = "SELECT id_turno FROM turno_caja WHERE fecha_hora_cierre IS NULL LIMIT 1";
$resTurnoAbierto = mysqli_query($conexion, $sqlTurnoAbierto);
$turnoAbierto = mysqli_fetch_assoc($resTurnoAbierto);
$idTurnoActual = $turnoAbierto ? (int)$turnoAbierto['id_turno'] : null;

// 2. Obtener el id_estado para 'Pendiente' automáticamente
$sqlEstadoPendiente = "SELECT id_estado FROM Estado_Pedido WHERE LOWER(nombre) = 'pendiente' LIMIT 1";
$resEstadoPendiente = mysqli_query($conexion, $sqlEstadoPendiente);
$filaEstadoPendiente = mysqli_fetch_assoc($resEstadoPendiente);
$idEstadoPendiente = $filaEstadoPendiente ? (int)$filaEstadoPendiente['id_estado'] : 1; // 1 como fallback

// Consultas para poblar los demás selectores
$mesas = array();
$consultaMesas = mysqli_query($conexion, "SELECT id_mesa, numero FROM Mesa ORDER BY numero ASC");
if ($consultaMesas) {
    while ($row = mysqli_fetch_assoc($consultaMesas)) {
        $mesas[] = $row;
    }
}

$metodosPago = array();
$consultaMP = mysqli_query($conexion, "SELECT id_mp, descripcion FROM Metodo_pago ORDER BY descripcion ASC");
if ($consultaMP) {
    while ($row = mysqli_fetch_assoc($consultaMP)) {
        $metodosPago[] = $row;
    }
}

$clientes = array();
$consultaClientes = mysqli_query($conexion, "SELECT id_cliente, CONCAT(nombre, ' ', apellido) AS nombre_completo FROM Cliente ORDER BY nombre ASC");
if ($consultaClientes) {
    while ($row = mysqli_fetch_assoc($consultaClientes)) {
        $clientes[] = $row;
    }
}

$productos = array();
$consultaProductos = mysqli_query($conexion, "SELECT id_producto, nombre, precio FROM Producto WHERE activo = TRUE ORDER BY nombre ASC");
if ($consultaProductos) {
    while ($row = mysqli_fetch_assoc($consultaProductos)) {
        $productos[] = $row;
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $descripcion = trim($_POST["descripcion"] ?? "");
    $fechaEntrega = trim($_POST["fecha_hora_entrega"] ?? "");
    $fechaEntrega = $fechaEntrega === "" ? null : $fechaEntrega;
    $idMesa = filter_var($_POST["id_mesa"] ?? null, FILTER_VALIDATE_INT);
    $idEstado = $idEstadoPendiente; // Se fuerza siempre a 'Pendiente'
    $idMetodoPago = filter_var($_POST["id_mp"] ?? null, FILTER_VALIDATE_INT);
    $idTurno = $idTurnoActual;
    $idCliente = empty($_POST["id_cliente"]) ? null : filter_var($_POST["id_cliente"], FILTER_VALIDATE_INT);
    $idEmpleado = filter_var($_POST["id_empleado"] ?? $id_empleado_sesion, FILTER_VALIDATE_INT);

    $idsProductos = $_POST["id_producto"] ?? array();
    $cantidades = $_POST["cantidad"] ?? array();

    $lineas = array();
    $idsUsados = array();
    $montoTotal = 0;
    $productosValidos = is_array($idsProductos) && is_array($cantidades) && count($idsProductos) === count($cantidades) && count($idsProductos) > 0;

    if ($productosValidos) {
        foreach ($idsProductos as $indice => $valorId) {
            $idProducto = filter_var($valorId, FILTER_VALIDATE_INT);
            $cantidad = filter_var($cantidades[$indice], FILTER_VALIDATE_INT);

            if ($idProducto === false || $idProducto <= 0 || $cantidad === false || $cantidad <= 0 || isset($idsUsados[$idProducto])) {
                $productosValidos = false;
                break;
            }

            $stmtPrecio = mysqli_prepare($conexion, "SELECT precio FROM Producto WHERE id_producto = ? AND activo = TRUE");
            if (!$stmtPrecio) {
                $productosValidos = false;
                break;
            }
            mysqli_stmt_bind_param($stmtPrecio, "i", $idProducto);
            mysqli_stmt_execute($stmtPrecio);
            $resPrecio = mysqli_stmt_get_result($stmtPrecio);
            $filaPrecio = mysqli_fetch_assoc($resPrecio);
            mysqli_stmt_close($stmtPrecio);

            if (!$filaPrecio) {
                $productosValidos = false;
                break;
            }

            $precio = (float)$filaPrecio["precio"];
            $total = $precio * $cantidad;
            $montoTotal += $total;
            $idsUsados[$idProducto] = true;

            $lineas[] = array(
                "id_producto" => $idProducto,
                "cantidad" => $cantidad,
                "precio" => $precio,
                "total" => $total
            );
        }
    }

    if (!$idTurno) {
        $mensaje = "No hay ningún turno de caja abierto. Debe abrir la caja antes de ingresar un pedido.";
    } elseif ($idMesa === false || $idMesa <= 0 || $idEstado === false || $idEstado <= 0 || $idMetodoPago === false || $idMetodoPago <= 0 || $idEmpleado === false || $idEmpleado <= 0) {
        $mensaje = "Debe completar todos los datos obligatorios del pedido.";
    } elseif (!$productosValidos) {
        $mensaje = "Debe seleccionar al menos un producto válido y no repetirlo.";
    } else {
        mysqli_begin_transaction($conexion);
        $correcto = true;

        $sqlPedido = "INSERT INTO Pedido (descripcion, fecha_hora_entrega, monto_total, id_empleado, id_turno, id_mesa, id_estado, id_cliente, id_mp) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmtPedido = mysqli_prepare($conexion, $sqlPedido);
        if ($stmtPedido) {
            mysqli_stmt_bind_param($stmtPedido, "ssdiiiiii", $descripcion, $fechaEntrega, $montoTotal, $idEmpleado, $idTurno, $idMesa, $idEstado, $idCliente, $idMetodoPago);
            $correcto = mysqli_stmt_execute($stmtPedido);
            $idPedido = mysqli_insert_id($conexion);
            mysqli_stmt_close($stmtPedido);
        } else {
            $correcto = false;
        }

        if ($correcto) {
            $stmtContiene = mysqli_prepare($conexion, "INSERT INTO Contiene (id_pedido, id_producto, cantidad, precio, total) VALUES (?, ?, ?, ?, ?)");
            $correcto = $stmtContiene !== false;
            if ($correcto) {
                foreach ($lineas as $linea) {
                    $idProd = $linea["id_producto"];
                    $cant = $linea["cantidad"];
                    $prec = $linea["precio"];
                    $tot = $linea["total"];
                    mysqli_stmt_bind_param($stmtContiene, "iiidd", $idPedido, $idProd, $cant, $prec, $tot);
                    if (!mysqli_stmt_execute($stmtContiene)) {
                        $correcto = false;
                        break;
                    }
                }
                mysqli_stmt_close($stmtContiene);
            }
        }

        if ($correcto) {
            $stmtMesa = mysqli_prepare($conexion, "SELECT id_estado FROM Estado_Mesa WHERE LOWER(nombre) = 'ocupada' LIMIT 1");
            if ($stmtMesa) {
                mysqli_stmt_execute($stmtMesa);
                $resMesa = mysqli_stmt_get_result($stmtMesa);
                $estadoOcupada = mysqli_fetch_assoc($resMesa);
                mysqli_stmt_close($stmtMesa);

                if ($estadoOcupada) {
                    $stmtUpdateMesa = mysqli_prepare($conexion, "UPDATE Mesa SET id_estado = ? WHERE id_mesa = ?");
                    if ($stmtUpdateMesa) {
                        $idEstOcupada = (int)$estadoOcupada["id_estado"];
                        mysqli_stmt_bind_param($stmtUpdateMesa, "ii", $idEstOcupada, $idMesa);
                        mysqli_stmt_execute($stmtUpdateMesa);
                        mysqli_stmt_close($stmtUpdateMesa);
                    }
                }
            }
        }

        if ($correcto) {
            mysqli_commit($conexion);
            header("Location: ../pedidos.php");
            exit();
        }

        mysqli_rollback($conexion);
        $mensaje = "No se pudo registrar el pedido.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nuevo Pedido</title>
    <link rel="stylesheet" href="../includes/style.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
</head>
<body>

    <div class="contenido-principal" style="padding: 0;">
        <div class="barra-superior">
            <h1>
                <a href="../pedidos.php" style="color: var(--accent-green); text-decoration: none;"><i class="ri-arrow-left-line"></i></a>
                Lo de Torres - Nuevo Pedido
            </h1>
            <div class="billetera-usuario" style="color: var(--accent-green);">
                <i class="ri-user-3-fill"></i>
                <span><?= htmlspecialchars($nombre_empleado, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </div>
    </div>

    <div class="contenedor-insumos">
        <h2 style="color: var(--color-text-main); margin-bottom: 20px; font-size: 24px;">Registrar Nuevo Pedido</h2>

        <?php if (!$idTurnoActual): ?>
            <div style="background: rgba(255, 74, 90, 0.15); border: 1px solid var(--accent-red); color: var(--accent-red); padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px;">
                ⚠️ <strong>Caja cerrada:</strong> No hay ningún turno de caja abierto en este momento. Debe ir a <a href="../caja.php" style="color: #fff; text-decoration: underline;">Control de Caja</a> para abrir un turno antes de realizar un pedido.
            </div>
        <?php endif; ?>

        <?php if ($mensaje !== ""): ?>
            <div style="background: rgba(255, 74, 90, 0.15); border: 1px solid var(--accent-red); color: var(--accent-red); padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px;">
                <?php echo htmlspecialchars($mensaje, ENT_QUOTES, "UTF-8"); ?>
            </div>
        <?php endif; ?>

        <form action="alta_pedido.php" method="POST" style="background: #1e293b; padding: 25px; border-radius: 8px; border: 1px solid #334155; display: flex; flex-direction: column; gap: 16px; max-width: 650px;">
            
            <input type="hidden" name="id_empleado" value="<?php echo htmlspecialchars($id_empleado_sesion ?? 1, ENT_QUOTES, 'UTF-8'); ?>">

            <div style="display: flex; flex-direction: column; gap: 6px;">
                <label for="id_mesa" style="color: var(--color-text-body); font-weight: 500; font-size: 14px;">Mesa *</label>
                <select id="id_mesa" name="id_mesa" required style="padding: 10px 15px; background: #0f172a; border: 1px solid #334155; border-radius: 6px; color: #fff; font-size: 14px; outline: none;">
                    <option value="">Seleccione una mesa...</option>
                    <?php foreach ($mesas as $mesa): ?>
                        <option value="<?php echo $mesa["id_mesa"]; ?>" <?php echo ((int)$mesa["id_mesa"] === (int)($_POST["id_mesa"] ?? 0)) ? "selected" : ""; ?>>
                            Mesa N° <?php echo htmlspecialchars($mesa["numero"], ENT_QUOTES, "UTF-8"); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display: flex; gap: 12px;">
                <!-- Estado fijado informativamente a Pendiente -->
                <div style="flex: 1; display: flex; flex-direction: column; gap: 6px;">
                    <label style="color: var(--color-text-body); font-weight: 500; font-size: 14px;">Estado Inicial</label>
                    <div style="padding: 10px 15px; background: #0f172a; border: 1px solid #334155; border-radius: 6px; color: #f59e0b; font-size: 14px; font-weight: 500;">
                        Pendiente
                    </div>
                </div>

                <div style="flex: 1; display: flex; flex-direction: column; gap: 6px;">
                    <label for="id_mp" style="color: var(--color-text-body); font-weight: 500; font-size: 14px;">Método de Pago *</label>
                    <select id="id_mp" name="id_mp" required style="padding: 10px 15px; background: #0f172a; border: 1px solid #334155; border-radius: 6px; color: #fff; font-size: 14px; outline: none;">
                        <option value="">Seleccione método...</option>
                        <?php foreach ($metodosPago as $mp): ?>
                            <option value="<?php echo $mp["id_mp"]; ?>" <?php echo ((int)$mp["id_mp"] === (int)($_POST["id_mp"] ?? 0)) ? "selected" : ""; ?>>
                                <?php echo htmlspecialchars($mp["descripcion"], ENT_QUOTES, "UTF-8"); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div style="display: flex; gap: 12px;">
                <div style="flex: 1; display: flex; flex-direction: column; gap: 6px;">
                    <label style="color: var(--color-text-body); font-weight: 500; font-size: 14px;">Turno de Caja Activo</label>
                    <div style="padding: 10px 15px; background: #0f172a; border: 1px solid #334155; border-radius: 6px; color: <?php echo $idTurnoActual ? 'var(--accent-green)' : 'var(--accent-red)'; ?>; font-size: 14px; font-weight: 500;">
                        <?php echo $idTurnoActual ? "Turno N° {$idTurnoActual} (Abierto)" : "Caja Cerrada"; ?>
                    </div>
                </div>

                <div style="flex: 1; display: flex; flex-direction: column; gap: 6px;">
                    <label for="id_cliente" style="color: var(--color-text-body); font-weight: 500; font-size: 14px;">Cliente (Opcional)</label>
                    <select id="id_cliente" name="id_cliente" style="padding: 10px 15px; background: #0f172a; border: 1px solid #334155; border-radius: 6px; color: #fff; font-size: 14px; outline: none;">
                        <option value="">Sin cliente asignado</option>
                        <?php foreach ($clientes as $cliente): ?>
                            <option value="<?php echo $cliente["id_cliente"]; ?>" <?php echo ((int)$cliente["id_cliente"] === (int)($_POST["id_cliente"]) ? "selected" : ""); ?>>
                                <?php echo htmlspecialchars($cliente["nombre_completo"], ENT_QUOTES, "UTF-8"); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 6px;">
                <label for="fecha_hora_entrega" style="color: var(--color-text-body); font-weight: 500; font-size: 14px;">Fecha/Hora Entrega (Opcional)</label>
                <input type="datetime-local" id="fecha_hora_entrega" name="fecha_hora_entrega" 
                       value="<?php echo htmlspecialchars($_POST["fecha_hora_entrega"] ?? "", ENT_QUOTES, "UTF-8"); ?>"
                       style="padding: 10px 15px; background: #0f172a; border: 1px solid #334155; border-radius: 6px; color: #fff; font-size: 14px; outline: none;">
            </div>

            <div style="display: flex; flex-direction: column; gap: 6px;">
                <label for="descripcion" style="color: var(--color-text-body); font-weight: 500; font-size: 14px;">Descripción / Observaciones</label>
                <input type="text" id="descripcion" name="descripcion" maxlength="255" placeholder="Ej: Sin cebolla, bien cocido..." 
                       value="<?php echo htmlspecialchars($_POST["descripcion"] ?? "", ENT_QUOTES, "UTF-8"); ?>"
                       style="padding: 10px 15px; background: #0f172a; border: 1px solid #334155; border-radius: 6px; color: #fff; font-size: 14px; outline: none;">
            </div>

            <h3 style="color: var(--color-text-main); font-size: 1.1rem; margin-top: 10px; margin-bottom: 0;">Productos del pedido</h3>
            
            <div id="componentes" style="display: flex; flex-direction: column; gap: 10px;">
                <div class="componente" style="display: flex; gap: 10px; align-items: center;">
                    <select name="id_producto[]" required style="flex: 2; padding: 10px 15px; background: #0f172a; border: 1px solid #334155; border-radius: 6px; color: #fff; font-size: 14px; outline: none;">
                        <option value="">Seleccione un producto...</option>
                        <?php foreach ($productos as $prod): ?>
                            <option value="<?php echo $prod["id_producto"]; ?>"><?php echo htmlspecialchars($prod["nombre"] . " - $" . $prod["precio"], ENT_QUOTES, "UTF-8"); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="number" name="cantidad[]" min="1" step="1" value="1" placeholder="Cantidad" required style="flex: 1; padding: 10px 15px; background: #0f172a; border: 1px solid #334155; border-radius: 6px; color: #fff; font-size: 14px; outline: none;">
                    <button type="button" onclick="quitar(this)" style="background: rgba(255, 74, 90, 0.2); color: var(--accent-red); border: 1px solid var(--accent-red); padding: 10px 12px; border-radius: 6px; cursor: pointer; font-weight: bold;">Quitar</button>
                </div>
            </div>

            <div>
                <button type="button" onclick="agregar()" style="background: #334155; color: #fff; border: none; padding: 8px 14px; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 13px;">+ Agregar producto</button>
            </div>

            <div style="display: flex; gap: 12px; align-items: center; margin-top: 10px;">
                <button type="submit" class="btn-nuevo" <?php echo !$idTurnoActual ? 'disabled style="opacity: 0.5; cursor: not-allowed;"' : 'style="border: none; cursor: pointer; font-size: 14px;"'; ?>>
                    Guardar pedido
                </button>
                <a href="../pedidos.php" style="color: var(--color-text-muted); text-decoration: none; font-size: 14px; font-weight: 500;">Volver al listado</a>
            </div>

        </form>
    </div>

    <template id="plantilla">
        <div class="componente" style="display: flex; gap: 10px; align-items: center;">
            <select name="id_producto[]" required style="flex: 2; padding: 10px 15px; background: #0f172a; border: 1px solid #334155; border-radius: 6px; color: #fff; font-size: 14px; outline: none;">
                <option value="">Seleccione un producto...</option>
                <?php foreach ($productos as $prod): ?>
                    <option value="<?php echo $prod["id_producto"]; ?>"><?php echo htmlspecialchars($prod["nombre"] . " - $" . $prod["precio"], ENT_QUOTES, "UTF-8"); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="number" name="cantidad[]" min="1" step="1" value="1" placeholder="Cantidad" required style="flex: 1; padding: 10px 15px; background: #0f172a; border: 1px solid #334155; border-radius: 6px; color: #fff; font-size: 14px; outline: none;">
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