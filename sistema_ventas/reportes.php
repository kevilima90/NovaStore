
<?php
require_once "proteger.php";
require_once "conexion.php";

// Funciones auxiliares
function e($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, "UTF-8");
}

function dinero($valor) {
    return "$" . number_format((float)$valor, 2);
}

function porcentaje($valor, $maximo) {
    if ((float)$maximo <= 0) {
        return 0;
    }

    return min(100, ((float)$valor / (float)$maximo) * 100);
}

// ========================================
// 1. ESTADÍSTICAS GENERALES
// ========================================

$sql = "
    SELECT
        COUNT(*) AS total_pedidos,

        SUM(
            CASE WHEN estado != 'Cancelado' THEN 1 ELSE 0 END
        ) AS pedidos_validos,

        SUM(
            CASE WHEN estado = 'Cancelado' THEN 1 ELSE 0 END
        ) AS pedidos_cancelados,

        COALESCE(
            SUM(
                CASE WHEN estado != 'Cancelado' THEN total ELSE 0 END
            ), 0
        ) AS ventas_totales,

        COALESCE(
            AVG(
                CASE WHEN estado != 'Cancelado' THEN total ELSE NULL END
            ), 0
        ) AS promedio_compra

    FROM pedidos
";

$general = $conexion->query($sql)->fetch(PDO::FETCH_ASSOC);

// ========================================
// 2. VENTAS DE LOS ÚLTIMOS 7 DÍAS
// ========================================

$sql = "
    SELECT
        DATE(fecha) AS periodo,
        COUNT(*) AS pedidos,
        COALESCE(SUM(total), 0) AS ventas

    FROM pedidos

    WHERE estado != 'Cancelado'
      AND fecha >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
      AND fecha < DATE_ADD(CURDATE(), INTERVAL 1 DAY)

    GROUP BY DATE(fecha)
    ORDER BY periodo ASC
";

$consulta = $conexion->query($sql);
$ventasDiarias = $consulta->fetchAll(PDO::FETCH_ASSOC);

// ========================================
// 3. VENTAS DE LOS ÚLTIMOS 12 MESES
// ========================================

$sql = "
    SELECT
        DATE_FORMAT(fecha, '%Y-%m') AS periodo,
        COUNT(*) AS pedidos,
        COALESCE(SUM(total), 0) AS ventas

    FROM pedidos

    WHERE estado != 'Cancelado'
      AND fecha >= DATE_FORMAT(
          DATE_SUB(CURDATE(), INTERVAL 11 MONTH),
          '%Y-%m-01'
      )

    GROUP BY DATE_FORMAT(fecha, '%Y-%m')
    ORDER BY periodo ASC
";

$consulta = $conexion->query($sql);
$ventasMensuales = $consulta->fetchAll(PDO::FETCH_ASSOC);

// ========================================
// 4. VENTAS POR AÑO
// ========================================

$sql = "
    SELECT
        YEAR(fecha) AS periodo,
        COUNT(*) AS pedidos,
        COALESCE(SUM(total), 0) AS ventas

    FROM pedidos

    WHERE estado != 'Cancelado'
      AND fecha >= DATE_SUB(
          DATE_FORMAT(CURDATE(), '%Y-01-01'),
          INTERVAL 4 YEAR
      )

    GROUP BY YEAR(fecha)
    ORDER BY periodo ASC
";

$consulta = $conexion->query($sql);
$ventasAnuales = $consulta->fetchAll(PDO::FETCH_ASSOC);

// ========================================
// 5. PRODUCTOS MÁS VENDIDOS
// ========================================

$sql = "
    SELECT
        pr.id,
        pr.nombre,
        pr.imagen,
        SUM(dp.cantidad) AS unidades_vendidas,
        SUM(dp.cantidad * dp.precio) AS total_vendido

    FROM detalle_pedido dp

    INNER JOIN pedidos pe
        ON pe.id = dp.pedido_id

    INNER JOIN productos pr
        ON pr.id = dp.producto_id

    WHERE pe.estado != 'Cancelado'

    GROUP BY pr.id, pr.nombre, pr.imagen

    ORDER BY unidades_vendidas DESC

    LIMIT 10
