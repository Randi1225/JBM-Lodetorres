<?php
include_once("../includes/conexionBD.php");
include_once("../includes/funciones.php");

if (function_exists('verificar_sesion_api')) {
    verificar_sesion_api();
}

header('Content-Type: application/json; charset=utf-8');

if (isset($_GET["tipo"]) && isset($_GET["id"])) {
    $id = (int)$_GET["id"];
    $tipo = $_GET['tipo'];
    $tabla = "";
    $campo_id = "";

    switch ($tipo) {
        case "insumo":
            $tabla = "Insumo"; // Revisa si esta tabla también está en plural (ej: insumos)
            $campo_id = "id_insumo";
            break;
        case "cliente":
            $tabla = "Cliente"; // Revisa si esta tabla está en plural (ej: clientes)
            $campo_id = "id_cliente";
            break;
        case "producto":
            $tabla = "Producto"; // Revisa si esta tabla está en plural (ej: productos)
            $campo_id = "id_producto";
            break;
        case "empleado":
            $tabla = "empleados"; // <--- Nombre exacto de tu tabla en la BD
            $campo_id = "id_empleado";
            break;
        default:
            http_response_code(400);
            echo json_encode(["error" => "Tipo no válido"]);
            exit;
    }

    // Ejecutamos la actualización del estado 'activo' a 0 (desactivado)
    $sql = "UPDATE $tabla SET activo = 0 WHERE $campo_id = $id";
    $resultado = mysqli_query($conexion, $sql);

    if ($resultado) {
        http_response_code(200);
        echo json_encode(["success" => "Empleado desactivado correctamente"]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Error en la base de datos: " . mysqli_error($conexion)]);
    }

} else {
    http_response_code(400);
    echo json_encode(["error" => "Datos incompletos"]);
}