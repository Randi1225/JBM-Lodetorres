<?php
session_start();
include_once("../includes/conexionBD.php");
include_once("../includes/funciones.php");

verificar_sesion();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $numero = intval($_POST['numero'] ?? 0);
    $ancho = intval($_POST['ancho'] ?? 90);

    if ($numero <= 0) {
        header("Location: ../mapa.php?error=numero_invalido");
        exit();
    }

    // Insertando usando tu tabla 'mesa' con id_estado por defecto 1 (libre)
    $sql = "INSERT INTO mesa (numero, ancho, id_estado, pos_x, pos_y) VALUES (?, ?, 1, 20, 20)";
    $stmt = mysqli_prepare($conexion, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ii", $numero, $ancho);
        if (mysqli_stmt_execute($stmt)) {
            header("Location: ../mapa.php?success=mesa_creada");
            exit();
        }
        mysqli_stmt_close($stmt);
    }
    header("Location: ../mapa.php?error=bd_error");
    exit();
}