";

$consulta = $conexion->query($sql);
$productosVendidos = $consulta->fetchAll(PDO::FETCH_ASSOC);

// ========================================
// 6. CLIENTES QUE MÁS COMPRAN
// ========================================

$sql = "
    SELECT
        c.id,
        c.nombre,
        c.correo,
        COUNT(p.id) AS cantidad_pedidos,
        COALESCE(SUM(p.total), 0) AS total_comprado

    FROM clientes c

    INNER JOIN pedidos p
        ON p.cliente_id = c.id

    WHERE p.estado != 'Cancelado'

    GROUP BY c.id, c.nombre, c.correo

    ORDER BY total_comprado DESC

    LIMIT 10
";

$consulta = $conexion->query($sql);
$mejoresClientes = $consulta->fetchAll(PDO::FETCH_ASSOC);

// ========================================
// 7. PEDIDOS POR ESTADO
// ========================================

$sql = "
    SELECT
        estado,
        COUNT(*) AS cantidad

    FROM pedidos

    GROUP BY estado
    ORDER BY cantidad DESC
";

$consulta = $conexion->query($sql);
$pedidosPorEstado = $consulta->fetchAll(PDO::FETCH_ASSOC);

// ========================================
// 8. MÁXIMOS PARA LOS GRÁFICOS
// ========================================

$maxDiario = 0;
foreach ($ventasDiarias as $venta) {
    $maxDiario = max($maxDiario, (float)$venta["ventas"]);
}

$maxMensual = 0;
foreach ($ventasMensuales as $venta) {
    $maxMensual = max($maxMensual, (float)$venta["ventas"]);
}

$maxAnual = 0;
foreach ($ventasAnuales as $venta) {
    $maxAnual = max($maxAnual, (float)$venta["ventas"]);
}

$maxProducto = 0;
foreach ($productosVendidos as $producto) {
    $maxProducto = max(
        $maxProducto,
        (int)$producto["unidades_vendidas"]
    );
}

$maxCliente = 0;
foreach ($mejoresClientes as $cliente) {
    $maxCliente = max(
        $maxCliente,
        (float)$cliente["total_comprado"]
    );
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Reportes y estadísticas | NovaStore</title>

<style>

* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
    font-family: Arial, sans-serif;
}

body {
    background: #080d19;
    color: white;
    padding: 30px;
}

.contenedor {
    max-width: 1400px;
    margin: auto;
}

.encabezado {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 30px;
}

h1 {
    color: #35d9ff;
    font-size: 32px;
    margin-bottom: 8px;
}

h2 {
    color: #35d9ff;
    margin-bottom: 20px;
}

.subtitulo {
    color: #aab6cc;
}

.boton {
    display: inline-block;
    background: #087da5;
    color: white;
    padding: 13px 18px;
    border-radius: 9px;
    text-decoration: none;
    font-weight: bold;
}

.boton:hover {
    background: #08a9d8;
}

.estadisticas {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
    gap: 18px;
    margin-bottom: 28px;
}

.tarjeta {
    background: #111c30;
    padding: 24px;
    border: 1px solid #243752;
    border-radius: 14px;
}

.tarjeta p {
    color: #aab6cc;
    margin-bottom: 13px;
    font-size: 14px;
}

.tarjeta h2 {
    font-size: 29px;
    margin: 0;
    color: #35d9ff;
}

.tarjeta.verde h2 {
    color: #54e6a5;
}

.tarjeta.roja h2 {
    color: #ff7688;
}

.seccion {
    background: #111c30;
    border: 1px solid #243752;
    border-radius: 14px;
    padding: 25px;
    margin-bottom: 25px;
}

.graficos {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 25px;
}

.grafico {
    background: #111c30;
    border: 1px solid #243752;
    border-radius: 14px;
    padding: 22px;
    min-width: 0;
}

.grafico h2 {
    font-size: 19px;
}

.barra-contenedor {
    margin-bottom: 19px;
}

