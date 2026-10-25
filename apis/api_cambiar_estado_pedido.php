<?php
include_once("../includes/conexionBD.php");
include_once("../includes/funciones.php");

if (ob_get_length()) {
    ob_clean();
}

verificar_sesion_api();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $idPedido = filter_var($_POST["id_pedido"] ?? 0, FILTER_VALIDATE_INT);
    $nombreEstado = trim($_POST["estado"] ?? "");

    if (!$idPedido || empty($nombreEstado)) {
        echo json_encode(array("success" => false, "error" => "Datos incompletos."), JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Buscar el id_estado correspondiente al nombre solicitado
    $sqlEstado = "SELECT id_estado FROM Estado_Pedido WHERE LOWER(nombre) = LOWER(?) LIMIT 1";
    $stmtEst = mysqli_prepare($conexion, $sqlEstado);
    mysqli_stmt_bind_param($stmtEst, "s", $nombreEstado);
    mysqli_stmt_execute($stmtEst);
    $resEst = mysqli_stmt_get_result($stmtEst);
    $rowEst = mysqli_fetch_assoc($resEst);
    mysqli_stmt_close($stmtEst);

    if (!$rowEst) {
        echo json_encode(array("success" => false, "error" => "El estado especificado no existe."), JSON_UNESCAPED_UNICODE);
        exit();
    }

    $idEstadoNuevo = (int)$rowEst["id_estado"];

    // Actualizar el estado del pedido
    $sqlUpdate = "UPDATE Pedido SET id_estado = ? WHERE id_pedido = ?";
    $stmtUp = mysqli_prepare($conexion, $sqlUpdate);
    mysqli_stmt_bind_param($stmtUp, "ii", $idEstadoNuevo, $idPedido);
    $exito = mysqli_stmt_execute($stmtUp);
    mysqli_stmt_close($stmtUp);

    echo json_encode(array("success" => $exito), JSON_UNESCAPED_UNICODE);
    exit();
}

echo json_encode(array("success" => false, "error" => "Método no permitido."), JSON_UNESCAPED_UNICODE);