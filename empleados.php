<?php
include_once("includes/conexionBD.php");
include_once("includes/funciones.php");
verificar_sesion();

$nombre_empleado =$_SESSION['empleado_nombre'] ?? 'Empleado';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Empleados</title>
    <link rel="stylesheet" href="includes/style.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
</head>
<body>

    <div class="contenido-principal" style="padding: 0;">
        <div class="barra-superior">
            <h1>
                <a href="dashboard.php" style="color: var(--accent-green); text-decoration: none;"><i class="ri-arrow-left-line"></i></a>
                Lo de Torres - Empleados
            </h1>
            <div class="billetera-usuario" style="color: var(--accent-green);">
                <i class="ri-user-3-fill"></i>
                <span><?= htmlspecialchars($nombre_empleado, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </div>
    </div>

    <div class="contenedor-insumos">
        <h2 style="color: var(--color-text-main); margin-bottom: 20px; font-size: 24px;">Listado de Empleados</h2>

        <div class="controles-insumos">
            <input type="text" id="inputFiltro" placeholder="Ingrese empleado a buscar..." onkeyup="buscarEmpleados()">
            <a href="altas/alta_empleado.php" class="btn-nuevo"><i class="ri-add-line"></i> Nuevo Empleado</a>
        </div>

        <table class="tabla-datos">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Apellido</th>
                    <th>Correo</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tablaEmpleados">
                <tr>
                    <td colspan="7" style="text-align: center; color: var(--color-text-muted);">Cargando empleados...</td>
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
            cargarEmpleados();
        });

        function cargarEmpleados() {
            const filtro = document.getElementById("inputFiltro").value.trim();
            const tbody = document.getElementById("tablaEmpleados");

            fetch(`apis/api_empleados.php?pagina=${paginaActual}&limite=${limitePorPagina}&filtro=${encodeURIComponent(filtro)}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error("HTTP error " + response.status);
                    }
                    return response.json();
                })
                .then(empleados => {
                    tbody.innerHTML = "";

                    if (!Array.isArray(empleados) || empleados.length === 0) {
                        tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; color: var(--color-text-muted);">No se encontraron empleados.</td></tr>`;
                        document.getElementById("btnSiguiente").disabled = true;
                        return;
                    }

                    empleados.forEach(empleado => {
                        const tr = document.createElement("tr");

                        const esActivo = Number(empleado.activo) === 1 || empleado.activo === true;
                        const estadoTexto = esActivo ? 'Activo' : 'Inactivo';
                        const estadoColor = esActivo ? 'var(--accent-green)' : 'var(--accent-red)';

                        tr.innerHTML = `
                            <td>${escapeHTML(empleado.id_empleado)}</td>
                            <td>${escapeHTML(empleado.nombre)}</td>
                            <td>${escapeHTML(empleado.apellido)}</td>
                            <td>${escapeHTML(empleado.correo)}</td>
                            <td>${escapeHTML(empleado.rol || empleado.id_rol)}</td>
                            <td><span style="color: ${estadoColor}; font-weight: 500;">${estadoTexto}</span></td>
                           <td>

    <button onclick="eliminarEmpleado(${empleado.id_empleado})" style="background: rgba(255, 74, 90, 0.1); color: var(--accent-red); border: 1px solid var(--accent-red); padding: 6px 10px; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 13px; display: inline-flex; align-items: center; gap: 4px;">
        <i class="ri-delete-bin-line"></i> Eliminar
    </button>
</td>
                        `;
                        tbody.appendChild(tr);
                    });

                    document.getElementById("btnAnterior").disabled = (paginaActual === 1);
                    document.getElementById("btnSiguiente").disabled = (empleados.length < limitePorPagina);
                    document.getElementById("infoPagina").textContent = `Página ${paginaActual}`;
                })
                .catch(error => {
                    console.error("Error al cargar los empleados:", error);
                    tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; color: var(--accent-red);">Error al obtener datos de la API.</td></tr>`;
                });
        }

        function cambiarPagina(delta) {
            paginaActual += delta;
            cargarEmpleados();
        }

        function buscarEmpleados() {
            clearTimeout(timeoutBusqueda);
            timeoutBusqueda = setTimeout(() => {
                paginaActual = 1;
                cargarEmpleados();
            }, 300);
        }

        function eliminarEmpleado(id) {
            if (confirm("¿Está seguro de que desea desactivar o eliminar este empleado?")) {
                fetch(`apis/eliminar.php?tipo=empleado&id=${id}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error("No se pudo desactivar el empleado.");
                        }
                        return response.json();
                    })
                    .then(() => {
                        cargarEmpleados();
                    })
                    .catch(error => {
                        console.error("Error al desactivar el empleado:", error);
                        alert("No se pudo desactivar el empleado.");
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