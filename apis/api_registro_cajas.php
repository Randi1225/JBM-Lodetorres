<?php
include_once("../includes/conexionBD.php");
include_once("../includes/funciones.php");
verificar_sesion_api();

header('Content-Type: application/json; charset=utf-8');

$accion = $_GET['accion'] ?? '';

if ($accion === 'eliminar') {
    $id_turno = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT);
    if ($id_turno) {
        $sql = "DELETE FROM turno_caja WHERE id_turno = ? AND fecha_hora_cierre IS NOT NULL";
        $stmt = mysqli_prepare($conexion, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id_turno);
        $success = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        echo json_encode(array("success" => $success), JSON_UNESCAPED_UNICODE);
        exit();
    }
    echo json_encode(array("success" => false), JSON_UNESCAPED_UNICODE);
    exit();
}

// Consultar historial de turnos cerrados
$fecha = trim($_GET['fecha'] ?? '');

if ($fecha !== '') {
    $sql = "SELECT t.*, e.nombre, e.apellido 
            FROM turno_caja t 
            INNER JOIN empleados e ON t.id_empleado = e.id_empleado 
            WHERE t.fecha_hora_cierre IS NOT NULL 
              AND DATE(t.fecha_hora_apertura) = ? 
            ORDER BY t.id_turno DESC";
    $stmt = mysqli_prepare($conexion, $sql);
    mysqli_stmt_bind_param($stmt, "s", $fecha);
} else {
    $sql = "SELECT t.*, e.nombre, e.apellido 
            FROM turno_caja t 
            INNER JOIN empleados e ON t.id_empleado = e.id_empleado 
            WHERE t.fecha_hora_cierre IS NOT NULL 
            ORDER BY t.id_turno DESC 
            LIMIT 100"; // Últimos 100 registros por rendimiento
    $stmt = mysqli_prepare($conexion, $sql);
}

mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);

$registros = array();
while ($row = mysqli_fetch_assoc($resultado)) {
    $registros[] = $row;
}

mysqli_stmt_close($stmt);
echo json_encode($registros, JSON_UNESCAPED_UNICODE);