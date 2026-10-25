<?php
session_start();
include_once("../includes/conexionBD.php");
include_once("../includes/funciones.php");

verificar_sesion();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id_mesa = intval($_POST['id_mesa'] ?? 0);

    if ($id_mesa <= 0) {
        header("Location: ../mapa.php?error=id_invalido");
        exit();
    }

    $sql = "DELETE FROM mesa WHERE id_mesa = ?";
    $stmt = mysqli_prepare($conexion, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id_mesa);
        if (mysqli_stmt_execute($stmt)) {
            header("Location: ../mapa.php?success=mesa_eliminada");
            exit();
        }
        mysqli_stmt_close($stmt);
    }
    header("Location: ../mapa.php?error=bd_error");
    exit();
}