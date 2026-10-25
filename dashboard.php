<?php
session_start();
include_once("includes/funciones.php");
include_once("includes/conexionBD.php");
verificar_sesion();

$nombre_empleado = $_SESSION['empleado_nombre'] ?? 'Empleado';
$id_rol = $_SESSION['empleado_rol'] ?? 0;

// Definir si es rol gerencial/admin o empleado operativo (ajusta los IDs según tu base de datos)
// Por ejemplo, asumimos que rol 1 o 2 (o administrador/encargado) ven las alertas de stock
$esAdmin = ($id_rol == 1 || $id_rol == 2); 

// Consultar pedidos listos para entregar
$sql_pedidos = "SELECT id_pedido, mesa, cliente FROM pedidos WHERE LOWER(estado) = 'listo' LIMIT 5";
$res_pedidos = mysqli_query($conexion, $sql_pedidos);

// Consultar insumos críticos (stock bajo) si es admin/encargado
$insumos_criticos = [];
if ($esAdmin) {
    $sql_insumos = "SELECT nombre, stock_actual, stock_minimo FROM insumos WHERE stock_actual <= stock_minimo LIMIT 5";
    $res_insumos = mysqli_query($conexion, $sql_insumos);
    if ($res_insumos) {
        while ($row = mysqli_fetch_assoc($res_insumos)) {
            $insumos_criticos[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Lo de Torres</title>
    <link rel="stylesheet" href="includes/style.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <script src="script.js" defer></script>
</head>
<body>

    <div class="barra-superior">
        <h1>Lo de Torres</h1>
        <div class="billetera-usuario">
            <i class="ri-user-3-fill"></i>
            <span><?= htmlspecialchars($nombre_empleado, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    </div>

    <div class="app-layout" style="display: block; padding: 15px; max-width: 800px; margin: 0 auto; box-sizing: border-box;">
        
        <!-- SECCIÓN DE ALERTAS RÁPIDAS (DASHBOARD INTELIGENTE) -->
        <div style="margin-bottom: 25px;">
            <h3 style="color: var(--color-text-main); font-size: 18px; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                <i class="ri-notification-3-line" style="color: var(--accent-cyan);"></i> Alertas y Pendientes
            </h3>

            <div style="display: grid; grid-template-columns: 1fr; gap: 15px;">
                
                <!-- Alerta de Pedidos Listos -->
                <div style="background: var(--bg-card); padding: 15px; border-radius: 8px; border: 1px solid var(--border-subtle);">
                    <h4 style="color: var(--accent-green); font-size: 15px; margin-bottom: 10px; display: flex; align-items: center; gap: 6px;">
                        <i class="ri-checkbox-circle-line"></i> Pedidos Listos para Entregar
                    </h4>
                    <?php if ($res_pedidos && mysqli_num_rows($res_pedidos) > 0): ?>
                        <ul style="list-style: none; padding: 0; margin: 0;">
                            <?php while ($p = mysqli_fetch_assoc($res_pedidos)): ?>
                                <li style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid var(--border-subtle); font-size: 14px;">
                                    <span>Mesa: <strong><?= htmlspecialchars($p['mesa']) ?></strong> (<?= htmlspecialchars($p['cliente']) ?>)</span>
                                    <a href="pedidos.php" style="background: var(--accent-green); color: #000; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: bold; text-decoration: none;">Ver</a>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <p style="color: var(--color-text-muted); font-size: 13px; margin: 0;">No hay pedidos listos en este momento.</p>
                    <?php endif; ?>
                </div>

                <!-- Alerta de Insumos (Solo para Admins/Encargados) -->
                <?php if ($esAdmin): ?>
                <div style="background: var(--bg-card); padding: 15px; border-radius: 8px; border: 1px solid var(--border-subtle);">
                    <h4 style="color: var(--accent-red); font-size: 15px; margin-bottom: 10px; display: flex; align-items: center; gap: 6px;">
                        <i class="ri-alert-line"></i> Insumos con Stock Bajo
                    </h4>
                    <?php if (!empty($insumos_criticos)): ?>
                        <ul style="list-style: none; padding: 0; margin: 0;">
                            <?php foreach ($insumos_criticos as $insumo): ?>
                                <li style="display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid var(--border-subtle); font-size: 14px;">
                                    <span><?= htmlspecialchars($insumo['nombre']) ?> (Actual: <strong><?= $insumo['stock_actual'] ?></strong>)</span>
                                    <a href="insumos.php" style="background: var(--accent-red); color: #fff; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: bold; text-decoration: none;">Reponer</a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p style="color: var(--color-text-muted); font-size: 13px; margin: 0;">Todos los insumos tienen stock suficiente.</p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

            </div>
        </div>

        <!-- MENÚ DE NAVEGACIÓN RÁPIDA -->
        <h3 style="color: var(--color-text-main); font-size: 18px; margin-bottom: 12px;">Módulos del Sistema</h3>
        <ul id="menu-lateral" class="menu-lateral" style="position: static; width: 100%; box-shadow: none; background: transparent; padding: 0; display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 10px;">
            <li>
                <a href="pedidos.php" class="boton-menu" style="padding: 14px; font-size: 15px; display: flex; flex-direction: column; align-items: center; text-align: center; gap: 6px; height: 100%; box-sizing: border-box;">
                    <i class="ri-store-2-line" style="font-size: 24px;"></i> Pedidos
                </a>
            </li>
            <li>
                <a href="cocina.php" class="boton-menu" style="padding: 14px; font-size: 15px; display: flex; flex-direction: column; align-items: center; text-align: center; gap: 6px; height: 100%; box-sizing: border-box;">
                    <i class="ri-restaurant-2-line" style="font-size: 24px;"></i> Cocina
                </a>
            </li>
            <li>
                <a href="cobrar.php" class="boton-menu" style="padding: 14px; font-size: 15px; display: flex; flex-direction: column; align-items: center; text-align: center; gap: 6px; height: 100%; box-sizing: border-box;">
                    <i class="ri-hand-coin-line" style="font-size: 24px;"></i> Cobros
                </a>
            </li>
            <li>
                <a href="caja.php" class="boton-menu" style="padding: 14px; font-size: 15px; display: flex; flex-direction: column; align-items: center; text-align: center; gap: 6px; height: 100%; box-sizing: border-box;">
                    <i class="ri-wallet-3-line" style="font-size: 24px;"></i> Turno de Caja
                </a>
            </li>
            <li>
                <a href="mapa.php" class="boton-menu" style="padding: 14px; font-size: 15px; display: flex; flex-direction: column; align-items: center; text-align: center; gap: 6px; height: 100%; box-sizing: border-box;">
                    <i class="ri-map-pin-line" style="font-size: 24px;"></i> Mapa
                </a>
            </li>
            <?php if ($esAdmin): ?>
            <li>
                <a href="empleados.php" class="boton-menu" style="padding: 14px; font-size: 15px; display: flex; flex-direction: column; align-items: center; text-align: center; gap: 6px; height: 100%; box-sizing: border-box;">
                    <i class="ri-team-line" style="font-size: 24px;"></i> Empleados
                </a>
            </li>
            <?php endif; ?>
            <li>
                <a href="productos.php" class="boton-menu" style="padding: 14px; font-size: 15px; display: flex; flex-direction: column; align-items: center; text-align: center; gap: 6px; height: 100%; box-sizing: border-box;">
                    <i class="ri-shopping-bag-3-line" style="font-size: 24px;"></i> Productos
                </a>
            </li>
            <li>
                <a href="insumos.php" class="boton-menu" style="padding: 14px; font-size: 15px; display: flex; flex-direction: column; align-items: center; text-align: center; gap: 6px; height: 100%; box-sizing: border-box;">
                    <i class="ri-box-3-line" style="font-size: 24px;"></i> Insumos
                </a>
            </li>
            <li>
                <a href="iniciar-sesion.php" class="boton-menu" style="color: var(--accent-red); padding: 14px; font-size: 15px; display: flex; flex-direction: column; align-items: center; text-align: center; gap: 6px; height: 100%; box-sizing: border-box;">
                    <i class="ri-logout-box-line" style="font-size: 24px;"></i> Salir
                </a>
            </li>
        </ul>

    </div>

</body>
</html> 