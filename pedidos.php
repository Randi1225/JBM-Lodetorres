<?php
include_once("includes/funciones.php");
verificar_sesion();

$nombre_empleado = $_SESSION['empleado_nombre'] ?? 'Empleado';$esAdmin = tieneRol(['administrador', 'admin', 'encargado']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Pedidos - Lo de Torres</title>
    <link rel="stylesheet" href="includes/style.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        /* Ajustes responsivos específicos para móviles */
        @media screen and (max-width: 768px) {
            .contenedor-insumos {
                padding: 10px !important;
            }
            .controles-insumos {
                flex-direction: column !important;
                gap: 10px !important;
            }
            .controles-insumos input, 
            .controles-insumos .btn-nuevo {
                width: 100% !important;
                box-sizing: border-box;
                text-align: center;
            }
            .tabla-datos {
                display: block;
                width: 100%;
                overflow-x: auto;
                white-space: nowrap;
            }
        }
    </style>
</head>
<body>

    <div class="contenido-principal" style="padding: 0;">
        <div class="barra-superior">
            <h1 style="font-size: 1.1rem; display: flex; align-items: center; gap: 8px;">
                <a href="dashboard.php" style="color: var(--accent-green); text-decoration: none;"><i class="ri-arrow-left-line" style="font-size: 1.3rem;"></i></a>
                Pedidos
            </h1>
            <div class="billetera-usuario" style="color: var(--accent-green); font-size: 0.9rem;">
                <i class="ri-user-3-fill"></i>
                <span><?= htmlspecialchars($nombre_empleado, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </div>
    </div>

    <div class="contenedor-insumos" style="padding: 20px; box-sizing: border-box;">
        <h2 style="color: var(--color-text-main); margin-bottom: 15px; font-size: 20px;">Listado de Pedidos</h2>

        <div class="controles-insumos" style="display: flex; gap: 10px; margin-bottom: 15px;">
            <input type="text" id="inputFiltro" placeholder="Buscar por mesa, estado o cliente..." onkeyup="buscarPedidos()" style="flex: 1; padding: 12px; font-size: 16px; background: var(--bg-input); border: 1px solid var(--border-subtle); color: #fff; border-radius: 6px; outline: none;">
            <a href="altas/alta_pedido.php" class="btn-nuevo" style="padding: 12px 18px; font-size: 15px; display: inline-flex; align-items: center; justify-content: center; gap: 6px; text-decoration: none; border-radius: 6px;"><i class="ri-add-line"></i> Nuevo Pedido</a>
        </div>

        <div style="overflow-x: auto; background: var(--bg-card); border-radius: 8px; border: 1px solid var(--border-subtle);">
            <table class="tabla-datos" style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border-subtle);">
                        <th style="padding: 12px;">Mesa</th>
                        <th style="padding: 12px;">Cliente</th>
                        <th style="padding: 12px;">Estado</th>
                        <th style="padding: 12px;">Total</th>
                        <th style="padding: 12px;">Fecha Creación</th>
                        <th style="padding: 12px;">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tablaPedidos">
                    <!-- Dinámico -->
                </tbody>
            </table>
        </div>
    </div>

    <script>
        let paginaActual = 1;
        const limitePorPagina = 15;
        let timeoutBusqueda = null;

        document.addEventListener("DOMContentLoaded", () => {
            cargarPedidos();
            setInterval(cargarPedidos, 10000); 
        });

        function cargarPedidos() {
            const filtro = document.getElementById("inputFiltro").value.trim();
            const tbody = document.getElementById("tablaPedidos");

            fetch(`apis/api_pedidos.php?pagina=${paginaActual}&limite=${limitePorPagina}&filtro=${encodeURIComponent(filtro)}`)
                .then(response => response.json())
                .then(pedidos => {
                    tbody.innerHTML = "";

                    if (!Array.isArray(pedidos) || pedidos.length === 0) {
                        tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding: 20px; color: var(--color-text-muted);">No se encontraron pedidos.</td></tr>`;
                        return;
                    }

                    pedidos.forEach(pedido => {
                        const tr = document.createElement("tr");
                        const estadoNormalizado = (pedido.estado || '').toLowerCase();

                        let botonEntregar = '';
                        if (estadoNormalizado === 'listo') {
                            botonEntregar = `
                                <button onclick="marcarEntregado(${pedido.id_pedido})" 
                                        style="background: var(--accent-green); color: #000; border: none; padding: 8px 12px; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 13px; margin-right: 5px;" 
                                        title="Marcar como Entregado">
                                    <i class="ri-checkbox-circle-line"></i> Entregar
                                </button>`;
                        }

                        let botonEliminar = '';
                        if (estadoNormalizado === 'pendiente') {
                           botonEliminar = `<button onclick="eliminarPedido(${pedido.id_pedido})" style="background: rgba(255, 74, 90, 0.1); color: var(--accent-red); border: 1px solid var(--accent-red); padding: 6px 10px; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 13px; display: inline-flex; align-items: center; gap: 4px;">
    <i class="ri-delete-bin-line"></i> Eliminar
</button>`;
                        } else {
                            botonEliminar = `<span style="color: var(--color-text-muted); font-size: 12px; font-style: italic;">No editable</span>`;
                        }

                        let badgeColor = 'var(--color-text-body)';
                        if (estadoNormalizado === 'pendiente') badgeColor = '#f59e0b';
                        else if (estadoNormalizado === 'en preparación' || estadoNormalizado === 'en preparacion') badgeColor = '#3b82f6';
                        else if (estadoNormalizado === 'listo') badgeColor = 'var(--accent-green)';
                        else if (estadoNormalizado === 'cobrado') badgeColor = '#10b981';

                        tr.innerHTML = `
                            <td style="padding: 12px; border-bottom: 1px solid var(--border-subtle);">${escapeHTML(pedido.mesa || 'Sin mesa')}</td>
                            <td style="padding: 12px; border-bottom: 1px solid var(--border-subtle);">${escapeHTML(pedido.cliente || 'Consumidor Final')}</td>
                            <td style="padding: 12px; border-bottom: 1px solid var(--border-subtle);"><span style="color: ${badgeColor}; font-weight: 500;">${escapeHTML(pedido.estado)}</span></td>
                            <td style="padding: 12px; border-bottom: 1px solid var(--border-subtle);">$ ${escapeHTML(pedido.monto_total)}</td>
                            <td style="padding: 12px; border-bottom: 1px solid var(--border-subtle);">${escapeHTML(pedido.fecha_hora_creacion)}</td>
                            <td style="padding: 12px; border-bottom: 1px solid var(--border-subtle);">
                                ${botonEntregar}
                                ${botonEliminar}
                            </td>
                        `;
                        tbody.appendChild(tr);
                    });
                })
                .catch(error => console.error("Error al cargar los pedidos:", error));
        }

        function cambiarEstadoPedido(idPedido, nuevoEstado) {
            fetch('apis/api_cambiar_estado_pedido.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id_pedido=${idPedido}&estado=${encodeURIComponent(nuevoEstado)}`
            })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    cargarPedidos();
                } else {
                    alert(res.error || "No se pudo actualizar el estado del pedido.");
                }
            });
        }

        function marcarEntregado(id) {
            if (confirm(`¿Marcar el pedido #${id} como 'Entregado'?`)) {
                cambiarEstadoPedido(id, 'Entregado');
            }
        }

        function eliminarPedido(id) {
            if (confirm(`¿Está seguro de que desea eliminar el pedido #${id}? Esta acción no se puede deshacer.`)) {
                fetch(`apis/eliminar.php?tipo=pedido&id=${id}`)
                    .then(response => response.json())
                    .then(res => {
                        if (res.success) {
                            cargarPedidos();
                        } else {
                            alert(res.error || "No se pudo eliminar el pedido.");
                        }
                    })
                    .catch(error => {
                        console.error("Error al eliminar el pedido:", error);
                        alert("Error de conexión al eliminar el pedido.");
                    });
            }
        }

        function buscarPedidos() {
            clearTimeout(timeoutBusqueda);
            timeoutBusqueda = setTimeout(() => {
                paginaActual = 1;
                cargarPedidos();
            }, 300);
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