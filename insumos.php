<?php
include_once("includes/funciones.php");
verificar_sesion();

$nombre_empleado =$_SESSION['empleado_nombre'] ?? 'Empleado';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Insumos</title>
    <link rel="stylesheet" href="includes/style.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
</head>
<body>

    <div class="contenido-principal" style="padding: 0;">
        <div class="barra-superior">
            <h1>
                <a href="dashboard.php" style="color: var(--accent-green); text-decoration: none;"><i class="ri-arrow-left-line"></i></a>
                Lo de Torres - Insumos
            </h1>
            <div class="billetera-usuario" style="color: var(--accent-green);">
                <i class="ri-user-3-fill"></i>
                <span><?= htmlspecialchars($nombre_empleado, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </div>
    </div>

    <div class="contenedor-insumos">
        <h2 style="color: var(--color-text-main); margin-bottom: 20px; font-size: 24px;">Listado de Insumos</h2>

        <div class="controles-insumos">
            <input type="text" id="inputFiltro" placeholder="Ingrese Insumo a buscar..." onkeyup="buscarInsumos()">
            <a href="altas/alta_insumo.php" class="btn-nuevo"><i class="ri-add-line"></i> Nuevo Insumo</a>
        </div>

        <table class="tabla-datos">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Unidad de Medida</th>
                    <th>Cant. Actual</th>
                    <th>Stock Mínimo</th>
                    <th>Costo</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tablaInsumos">
                <tr>
                    <td colspan="7" style="text-align: center; color: var(--color-text-muted);">No se encontraron insumos.</td>
                </tr>
            </tbody>
        </table>

        <div class="paginacion">
            <button id="btnAnterior" onclick="cambiarPagina(-1)">Anterior</button>
            <span id="infoPagina">Página 1</span>
            <button id="btnSiguiente" onclick="cambiarPagina(1)">Siguiente</button>
        </div>
    </div>
    
    <script>
        let paginaActual = 1;
        const limitePorPagina = 15;
        let timeoutBusqueda = null;

        document.addEventListener("DOMContentLoaded", () => {
            cargarInsumos();
        });

        function cargarInsumos() {
            const filtro = document.getElementById("inputFiltro").value.trim();
            const tbody = document.getElementById("tablaInsumos");

            fetch(`apis/api_insumos.php?pagina=${paginaActual}&limite=${limitePorPagina}&filtro=${encodeURIComponent(filtro)}`)
                .then(response => response.json())
                .then(insumos => {
                    tbody.innerHTML = "";

                    if (insumos.length === 0) {
                        tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; color: #94a3b8;">No se encontraron insumos.</td></tr>`;
                        document.getElementById("btnSiguiente").disabled = true;
                        return;
                    }

                    insumos.forEach(insumo => {
                        const tr = document.createElement("tr");

                        tr.innerHTML = `
                            <td>${escapeHTML(insumo.id_insumo)}</td>
                            <td>${escapeHTML(insumo.nombre)}</td>
                            <td>${escapeHTML(insumo.unidad)}</td>
                            <td>${escapeHTML(insumo.cantidad_actual)}</td>
                            <td>${escapeHTML(insumo.stock_minimo)}</td>
                            <td>$ ${escapeHTML(insumo.costo)}</td>
                            <td>
                        <button onclick="eliminarInsumo(${insumo.id_insumo})" style="background: rgba(255, 74, 90, 0.1); color: var(--accent-red); border: 1px solid var(--accent-red); padding: 6px 10px; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 13px; display: inline-flex; align-items: center; gap: 4px;">
                            <i class="ri-delete-bin-line"></i> Eliminar
                        </button>
                            </td>
                        `;
                        tbody.appendChild(tr);
                    });

                    document.getElementById("btnAnterior").disabled = (paginaActual === 1);
                    document.getElementById("btnSiguiente").disabled = (insumos.length < limitePorPagina);
                    document.getElementById("infoPagina").textContent = `Página ${paginaActual}`;
                })
                .catch(error => {
                    console.error("Error al cargar los insumos:", error);
                });
        }

        function cambiarPagina(delta) {
            paginaActual += delta;
            cargarInsumos();
        }

        function buscarInsumos() {
            clearTimeout(timeoutBusqueda);
            timeoutBusqueda = setTimeout(() => {
                paginaActual = 1;
                cargarInsumos();
            }, 300);
        }

        function eliminarInsumo(id) {
            if (confirm("¿Está seguro de que desea eliminar este insumo?")) {
                fetch(`apis/eliminar.php?tipo=insumo&id=${id}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error("No se pudo desactivar el insumo.");
                        }
                        return response.json();
                    })
                    .then(() => {
                        cargarInsumos();
                    })
                    .catch(error => {
                        console.error("Error al desactivar el insumo:", error);
                        alert("No se pudo desactivar el insumo.");
                    });
            }
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