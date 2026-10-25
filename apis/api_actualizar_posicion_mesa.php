<?php
session_start();
include_once("../includes/conexionBD.php");
include_once("../includes/funciones.php");

header('Content-Type: application/json');

if (!isset($_SESSION['empleado_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit();
}

$id_mesa = intval($_POST['id_mesa'] ?? 0);
$pos_x = intval($_POST['pos_x'] ?? 0);
$pos_y = intval($_POST['pos_y'] ?? 0);

if ($id_mesa <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID de mesa inválido']);
    exit();
}

$sql = "UPDATE mesa SET pos_x = ?, pos_y = ? WHERE id_mesa = ?";
$stmt = mysqli_prepare($conexion, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "iii", $pos_x, $pos_y, $id_mesa);
    if (mysqli_stmt_execute($stmt)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al actualizar en la base de datos']);
    }
    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['success' => false, 'error' => 'Error en la consulta preparada']);
}