.barra-info {
    display: flex;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 8px;
    font-size: 13px;
    color: #d7e3f5;
}

.barra-fondo {
    width: 100%;
    height: 12px;
    background: #263750;
    border-radius: 20px;
    overflow: hidden;
}

.barra {
    height: 100%;
    background: linear-gradient(90deg, #087da5, #35d9ff);
    border-radius: 20px;
}

.barra-verde {
    background: linear-gradient(90deg, #168b63, #54e6a5);
}

.barra-morada {
    background: linear-gradient(90deg, #6243b5, #a78bfa);
}

.tabla-scroll {
    overflow-x: auto;
}

table {
    width: 100%;
    min-width: 650px;
    border-collapse: collapse;
}

th, td {
    padding: 14px;
    text-align: left;
    border-bottom: 1px solid #283850;
}

th {
    color: #35d9ff;
    background: #17243a;
}

tr:hover {
    background: #17243a;
}

.producto {
    display: flex;
    align-items: center;
    gap: 12px;
}

.producto img {
    width: 55px;
    height: 55px;
    object-fit: contain;
    background: #fff;
    border-radius: 8px;
    padding: 4px;
}

.sin-datos {
    color: #aab6cc;
    padding: 18px 0;
}

.estado {
    display: inline-block;
    padding: 7px 10px;
    border-radius: 20px;
    font-size: 12px;
    background: #263750;
    color: #d7e3f5;
}

@media(max-width: 600px) {

    body {
        padding: 15px;
    }

    h1 {
        font-size: 25px;
    }

    .seccion, .grafico {
        padding: 16px;
    }

    .estadisticas {
        grid-template-columns: 1fr 1fr;
    }

    .tarjeta {
        padding: 17px;
    }

    .tarjeta h2 {
        font-size: 22px;
    }
}

</style>

</head>

<body>

<div class="contenedor">

    <!-- ENCABEZADO -->

    <div class="encabezado">

        <div>
            <h1>NovaStore | Reportes</h1>

            <p class="subtitulo">
                Estadísticas y rendimiento de tu tienda.
            </p>
        </div>

        <a href="admin.php" class="boton">
            ← Volver al panel
        </a>

    </div>


    <!-- ESTADÍSTICAS GENERALES -->

    <div class="estadisticas">

        <div class="tarjeta">
            <p>Total de pedidos</p>
            <h2><?= (int)$general["total_pedidos"] ?></h2>
        </div>

        <div class="tarjeta verde">
            <p>Ventas no canceladas</p>
            <h2><?= dinero($general["ventas_totales"]) ?></h2>
        </div>

        <div class="tarjeta">
            <p>Pedidos válidos</p>
            <h2><?= (int)$general["pedidos_validos"] ?></h2>
        </div>

        <div class="tarjeta roja">
            <p>Pedidos cancelados</p>
            <h2><?= (int)$general["pedidos_cancelados"] ?></h2>
        </div>

        <div class="tarjeta">
            <p>Compra promedio</p>
            <h2><?= dinero($general["promedio_compra"]) ?></h2>
        </div>

    </div>


    <!-- VENTAS POR DÍA -->

    <div class="seccion">

        <h2>Ventas de los últimos 7 días</h2>

        <?php if (empty($ventasDiarias)): ?>

            <p class="sin-datos">Todavía no existen ventas en este período.</p>

        <?php else: ?>

            <?php foreach ($ventasDiarias as $venta): ?>

                <div class="barra-contenedor">

                    <div class="barra-info">

                        <span><?= e(date("d/m/Y", strtotime($venta["periodo"]))) ?></span>

                        <strong><?= dinero($venta["ventas"]) ?></strong>

                    </div>

                    <div class="barra-fondo">

                        <div
                            class="barra"
                            style="width: <?= porcentaje($venta["ventas"], $maxDiario) ?>%">
                        </div>

                    </div>

                    <small>
                        <?= (int)$venta["pedidos"] ?> pedidos
                    </small>

                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>


    <!-- VENTAS MENSUALES Y ANUALES -->

    <div class="graficos">

        <div class="grafico">

            <h2>Ventas por mes</h2>

            <?php if (empty($ventasMensuales)): ?>

                <p class="sin-datos">No hay ventas registradas.</p>

            <?php else: ?>

                <?php foreach ($ventasMensuales as $venta): ?>

                    <div class="barra-contenedor">

                        <div class="barra-info">

                            <span><?= e($venta["periodo"]) ?></span>

                            <strong><?= dinero($venta["ventas"]) ?></strong>

                        </div>

                        <div class="barra-fondo">

                            <div
                                class="barra barra-verde"
                                style="width: <?= porcentaje($venta["ventas"], $maxMensual) ?>%">
                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            <?php endif; ?>

        </div>


        <div class="grafico">

            <h2>Ventas por año</h2>

            <?php if (empty($ventasAnuales)): ?>

                <p class="sin-datos">No hay ventas registradas.</p>

            <?php else: ?>

                <?php foreach ($ventasAnuales as $venta): ?>

                    <div class="barra-contenedor">

                        <div class="barra-info">

                            <span><?= e($venta["periodo"]) ?></span>

                            <strong><?= dinero($venta["ventas"]) ?></strong>

                        </div>

                        <div class="barra-fondo">

                            <div
                                class="barra barra-morada"
                                style="width: <?= porcentaje($venta["ventas"], $maxAnual) ?>%">
                            </div>

                        </div>

                    </div>

                <?php endforeach; ?>

            <?php endif; ?>

        </div>

    </div>


    <!-- PRODUCTOS MÁS VENDIDOS -->

    <div class="seccion">

        <h2>Productos más vendidos</h2>

        <div class="tabla-scroll">

            <table>

                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Unidades vendidas</th>
                        <th>Total vendido</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (!empty($productosVendidos)): ?>

                    <?php foreach ($productosVendidos as $producto): ?>

                        <tr>

                            <td>

                                <div class="producto">

                                    <?php if (!empty($producto["imagen"])): ?>

                                        <img
                                            src="imagenes/<?= rawurlencode(basename($producto["imagen"])) ?>"
                                            alt="Producto">

                                    <?php endif; ?>

                                    <strong><?= e($producto["nombre"]) ?></strong>

                                </div>

                            </td>

                            <td>
                                <?= (int)$producto["unidades_vendidas"] ?>
                            </td>

                            <td>
                                <?= dinero($producto["total_vendido"]) ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="3">Todavía no hay productos vendidos.</td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>


    <!-- CLIENTES QUE MÁS COMPRAN -->

    <div class="seccion">

        <h2>Mejores clientes</h2>

        <div class="tabla-scroll">

            <table>

                <thead>
                    <tr>
                        <th>Posición</th>
                        <th>Cliente</th>
                        <th>Correo</th>
                        <th>Pedidos</th>
                        <th>Total comprado</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (!empty($mejoresClientes)): ?>

                    <?php foreach ($mejoresClientes as $i => $cliente): ?>

                        <tr>

                            <td>
                                <strong>#<?= $i + 1 ?></strong>
                            </td>

                            <td><?= e($cliente["nombre"]) ?></td>

                            <td><?= e($cliente["correo"]) ?></td>

                            <td><?= (int)$cliente["cantidad_pedidos"] ?></td>

                            <td>
                                <strong><?= dinero($cliente["total_comprado"]) ?></strong>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="5">Todavía no hay clientes con compras.</td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>


    <!-- PEDIDOS POR ESTADO -->

    <div class="seccion">

        <h2>Resumen de pedidos por estado</h2>

        <div class="tabla-scroll">

            <table>

                <thead>
                    <tr>
                        <th>Estado</th>
                        <th>Cantidad de pedidos</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (!empty($pedidosPorEstado)): ?>

                    <?php foreach ($pedidosPorEstado as $estado): ?>

                        <tr>

                            <td>
                                <span class="estado">
                                    <?= e($estado["estado"]) ?>
                                </span>
                            </td>

                            <td>
                                <strong><?= (int)$estado["cantidad"] ?></strong>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="2">Todavía no hay pedidos registrados.</td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

</body>
</html>
