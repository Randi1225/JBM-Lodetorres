<?php
include_once("includes/funciones.php");
include_once("includes/conexionBD.php");
verificar_sesion();

$nombre_empleado =$_SESSION['empleado_nombre'] ?? 'Empleado';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de Cajas - Lo de Torres</title>
    <link rel="stylesheet" href="includes/style.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
</head>
<body>

    <div class="contenido-principal" style="padding: 0;">
        <div class="barra-superior">
            <h1>
                <a href="caja.php" style="color: var(--accent-green); text-decoration: none;"><i class="ri-arrow-left-line"></i></a>
                Lo de Torres - Historial de Cajas
            </h1>
            <div class="billetera-usuario" style="color: var(--accent-green);">
                <i class="ri-user-3-fill"></i>
                <span><?= htmlspecialchars($nombre_empleado, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </div>
    </div>

    <div class="contenedor-insumos">
        <h2 style="color: var(--color-text-main); margin-bottom: 20px; font-size: 24px;">Historial de Turnos de Caja Cerrados</h2>

        <div class="controles-insumos">
            <input type="date" id="filtroFecha" onchange="cargarHistorial()" style="padding: 8px 12px; background: #0f172a; border: 1px solid #334155; border-radius: 6px; color: #fff;">
            <button onclick="limpiarFiltro()" class="btn-nuevo" style="background: #334155; border: none; cursor: pointer;">Mostrar Todos</button>
        </div>

        <table class="tabla-datos">
            <thead>
                <tr>
                    <th>ID Turno</th>
                    <th>Empleado</th>
                    <th>Apertura</th>
                    <th>Cierre</th>
                    <th>Monto Inicial</th>
                    <th>Monto Final</th>
                    <th>Diferencia</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tablaHistorial">
                <tr>
                    <td colspan="8" style="text-align: center; color: var(--color-text-muted);">Cargando historial...</td>
                </tr>
            </tbody>
        </table>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            cargarHistorial();
        });

        function cargarHistorial() {
            const fecha = document.getElementById("filtroFecha").value;
            const tbody = document.getElementById("tablaHistorial");

            fetch(`apis/api_registro_cajas.php?fecha=${fecha}`)
                .then(response => response.json())
                .then(registros => {
                    tbody.innerHTML = "";

                    if (!Array.isArray(registros) || registros.length === 0) {
                        tbody.innerHTML = `<tr><td colspan="8" style="text-align:center; color: var(--color-text-muted);">No se encontraron turnos cerrados.</td></tr>`;
                        return;
                    }

                    registros.forEach(reg => {
                        const tr = document.createElement("tr");
                        const diff = Number(reg.monto_final) - Number(reg.monto_inicial);
                        const diffColor = diff >= 0 ? 'var(--accent-green)' : 'var(--accent-red)';

                        tr.innerHTML = `
                            <td>${escapeHTML(reg.id_turno)}</td>
                            <td>${escapeHTML(reg.nombre + ' ' + reg.apellido)}</td>
                            <td>${escapeHTML(reg.fecha_hora_apertura)}</td>
                            <td>${escapeHTML(reg.fecha_hora_cierre)}</td>
                            <td>$${Number(reg.monto_inicial).toFixed(2)}</td>
                            <td>$${Number(reg.monto_final).toFixed(2)}</td>
                            <td style="color: ${diffColor}; font-weight: 500;">$${diff.toFixed(2)}</td>
                            <td>
                                <img src="imagenes/acciones/borrar.png" class="accion" title="Eliminar registro viejo" onclick="eliminarTurno(${reg.id_turno})">
                            </td>
                        `;
                        tbody.appendChild(tr);
                    });
                })
                .catch(error => {
                    console.error("Error al cargar historial:", error);
                    tbody.innerHTML = `<tr><td colspan="8" style="text-align:center; color: var(--accent-red);">Error al cargar los datos.</td></tr>`;
                });
        }

        function limpiarFiltro() {
            document.getElementById("filtroFecha").value = "";
            cargarHistorial();
        }

        function eliminarTurno(id) {
            if (confirm("¿Está seguro de eliminar este registro histórico de caja? Esta acción no se puede deshacer.")) {
                fetch(`apis/api_registro_cajas.php?accion=eliminar&id=${id}`)
                    .then(response => response.json())
                    .then(res => {
                        if (res.success) {
                            cargarHistorial();
                        } else {
                            alert("No se pudo eliminar el registro.");
                        }
                    })
                    .catch(error => console.error("Error:", error));
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