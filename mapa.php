<?php
include_once("includes/funciones.php");
include_once("includes/conexionBD.php");
verificar_sesion();

$nombre_empleado = $_SESSION['empleado_nombre'] ?? 'Empleado';$esAdmin = tieneRol(['administrador', 'admin', 'encargado', 'cajero']); // Según entrevista, admin o cajero

// Cargar mesa desde la base de datos (asumiendo que guardas pos_x y pos_y)
$sql = "SELECT id_mesa, numero, estado, pos_x, pos_y FROM mesa";
$resultado = mysqli_query($conexion, $sql);$mesa = [];
if ($resultado) {
    while ($row = mysqli_fetch_assoc($resultado)) {
        $mesa[] =$row;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mapa Interactivo - Lo de Torres</title>
    <link rel="stylesheet" href="includes/style.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        .salon-contenedor {
            position: relative;
            width: 100%;
            height: 70vh;
            background: var(--bg-card);
            border: 2px dashed var(--border-subtle);
            border-radius: 12px;
            overflow: hidden;
            margin-top: 15px;
        }
        /* Mesa estilo cuadrado genérico arrastrable */
        .mesa-cuadrada {
            position: absolute;
            width: 90px;
            height: 90px;
            background: var(--bg-input);
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: grab;
            user-select: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.3);
            font-weight: bold;
            font-size: 14px;
            transition: border-color 0.2s;
        }
        .mesa-cuadrada:active {
            cursor: grabbing;
        }
        /* Colores según estados pedidos en la entrevista */
        .estado-libre { border: 2px solid #4ade80; color: #4ade80; background: rgba(74, 222, 128, 0.05); }
        .estado-esperando { border: 2px solid #f59e0b; color: #f59e0b; background: rgba(245, 158, 11, 0.05); }
        .estado-ocupada { border: 2px solid #ef4444; color: #ef4444; background: rgba(239, 68, 68, 0.05); }
        .estado-limpiar { border: 2px solid #64748b; color: #94a3b8; background: rgba(100, 116, 139, 0.1); }
    </style>
</head>
<body>

    <div class="contenido-principal" style="padding: 0;">
        <div class="barra-superior">
            <h1 style="font-size: 1.1rem; display: flex; align-items: center; gap: 8px;">
                <a href="dashboard.php" style="color: var(--accent-green); text-decoration: none;"><i class="ri-arrow-left-line"></i></a>
                Mapa del Salón (Distribución)
            </h1>
            <div class="billetera-usuario" style="color: var(--accent-green); font-size: 0.9rem;">
                <i class="ri-user-3-fill"></i>
                <span><?= htmlspecialchars($nombre_empleado, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </div>
    </div>

    <div style="padding: 20px; max-width: 1200px; margin: 0 auto;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h2 style="color: var(--color-text-main); font-size: 20px; margin: 0;">Arrastra las mesas para ubicarlas</h2>
            <?php if ($esAdmin): ?>
                <span style="color: var(--accent-cyan); font-size: 13px;"><i class="ri-information-line"></i> Modo edición activo</span>
            <?php endif; ?>
        </div>

        <!-- Lienzo del salón donde se mueven las mesa -->
        <div class="salon-contenedor" id="salon">
           <?php
// Consulta adaptada a tu tabla 'mesa' y con id_estado numérico
$sql = "SELECT id_mesa, numero, id_estado, pos_x, pos_y, ancho FROM mesa";
$resultado = mysqli_query($conexion, $sql);
$mesas = [];
if ($resultado) {
    while ($row = mysqli_fetch_assoc($resultado)) {
        $mesas[] = $row;
    }
}
?>

<!-- En la sección del HTML / Bucle de mesas -->
<?php foreach ($mesas as $m): 
    // Mapear id_estado a clases CSS (asumiendo IDs: 1=Libre, 2=Esperando, 3=Ocupada, 4=Limpiar)
    $claseEstado = 'estado-libre';
    $id_est = intval($m['id_estado'] ?? 1);
    if ($id_est === 2) $claseEstado = 'estado-esperando';
    if ($id_est === 3) $claseEstado = 'estado-ocupada';
    if ($id_est === 4) $claseEstado = 'estado-limpiar';

    $posX = max(20, intval($m['pos_x'] ?? 20));
    $posY = max(20, intval($m['pos_y'] ?? 20));
    $anchoMesa = intval($m['ancho'] ?? 90); // Usando tu columna 'ancho'
?>
    <div class="mesa-cuadrada <?= $claseEstado ?>" 
         id="mesa-<?= $m['id_mesa'] ?>" 
         style="left: <?= $posX ?>px; top: <?= $posY ?>px; width: <?= $anchoMesa ?>px; height: <?= $anchoMesa ?>px;"
         draggable="true"
         ondragstart="iniciarArrastre(event)"
         ondragend="terminarArrastre(event, <?= $m['id_mesa'] ?>)">
        <i class="ri-restaurant-line" style="font-size: 18px; margin-bottom: 4px;"></i>
        Mesa <?= htmlspecialchars($m['numero']) ?>
    </div>
<?php endforeach; ?>
        </div>
    </div>
    <!-- PANEL INFERIOR PARA CREAR NUEVAS mesa -->
    <div style="margin-top: 25px; background: var(--bg-card); padding: 20px; border-radius: 8px; border: 1px solid var(--border-subtle); max-width: 1200px; margin-left: auto; margin-right: auto; box-sizing: border-box;">
        <h3 style="color: var(--color-text-main); font-size: 16px; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
            <i class="ri-add-box-line" style="color: var(--accent-cyan);"></i> Agregar Nueva Mesa
        </h3>
     <form action="apis/api_crear_mesa.php" method="POST" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;">
    <div style="flex: 1; min-width: 150px;">
        <label for="numero" style="display: block; font-size: 13px; color: var(--color-text-muted); margin-bottom: 6px;">Número de Mesa</label>
        <input type="number" id="numero" name="numero" required placeholder="Ej. 10" style="width: 100%; padding: 10px; font-size: 14px; background: var(--bg-input); border: 1px solid var(--border-subtle); color: #fff; border-radius: 6px; box-sizing: border-box; outline: none;">
    </div>

    <div style="flex: 1; min-width: 150px;">
        <label for="ancho" style="display: block; font-size: 13px; color: var(--color-text-muted); margin-bottom: 6px;">Ancho / Tamaño (px)</label>
        <input type="number" id="ancho" name="ancho" value="90" required style="width: 100%; padding: 10px; font-size: 14px; background: var(--bg-input); border: 1px solid var(--border-subtle); color: #fff; border-radius: 6px; box-sizing: border-box; outline: none;">
    </div>

    <div>
        <button type="submit" style="background: var(--accent-green); color: #000; border: none; padding: 10px 20px; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 14px; display: inline-flex; align-items: center; gap: 6px; height: 41px;">
            <i class="ri-add-line"></i> Crear Mesa
        </button>
    </div>
</form>
    </div>
    <!-- PANEL INFERIOR PARA ELIMINAR MESA -->
    <div style="margin-top: 15px; background: var(--bg-card); padding: 20px; border-radius: 8px; border: 1px solid var(--border-subtle); max-width: 1200px; margin-left: auto; margin-right: auto; box-sizing: border-box;">
        <h3 style="color: var(--accent-red); font-size: 16px; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
            <i class="ri-delete-bin-line"></i> Eliminar Mesa
        </h3>
        
        <form action="apis/api_eliminar_mesa.php" method="POST" onsubmit="return confirm('¿Estás seguro de eliminar esta mesa?');" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;">
            <div style="flex: 1; min-width: 200px;">
                <label for="id_mesa_eliminar" style="display: block; font-size: 13px; color: var(--color-text-muted); margin-bottom: 6px;">Seleccionar Mesa</label>
                <select id="id_mesa_eliminar" name="id_mesa" required style="width: 100%; padding: 10px; font-size: 14px; background: var(--bg-input); border: 1px solid var(--border-subtle); color: #fff; border-radius: 6px; box-sizing: border-box; outline: none;">
                    <option value="" disabled selected>-- Elige una mesa --</option>
                    <?php foreach ($mesas as $m): ?>
                        <option value="<?= $m['id_mesa'] ?>">Mesa <?= htmlspecialchars($m['numero']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <button type="submit" style="background: rgba(255, 74, 90, 0.1); color: var(--accent-red); border: 1px solid var(--accent-red); padding: 10px 20px; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 14px; display: inline-flex; align-items: center; gap: 6px; height: 41px;">
                    <i class="ri-delete-bin-line"></i> Eliminar Mesa
                </button>
            </div>
        </form>
    </div>

    <script>
        let offsetX, offsetY;

        function iniciarArrastre(e) {
            // Guardar la posición inicial del mouse relativa al elemento
            const rect = e.target.getBoundingClientRect();
            offsetX = e.clientX - rect.left;
            offsetY = e.clientY - rect.top;
            e.dataTransfer.setData("text/plain", e.target.id);
        }

        function terminarArrastre(e, idMesa) {
            const salon = document.getElementById("salon").getBoundingClientRect();
            
            // Calcular nueva posición dentro del contenedor
            let nuevaX = e.clientX - salon.left - offsetX;
            let nuevaY = e.clientY - salon.top - offsetY;

            // Limitar para que no salgan del contenedor
            if (nuevaX < 0) nuevaX = 0;
            if (nuevaY < 0) nuevaY = 0;

            // Mover visualmente el elemento de inmediato
            const elemento = document.getElementById(`mesa-${idMesa}`);
            elemento.style.left = `${nuevaX}px`;
            elemento.style.top = `${nuevaY}px`;

            // Enviar la nueva posición a PHP mediante AJAX (Fetch)
            fetch('apis/api_actualizar_posicion_mesa.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id_mesa=${idMesa}&pos_x=${Math.round(nuevaX)}&pos_y=${Math.round(nuevaY)}`
            })
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    console.error("No se pudo guardar la posición de la mesa");
                }
            })
            .catch(err => console.error("Error de conexión:", err));
        }
    </script>
</body>
</html>