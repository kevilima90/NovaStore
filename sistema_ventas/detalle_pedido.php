
<?php
require_once "proteger.php";
require_once "conexion.php";

$pedido_id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$pedido_id) {
    die("Número de pedido no válido.");
}

$sql = "
    SELECT p.id, p.fecha, p.total, p.estado,
           c.nombre, c.cedula, c.telefono,
           c.correo, c.direccion
    FROM pedidos p
    INNER JOIN clientes c ON p.cliente_id = c.id
    WHERE p.id = ?
";

$consulta = $conexion->prepare($sql);
$consulta->execute([$pedido_id]);

$pedido = $consulta->fetch(PDO::FETCH_ASSOC);

if (!$pedido) {
    die("El pedido no existe.");
}

$sqlProductos = "
    SELECT dp.producto_id, dp.cantidad, dp.precio,
           pr.nombre AS producto
    FROM detalle_pedido dp
    INNER JOIN productos pr ON dp.producto_id = pr.id
    WHERE dp.pedido_id = ?
";

$consultaProductos = $conexion->prepare($sqlProductos);
$consultaProductos->execute([$pedido_id]);

$productos = $consultaProductos->fetchAll(PDO::FETCH_ASSOC);

function e($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Detalle del pedido | NovaStore</title>

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
    max-width: 1100px;
    margin: auto;
}

.encabezado {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 30px;
}

h1, h2 {
    color: #35d9ff;
}

h1 {
    font-size: 30px;
}

h2 {
    margin-bottom: 20px;
}

.boton {
    background: #087da5;
    color: white;
    padding: 12px 18px;
    border-radius: 9px;
    text-decoration: none;
}

.tarjeta {
    background: #111c30;
    border: 1px solid #243752;
    border-radius: 14px;
    padding: 25px;
    margin-bottom: 22px;
}

.datos {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 18px;
}

.dato {
    background: #17243a;
    padding: 15px;
    border-radius: 9px;
    overflow-wrap: anywhere;
}

.dato strong {
    display: block;
    color: #35d9ff;
    margin-bottom: 8px;
}

.tabla-contenedor {
    overflow-x: auto;
}

table {
    width: 100%;
    min-width: 550px;
    border-collapse: collapse;
}

th, td {
    padding: 15px;
    text-align: left;
    border-bottom: 1px solid #283850;
}

th {
    color: #35d9ff;
    background: #17243a;
}

.total {
    text-align: right;
    font-size: 25px;
    color: #35d9ff;
    font-weight: bold;
    margin-top: 22px;
}

@media(max-width: 600px) {
    body {
        padding: 15px;
    }
}
</style>
</head>

<body>

<div class="contenedor">

    <div class="encabezado">
        <h1>Detalle del pedido #<?= (int)$pedido["id"] ?></h1>

        <div>
            <a href="gestionar_pedidos.php" class="boton">Volver a pedidos</a>
            <a href="cerrar_sesion.php" class="boton">Cerrar sesión</a>
        </div>
    </div>

    <div class="tarjeta">

        <h2>Información de la compra</h2>

        <div class="datos">

            <div class="dato">
                <strong>Número de pedido</strong>
                #<?= (int)$pedido["id"] ?>
            </div>

            <div class="dato">
                <strong>Fecha</strong>
                <?= e($pedido["fecha"]) ?>
            </div>

            <div class="dato">
                <strong>Estado</strong>
                <?= e($pedido["estado"]) ?>
            </div>

            <div class="dato">
                <strong>Total</strong>
                $<?= number_format((float)$pedido["total"], 2) ?>
            </div>

        </div>

    </div>

    <div class="tarjeta">

        <h2>Datos del cliente</h2>

        <div class="datos">

            <div class="dato">
                <strong>Nombre</strong>
                <?= e($pedido["nombre"]) ?>
            </div>

            <div class="dato">
                <strong>Cédula</strong>
                <?= e($pedido["cedula"]) ?>
            </div>

            <div class="dato">
                <strong>Teléfono</strong>
                <?= e($pedido["telefono"]) ?>
            </div>

            <div class="dato">
                <strong>Correo</strong>
                <?= e($pedido["correo"]) ?>
            </div>

            <div class="dato">
                <strong>Dirección</strong>
                <?= e($pedido["direccion"]) ?>
            </div>

        </div>

    </div>

    <div class="tarjeta">

        <h2>Productos comprados</h2>

        <div class="tabla-contenedor">

            <table>

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Producto</th>
                        <th>Precio unitario</th>
                        <th>Cantidad</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>

                <tbody>

                <?php foreach ($productos as $producto): ?>

                    <tr>
                        <td><?= (int)$producto["producto_id"] ?></td>
                        <td><?= e($producto["producto"]) ?></td>
                        <td>$<?= number_format((float)$producto["precio"], 2) ?></td>
                        <td><?= (int)$producto["cantidad"] ?></td>
                        <td>
                            $<?= number_format(
                                (float)$producto["precio"] * (int)$producto["cantidad"],
                                2
                            ) ?>
                        </td>
                    </tr>

                <?php endforeach; ?>

                <?php if (empty($productos)): ?>
                    <tr>
                        <td colspan="5">No hay productos registrados para este pedido.</td>
                    </tr>
                <?php endif; ?>

                </tbody>

            </table>

        </div>

        <div class="total">
            Total: $<?= number_format((float)$pedido["total"], 2) ?>
        </div>

    </div>

</div>

</body>
</html>
