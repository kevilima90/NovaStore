
<?php
require_once "proteger.php";
require_once "conexion.php";

// Estadísticas generales
$totalProductos = $conexion->query(
    "SELECT COUNT(*) FROM productos"
)->fetchColumn();

$totalClientes = $conexion->query(
    "SELECT COUNT(*) FROM clientes"
)->fetchColumn();

$totalPedidos = $conexion->query(
    "SELECT COUNT(*) FROM pedidos"
)->fetchColumn();

$pedidosPendientes = $conexion->query(
    "SELECT COUNT(*) FROM pedidos WHERE estado = 'Pendiente'"
)->fetchColumn();

$totalVentas = $conexion->query(
    "SELECT COALESCE(SUM(total), 0)
     FROM pedidos
     WHERE estado != 'Cancelado'"
)->fetchColumn();

// Últimos pedidos
$sql = "SELECT p.*, c.nombre AS cliente
        FROM pedidos p
        INNER JOIN clientes c ON p.cliente_id = c.id
        ORDER BY p.id DESC
        LIMIT 10";

$pedidos = $conexion->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Panel de administración | NovaStore</title>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    padding: 30px;
    font-family: Arial, sans-serif;
    background: #080d1b;
    color: white;
}

.contenedor {
    max-width: 1350px;
    margin: auto;
}

.encabezado {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 35px;
}

h1 {
    color: #38d9ff;
    font-size: 34px;
    margin-bottom: 8px;
}

h2 {
    color: #38d9ff;
    margin-bottom: 20px;
}

.botones {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.boton {
    display: inline-block;
    padding: 13px 17px;
    border-radius: 9px;
    background: #087ea4;
    color: white;
    text-decoration: none;
    font-weight: bold;
    transition: 0.3s;
}

.boton:hover {
    background: #08a9d8;
    transform: translateY(-2px);
}

.reportes {
    background: #673ab7;
}

.reportes:hover {
    background: #8252d6;
}

.configuracion {
    background: #16865b;
}

.configuracion:hover {
    background: #20a875;
}

.cerrar {
    background: #b83248;
}

.cerrar:hover {
    background: #dc3545;
}

.estadisticas {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 35px;
}

.tarjeta {
    background: linear-gradient(145deg, #111a2d, #17243b);
    border: 1px solid #263b59;
    border-radius: 14px;
    padding: 25px;
    transition: 0.3s;
}

.tarjeta:hover {
    transform: translateY(-4px);
    border-color: #38d9ff;
}

.tarjeta p {
    color: #aebdd0;
    font-size: 16px;
}

.numero {
    font-size: 32px;
    font-weight: bold;
    color: #38d9ff;
}

.panel {
    background: #111a2d;
    border: 1px solid #263b59;
    border-radius: 14px;
    padding: 25px;
}

.tabla-scroll {
    overflow-x: auto;
}

table {
    width: 100%;
    min-width: 700px;
    border-collapse: collapse;
}

th, td {
    padding: 15px;
    text-align: left;
    border-bottom: 1px solid #263b59;
}

th {
    color: #38d9ff;
    background: #17243b;
}

.estado {
    padding: 7px 11px;
    border-radius: 6px;
    background: #263b59;
}

.detalle {
    color: #38d9ff;
    text-decoration: none;
    font-weight: bold;
}

.detalle:hover {
    text-decoration: underline;
}

@media(max-width: 650px) {
    body {
        padding: 15px;
    }

    .encabezado {
        align-items: flex-start;
    }

    .botones {
        width: 100%;
    }

    .boton {
        text-align: center;
        flex: 1 1 100%;
    }

    .panel {
        padding: 15px;
    }
}
</style>
</head>

<body>

<div class="contenedor">

    <div class="encabezado">

        <div>
            <h1>NovaStore</h1>
            <p>Panel de administración</p>

            <p>
                Bienvenido,
                <?= htmlspecialchars($_SESSION["administrador_usuario"] ?? "Administrador") ?>
            </p>
        </div>

        <div class="botones">

            <a href="index.php" class="boton">
                Ver tienda
            </a>

            <a href="gestionar_productos.php" class="boton">
                Gestionar productos
            </a>

            <a href="gestionar_clientes.php" class="boton">
                Gestionar clientes
            </a>

            <a href="gestionar_pedidos.php" class="boton">
                Gestionar pedidos
            </a>

            <a href="reportes.php" class="boton reportes">
                Reportes y estadísticas
            </a>

            <a href="configuracion.php" class="boton configuracion">
                Configuración general
            </a>

            <a href="cerrar_sesion.php" class="boton cerrar">
                Cerrar sesión
            </a>

        </div>

    </div>

    <h2>Resumen general</h2>

    <div class="estadisticas">

        <div class="tarjeta">
            <p>Total de productos</p>
            <div class="numero">
                <?= (int)$totalProductos ?>
            </div>
        </div>

        <div class="tarjeta">
            <p>Total de clientes</p>
            <div class="numero">
                <?= (int)$totalClientes ?>
            </div>
        </div>

        <div class="tarjeta">
            <p>Total de pedidos</p>
            <div class="numero">
                <?= (int)$totalPedidos ?>
            </div>
        </div>

        <div class="tarjeta">
            <p>Pedidos pendientes</p>
            <div class="numero">
                <?= (int)$pedidosPendientes ?>
            </div>
        </div>

        <div class="tarjeta">
            <p>Total de ventas</p>
            <div class="numero">
                $<?= number_format((float)$totalVentas, 2) ?>
            </div>
        </div>

    </div>

    <div class="panel">

        <h2>Pedidos recientes</h2>

        <div class="tabla-scroll">

            <table>
                <thead>
                    <tr>
                        <th>N.º</th>
                        <th>Cliente</th>
                        <th>Fecha</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Detalles</th>
                    </tr>
                </thead>

                <tbody>

                <?php foreach ($pedidos as $pedido): ?>

                    <tr>
                        <td><?= (int)$pedido["id"] ?></td>

                        <td>
                            <?= htmlspecialchars($pedido["cliente"]) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($pedido["fecha"]) ?>
                        </td>

                        <td>
                            $<?= number_format((float)$pedido["total"], 2) ?>
                        </td>

                        <td>
                            <span class="estado">
                                <?= htmlspecialchars($pedido["estado"]) ?>
                            </span>
                        </td>

                        <td>
                            <a class="detalle"
                               href="detalle_pedido.php?id=<?= (int)$pedido["id"] ?>">
                                Ver detalles
                            </a>
                        </td>
                    </tr>

                <?php endforeach; ?>

                <?php if (empty($pedidos)): ?>
                    <tr>
                        <td colspan="6">
                            Todavía no hay pedidos registrados.
                        </td>
                    </tr>
                <?php endif; ?>

                </tbody>
            </table>

        </div>

    </div>

</div>

</body>
</html>
