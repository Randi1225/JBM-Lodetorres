<?php
include_once("includes/funciones.php");
verificar_sesion();

$nombre_empleado =$_SESSION['empleado_nombre'] ?? 'Empleado';
include_once("includes/conexionBD.php");

// Consultar si hay una caja abierta
$res_turno = mysqli_query($conexion, "SELECT id_turno FROM turno_caja WHERE fecha_hora_cierre IS NULL LIMIT 1");
$turno_activo = mysqli_fetch_assoc($res_turno);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cobros / POS - Lo de Torres</title>
    <link rel="stylesheet" href="includes/style.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
</head>
<body>

    <div class="contenido-principal" style="padding: 0;">
        <div class="barra-superior">
            <h1>
                <a href="dashboard.php" style="color: var(--accent-green); text-decoration: none;"><i class="ri-arrow-left-line"></i></a>
                Lo de Torres - Cobros / POS
            </h1>
            <div class="billetera-usuario" style="color: var(--accent-green);">
                <i class="ri-user-3-fill"></i>
                <span><?= htmlspecialchars($nombre_empleado, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </div>
    </div>

    <div class="contenedor-insumos">
        <?php if (!$turno_activo): ?>
            <div style="background: rgba(255, 74, 90, 0.15); border: 1px solid var(--accent-red); color: var(--accent-red); padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                ⚠️ <strong>Caja Cerrada:</strong> No hay ningún turno abierto. Debe ir a <a href="caja.php" style="color: #fff; text-decoration: underline;">Turno de Caja</a> para abrir un turno antes de cobrar.
            </div>
        <?php else: ?>
            <h2 style="color: var(--color-text-main); margin-bottom: 20px; font-size: 24px;">Pedidos Pendientes de Cobro</h2>

            <table class="tabla-datos">
                <thead>
                    <tr>
                        <th>Pedido #</th>
                        <th>Mesa</th>
                        <th>Cliente</th>
                        <th>Estado</th>
                        <th>Total</th>
                        <th>Método Pago</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody id="tablaCobros">
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--color-text-muted);">Cargando pedidos...</td>
                    </tr>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            if (<?= $turno_activo ? 'true' : 'false' ?>) {
                cargarPedidosSinCobrar();
            }
        });

        function cargarPedidosSinCobrar() {
            fetch('apis/api_pedidos.php?filtro=sin_cobrar')
                .then(res => res.json())
                .then(pedidos => {
                    const tbody = document.getElementById("tablaCobros");
                    tbody.innerHTML = "";

                    if (!Array.isArray(pedidos) || pedidos.length === 0) {
                        tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; color: var(--color-text-muted);">No hay pedidos pendientes de cobro.</td></tr>`;
                        return;
                    }

                    pedidos.forEach(p => {
                        const tr = document.createElement("tr");
                        tr.innerHTML = `
                            <td>#${escapeHTML(p.id_pedido)}</td>
                            <td>${escapeHTML(p.mesa || 'Sin mesa')}</td>
                            <td>${escapeHTML(p.cliente || 'Consumidor Final')}</td>
                            <td><span style="color: #f59e0b; font-weight: 500;">${escapeHTML(p.estado)}</span></td>
                            <td style="color: var(--accent-green); font-weight: bold;">$${Number(p.monto_total).toFixed(2)}</td>
                            <td>${escapeHTML(p.metodo_pago || 'Efectivo')}</td>
                            <td>
                                <button type="button" class="btn-nuevo btn-cobrar" data-id="${p.id_pedido}" style="padding: 6px 12px; font-size: 13px; border: none; cursor: pointer;">
                                    <i class="ri-money-dollar-circle-line"></i> Cobrar
                                </button>
                            </td>
                        `;
                        tbody.appendChild(tr);
                    });

                    // Asignar eventos de clic de forma dinámica a todos los botones generados
                    document.querySelectorAll('.btn-cobrar').forEach(boton => {
                        boton.addEventListener('click', function() {
                            const idPedido = this.getAttribute('data-id');
                            procesarCobro(idPedido);
                        });
                    });
                })
                .catch(err => {
                    console.error("Error al cargar pedidos sin cobrar:", err);
                });
        }

        function procesarCobro(idPedido) {
            if (!confirm(`¿Confirmar cobro del pedido #${idPedido}?`)) return;

            fetch('apis/api_cobrar.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'id_pedido=' + encodeURIComponent(idPedido)
            })
            .then(response => response.text())
            .then(texto => {
                console.log("Respuesta de la API:", texto);
                try {
                    const res = JSON.parse(texto);
                    if (res.success) {
                        alert("✅ ¡Pedido cobrado exitosamente!");
                        cargarPedidosSinCobrar();
                    } else {
                        alert("⚠️ Error del servidor: " + (res.error || "Desconocido"));
                    }
                } catch (e) {
                    alert("❌ La API devolvió HTML o un error de PHP en lugar de JSON. Revisa la consola.");
                    console.error("Texto recibido:", texto);
                }
            })
            .catch(error => {
                console.error("Error de red:", error);
                alert("Error de conexión al intentar cobrar.");
            });
        }

        function escapeHTML(str) {
            if (str === null || str === undefined) return '';
            return String(str)
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }
    </script>
</body>
</html>