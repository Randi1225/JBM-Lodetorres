<?php
include_once("../includes/conexionBD.php");
include_once("../includes/funciones.php");
verificar_sesion();

$accion = $_GET['accion'] ?? '';
$id_empleado = $_SESSION['empleado_id'] ?? $_SESSION['id_empleado'] ?? 0;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    if ($accion === 'abrir') {
        $monto_inicial = filter_var($_POST["monto_inicial"] ?? 0, FILTER_VALIDATE_FLOAT);

        // Verificar que no haya otro turno ya abierto (fecha_hora_cierre IS NULL)
        $check = mysqli_query($conexion, "SELECT id_turno FROM turno_caja WHERE fecha_hora_cierre IS NULL LIMIT 1");
        if (mysqli_num_rows($check) > 0) {
            header("Location: ../caja.php?error=Ya_existe_turno_abierto");
            exit();
        }

        $sql = "INSERT INTO turno_caja (fecha_hora_apertura, monto_inicial, id_empleado) VALUES (NOW(), ?, ?)";
        $stmt = mysqli_prepare($conexion, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "di", $monto_inicial, $id_empleado);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        header("Location: ../caja.php");
        exit();

    } elseif ($accion === 'cerrar') {
        $id_turno = filter_var($_POST["id_turno"] ?? 0, FILTER_VALIDATE_INT);
        $monto_final = filter_var($_POST["monto_final"] ?? 0, FILTER_VALIDATE_FLOAT);

        $sql = "UPDATE turno_caja SET monto_final = ?, fecha_hora_cierre = NOW() WHERE id_turno = ? AND fecha_hora_cierre IS NULL";
        $stmt = mysqli_prepare($conexion, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "di", $monto_final, $id_turno);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        header("Location: ../caja.php");
        exit();
    }
}

header("Location: ../caja.php");
exit();