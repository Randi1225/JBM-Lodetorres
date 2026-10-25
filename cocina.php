<?php
include_once("includes/funciones.php");
verificar_sesion();

$nombre_empleado =$_SESSION['empleado_nombre'] ?? 'Cocinero';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Cocina - Comandas</title>
    <link rel="stylesheet" href="includes/style.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
</head>
<body>

    <div class="contenido-principal" style="padding: 0;">
        <div class="barra-superior">
            <h1>
                <a href="dashboard.php" style="color: var(--accent-green); text-decoration: none;"><i class="ri-arrow-left-line"></i></a>
                Lo de Torres - Cocina 
            </h1>
            <div class="billetera-usuario" style="color: var(--accent-green);">
                <i class="ri-restaurant-fill"></i>
                <span><?= htmlspecialchars($nombre_empleado, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </div>
    </div>

    <div class="contenedor-insumos" style="max-width: 100%; padding: 20px;">
        <h2 style="color: var(--color-text-main); margin-bottom: 20px; font-size: 24px;">Pedidos Activos en Cocina</h2>

        <div id="contenedorComandas" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
            <div style="background: #1e293b; padding: 20px; border-radius: 8px; border: 1px solid #334155; text-align: center; color: var(--color-text-muted); grid-column: 1 / -1;">
                Cargando comandas...
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            cargarComandas();
            setInterval(cargarComandas, 10000); // Actualiza cada 10 segundos
        });

        function cargarComandas() {
            // Se traen tanto los pedidos 'Pendiente' como 'En preparación'
            fetch('apis/api_pedidos.php?filtro=cocina')
                .then(response => response.json())
                .then(pedidos => {
                    const contenedor = document.getElementById("contenedorComandas");
                    contenedor.innerHTML = "";

                    // Filtrar pedidos relevantes para cocina si la API devuelve todos
                    const pedidosCocina = Array.isArray(pedidos) ? pedidos.filter(p => {
                        const est = (p.estado || '').toLowerCase();
                        return est === 'pendiente' || est === 'en preparación' || est === 'en preparacion';
                    }) : [];

                    if (pedidosCocina.length === 0) {
                        contenedor.innerHTML = `<div style="background: #1e293b; padding: 20px; border-radius: 8px; border: 1px solid #334155; text-align: center; color: var(--color-text-muted); grid-column: 1 / -1;">No hay pedidos activos en cocina en este momento.</div>`;
                        return;
                    }

                    pedidosCocina.forEach(pedido => {
                        let listaProductos = "";
                        if (pedido.productos && pedido.productos.length > 0) {
                            pedido.productos.forEach(p => {
                                listaProductos += `<li style="margin-bottom: 5px;"><strong>${p.cantidad}x</strong> ${escapeHTML(p.nombre)}</li>`;
                            });
                        }

                        const estadoNormalizado = (pedido.estado || '').toLowerCase();
                        let botonAccion = '';
                        let colorEstado = '#f59e0b'; // Naranja para Pendiente

                        if (estadoNormalizado === 'pendiente') {
                            botonAccion = `
                                <button onclick="cambiarEstadoPedido(${pedido.id_pedido}, 'En preparación')" style="background: #3b82f6; color: #fff; border: none; padding: 10px; border-radius: 6px; font-weight: bold; cursor: pointer; margin-top: 5px;">
                                    Comenzar Preparación 👨‍🍳
                                </button>`;
                        } else if (estadoNormalizado === 'en preparación' || estadoNormalizado === 'en preparacion') {
                            colorEstado = '#3b82f6'; // Azul para En Preparación
                            botonAccion = `
                                <button onclick="cambiarEstadoPedido(${pedido.id_pedido}, 'Listo')" style="background: var(--accent-green); color: #000; border: none; padding: 10px; border-radius: 6px; font-weight: bold; cursor: pointer; margin-top: 5px;">
                                    Marcar como Listo 🍲
                                </button>`;
                        }

                        const card = document.createElement("div");
                        card.style.cssText = "background: #1e293b; padding: 20px; border-radius: 8px; border: 1px solid #334155; display: flex; flex-direction: column; gap: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);";
                        
                        card.innerHTML = `
                            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #334155; padding-bottom: 10px;">
                                <span style="font-size: 18px; font-weight: bold; color: var(--accent-green);">Pedido #${escapeHTML(pedido.id_pedido)}</span>
                                <span style="background: #334155; padding: 4px 8px; border-radius: 4px; font-size: 12px; color: ${colorEstado}; font-weight: bold;">
                                    ${escapeHTML(pedido.estado)}
                                </span>
                            </div>
                            <div style="font-size: 14px; color: var(--color-text-body);">
                                <p style="margin: 0 0 8px 0;"><strong>Mesa:</strong> ${escapeHTML(pedido.mesa || 'N/A')}</p>
                                <p style="margin: 0 0 8px 0;"><strong>Hora:</strong> ${escapeHTML(pedido.fecha_hora_creacion)}</p>
                                <p style="margin: 0 0 8px 0;"><strong>Observaciones:</strong> ${escapeHTML(pedido.descripcion || 'Ninguna')}</p>
                            </div>
                            <div style="background: #0f172a; padding: 10px; border-radius: 6px;">
                                <p style="margin: 0 0 6px 0; font-size: 12px; color: var(--color-text-muted); font-weight: bold;">PRODUCTOS:</p>
                                <ul style="margin: 0; padding-left: 18px; color: #fff; font-size: 14px;">
                                    ${listaProductos}
                                </ul>
                            </div>
                            ${botonAccion}
                        `;
                        contenedor.appendChild(card);
                    });
                })
                .catch(error => {
                    console.error("Error al cargar comandas:", error);
                });
        }

        function cambiarEstadoPedido(idPedido, nuevoEstado) {
            fetch('apis/api_cambiar_estado_pedido.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id_pedido=${idPedido}&estado=${encodeURIComponent(nuevoEstado)}`
            })
            .then(response => response.json())
            .then(res => {
                if (res.success) {
                    cargarComandas();
                } else {
                    alert(res.error || "No se pudo actualizar el estado del pedido.");
                }
            })
            .catch(error => {
                console.error("Error al actualizar estado:", error);
                alert("Error de conexión al actualizar el pedido.");
            });
        }

        function escapeHTML(str) {
            if (str === null || str === undefined) return '';
            return String(str).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
        }
    </script>
</body>
</html>