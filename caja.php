<?php
include_once("includes/funciones.php");
verificar_sesion();

$nombre_empleado = $_SESSION['empleado_nombre'] ?? 'Empleado';
$id_empleado = $_SESSION['empleado_id'] ?? $_SESSION['id_empleado'] ?? 0;

include_once("includes/conexionBD.php");

// 1. Buscar si hay un turno de caja abierto
$sql_turno = "SELECT t.*, e.nombre, e.apellido 
              FROM turno_caja t 
              INNER JOIN empleados e ON t.id_empleado = e.id_empleado 
              WHERE t.fecha_hora_cierre IS NULL 
              LIMIT 1";
$resultado_turno = mysqli_query($conexion, $sql_turno);
$turno_abierto = mysqli_fetch_assoc($resultado_turno);

$monto_acumulado_pedidos = 0;
$monto_esperado_total = 0;

if ($turno_abierto) {
    $id_turno_actual = $turno_abierto['id_turno'];

    // 2. Sumar todos los pedidos cobrados / finalizados de este turno
    // Se excluyen los pedidos cancelados si los hubiera
    $sql_acumulado = "SELECT COALESCE(SUM(p.monto_total), 0) AS total_ventas 
                      FROM Pedido p 
                      INNER JOIN Estado_Pedido ep ON p.id_estado = ep.id_estado
                      WHERE p.id_turno = ? 
                        AND LOWER(ep.nombre) NOT IN ('cancelado', 'anulado')";
    
    $stmt_acum = mysqli_prepare($conexion, $sql_acumulado);
    if ($stmt_acum) {
        mysqli_stmt_bind_param($stmt_acum, "i", $id_turno_actual);
        mysqli_stmt_execute($stmt_acum);
        $res_acum = mysqli_stmt_get_result($stmt_acum);
        if ($fila_acum = mysqli_fetch_assoc($res_acum)) {
            $monto_acumulado_pedidos = (float)$fila_acum['total_ventas'];
        }
        mysqli_stmt_close($stmt_acum);
    }

    // Cálculo automático: Monto Inicial + Pedidos Acumulados
    $monto_esperado_total = (float)$turno_abierto['monto_inicial'] + $monto_acumulado_pedidos;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Turno de Caja - Lo de Torres</title>
    <link rel="stylesheet" href="includes/style.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
</head>
<body>

    <div class="contenido-principal" style="padding: 0;">
        <div class="barra-superior">
            <h1>
                <a href="dashboard.php" style="color: var(--accent-green); text-decoration: none;"><i class="ri-arrow-left-line"></i></a>
                Lo de Torres - Turno de Caja
            </h1>
            <div class="billetera-usuario" style="color: var(--accent-green);">
                <i class="ri-user-3-fill"></i>
                <span><?= htmlspecialchars($nombre_empleado, ENT_QUOTES, 'UTF-8') ?></span>
                <a href="registro_cajas.php" class="btn-nuevo" style="background: #27722d; margin-left: 15px; text-decoration: none; font-size: 13px;">
                    <i class="ri-history-line"></i> Historial de Cajas
                </a>
            </div>
        </div>
    </div>

    <div class="contenedor-insumos" style="max-width: 600px; margin: 40px auto;">
        
        <?php if (!$turno_abierto): ?>
            <!-- SI LA CAJA ESTÁ CERRADA: Formulario para Abrir -->
            <div style="background: #1e293b; padding: 25px; border-radius: 8px; border: 1px solid #334155;">
                <h2 style="color: var(--accent-green); margin-bottom: 15px; font-size: 22px;">Abrir Turno de Caja</h2>
                <p style="color: var(--color-text-muted); margin-bottom: 20px; font-size: 14px;">No hay ningún turno abierto. Ingrese el monto inicial con el que comienza.</p>
                
                <form action="apis/api_caja.php?accion=abrir" method="POST" style="display: flex; flex-direction: column; gap: 16px;">
                    <div style="display: flex; flex-direction: column; gap: 6px;">
                        <label for="monto_inicial" style="color: var(--color-text-body); font-weight: 500; font-size: 14px;">Monto Inicial ($) *</label>
                        <input type="number" step="0.01" min="0" id="monto_inicial" name="monto_inicial" required
                               style="padding: 10px 15px; background: #0f172a; border: 1px solid #334155; border-radius: 6px; color: #fff; font-size: 16px; outline: none;">
                    </div>
                    <button type="submit" class="btn-nuevo" style="border: none; cursor: pointer; font-size: 15px; padding: 12px; text-align: center;">Abrir Caja</button>
                </form>
            </div>

        <?php else: ?>
            <!-- SI LA CAJA ESTÁ ABIERTA: Resumen Acumulado y Cierre -->
            <div style="background: #1e293b; padding: 25px; border-radius: 8px; border: 1px solid #334155;">
                <h2 style="color: var(--accent-green); margin-bottom: 15px; font-size: 22px;">Turno de Caja Activo 🟢</h2>
                
                <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 25px; font-size: 15px; color: var(--color-text-body);">
                    <div><strong>Turno N°:</strong> #<?= $turno_abierto['id_turno'] ?></div>
                    <div><strong>Abierto por:</strong> <?= htmlspecialchars($turno_abierto['nombre'] . ' ' . $turno_abierto['apellido']) ?></div>
                    <div><strong>Apertura:</strong> <?= htmlspecialchars($turno_abierto['fecha_hora_apertura']) ?></div>
                    
                    <hr style="border: 0; border-top: 1px solid #334155; margin: 10px 0;">

                    <!-- Desglose del cálculo acumulado -->
                    <div style="display: flex; justify-content: space-between;">
                        <span>Monto Inicial:</span>
                        <strong>$<?= number_format($turno_abierto['monto_inicial'], 2, ',', '.') ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span>Ventas Acumuladas del Turno:</span>
                        <strong style="color: var(--accent-green);">$<?= number_format($monto_acumulado_pedidos, 2, ',', '.') ?></strong>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; background: #0f172a; padding: 12px; border-radius: 6px; margin-top: 5px; font-size: 16px; border: 1px solid #334155;">
                        <span style="color: #fff; font-weight: bold;">Monto Mínimo / Esperado en Caja:</span>
                        <strong style="color: var(--accent-green); font-size: 18px;">$<?= number_format($monto_esperado_total, 2, ',', '.') ?></strong>
                    </div>
                </div>

                <hr style="border: 0; border-top: 1px solid #334155; margin-bottom: 20px;">

                <h3 style="color: var(--color-text-main); font-size: 18px; margin-bottom: 15px;">Arqueo y Cierre de Caja</h3>
                <form action="apis/api_caja.php?accion=cerrar" method="POST" style="display: flex; flex-direction: column; gap: 16px;">
                    <input type="hidden" name="id_turno" value="<?= $turno_abierto['id_turno'] ?>">
                    
                    <div style="display: flex; flex-direction: column; gap: 6px;">
                        <label for="monto_final" style="color: var(--color-text-body); font-weight: 500; font-size: 14px;">Dinero Real Contado en Caja ($) *</label>
                        <input type="number" step="0.01" min="0" id="monto_final" name="monto_final" value="<?= $monto_esperado_total ?>" required
                               style="padding: 10px 15px; background: #0f172a; border: 1px solid #334155; border-radius: 6px; color: #fff; font-size: 18px; font-weight: bold; outline: none;">
                        <small style="color: var(--color-text-muted); font-size: 12px;">El valor por defecto es el acumulado automático. Modifíquelo si hay diferencias al contar en efectivo.</small>
                    </div>

                    <button type="submit" style="background: var(--accent-red); color: white; border: none; cursor: pointer; font-size: 15px; padding: 12px; border-radius: 6px; font-weight: 500; text-align: center;">Cerrar Turno de Caja</button>
                </form>
            </div>
        <?php endif; ?>

    </div>

</body>
</html>