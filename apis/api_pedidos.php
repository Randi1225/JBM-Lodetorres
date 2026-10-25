<?php
include_once("../includes/conexionBD.php");
include_once("../includes/funciones.php");

if (ob_get_length()) {
    ob_clean();
}

verificar_sesion_api();
header('Content-Type: application/json; charset=utf-8');

$limite = 20;
if (isset($_GET["limite"])) {
    if ($_GET["limite"] === "sin") {
        $limite = 9999999;
    } else {
        $limite = max(1, (int)$_GET["limite"]);
    }
}

$pagina = 1;
if (isset($_GET["pagina"])) {
    $pagina = max(1, (int)$_GET["pagina"]);
}

$desdequeelemento = ($pagina - 1) * $limite;
$filtro = trim($_GET["filtro"] ?? "");

// Consulta principal con LEFT JOIN
$sql = "SELECT p.id_pedido, 
               p.descripcion, 
               p.fecha_hora_creacion, 
               p.fecha_hora_entrega, 
               p.monto_total, 
               m.numero AS mesa, 
               CONCAT(c.nombre, ' ', c.apellido) AS cliente, 
               ep.nombre AS estado,
               mp.descripcion AS metodo_pago,
               p.id_mp
        FROM Pedido p
        LEFT JOIN Mesa m ON p.id_mesa = m.id_mesa
        LEFT JOIN Cliente c ON p.id_cliente = c.id_cliente
        LEFT JOIN Metodo_pago mp ON p.id_mp = mp.id_mp
        INNER JOIN Estado_Pedido ep ON p.id_estado = ep.id_estado";

$params = array();
$types = "";

if ($filtro === "cocina") {
    // Pedidos activos para el panel de Cocina
    $sql .= " WHERE LOWER(ep.nombre) IN ('pendiente', 'en preparación', 'en preparacion')";
} elseif ($filtro === "sin_cobrar") {
    // Pedidos listos para cobrar (excluye Finalizados, Cobrados o Cancelados)
    $sql .= " WHERE LOWER(ep.nombre) NOT IN ('finalizado', 'cobrado', 'cancelado', 'anulado')";
} elseif ($filtro !== "" && $filtro !== "undefined") {
    $sql .= " WHERE (p.id_pedido LIKE ? 
               OR ep.nombre LIKE ? 
               OR m.numero LIKE ? 
               OR c.nombre LIKE ? 
               OR c.apellido LIKE ?)";
    $filtroParam = "%" . $filtro . "%";
    $params = array($filtroParam, $filtroParam, $filtroParam, $filtroParam, $filtroParam);
    $types = "sssss";
}

$sql .= " ORDER BY p.id_pedido DESC LIMIT ? OFFSET ?";
$params[] = $limite;
$params[] = $desdequeelemento;
$types .= "ii";

$stmt = mysqli_prepare($conexion, $sql);
if ($stmt) {
    if (!empty($types)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);

    $pedidos = array();
    while ($row = mysqli_fetch_assoc($resultado)) {
        // Consultar productos asociados
        $idPed = $row['id_pedido'];
        $sqlProd = "SELECT c.cantidad, pr.nombre 
                    FROM Contiene c 
                    INNER JOIN Producto pr ON c.id_producto = pr.id_producto 
                    WHERE c.id_pedido = ?";
        $stmtProd = mysqli_prepare($conexion, $sqlProd);
        if ($stmtProd) {
            mysqli_stmt_bind_param($stmtProd, "i", $idPed);
            mysqli_stmt_execute($stmtProd);
            $resProd = mysqli_stmt_get_result($stmtProd);
            $productos = array();
            while ($p = mysqli_fetch_assoc($resProd)) {
                $productos[] = $p;
            }
            mysqli_stmt_close($stmtProd);
            $row['productos'] = $productos;
        } else {
            $row['productos'] = array();
        }

        $pedidos[] = $row;
    }
    mysqli_stmt_close($stmt);

    echo json_encode($pedidos, JSON_UNESCAPED_UNICODE);
    exit();
} else {
    http_response_code(500);
    echo json_encode(array("error" => "Error en la consulta a la base de datos."), JSON_UNESCAPED_UNICODE);
    exit();
}