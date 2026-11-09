
<?php
require_once "conexion.php";

$conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function escapar($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

function mostrarResultado($titulo, $mensaje, $exito = false) {
    $color = $exito ? '#22c55e' : '#ef4444';

    echo '<!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>NovaStore | Pedido</title>
        <link rel="stylesheet" href="css/estilos.css">
        <style>
            body { background:#080d19; color:white; }
            .resultado {
                max-width:650px; margin:90px auto; padding:40px 25px;
                text-align:center; background:#151f34;
                border:1px solid #263b5b; border-radius:20px;
            }
            .resultado h1 { color:' . $color . '; font-size:32px; }
            .resultado p { font-size:18px; line-height:1.7; }
            .resultado a {
                display:inline-block; margin-top:20px; padding:14px 25px;
                background:linear-gradient(90deg,#087fce,#7139c9);
                color:white; text-decoration:none; border-radius:10px;
            }
        </style>
    </head>
    <body>
        <div class="resultado">
            <h1>' . escapar($titulo) . '</h1>
            <p>' . escapar($mensaje) . '</p>
            <a href="index.php">Volver a NovaStore</a>
        </div>
    </body>
    </html>';
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    mostrarResultado("Acceso no permitido", "Debes iniciar el proceso desde el formulario de compra.");
    exit;
}

// Recibir datos del cliente
$nombre = trim($_POST["nombre"] ?? "");
$cedula = trim($_POST["cedula"] ?? "");
$telefono = trim($_POST["telefono"] ?? "");
$correo = trim($_POST["correo"] ?? "");
$direccion = trim($_POST["direccion"] ?? "");

if ($nombre === "" || $cedula === "" || $telefono === "" ||
    $correo === "" || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    mostrarResultado("Datos incorrectos", "Revisa los datos del formulario e inténtalo nuevamente.");
    exit;
}

// Recibir carrito
$carrito = json_decode($_POST["carrito"] ?? "", true);

if (!is_array($carrito) || count($carrito) === 0) {
    mostrarResultado("Carrito vacío", "Agrega productos antes de confirmar tu pedido.");
    exit;
}

// Agrupar productos repetidos y validar cantidades
$cantidades = [];

foreach ($carrito as $item) {
    $id = filter_var($item["id"] ?? null, FILTER_VALIDATE_INT);
    $cantidad = filter_var($item["cantidad"] ?? null, FILTER_VALIDATE_INT);

    if (!$id || !$cantidad || $cantidad < 1 || $cantidad > 1000) {
        mostrarResultado("Pedido inválido", "Hay productos o cantidades incorrectas en el carrito.");
        exit;
    }

    $cantidades[$id] = ($cantidades[$id] ?? 0) + $cantidad;
}

try {
    $conexion->beginTransaction();

    // Comprobar productos, precios y existencias directamente en MariaDB
    $consultaProducto = $conexion->prepare(
        "SELECT id, nombre, precio, stock
         FROM productos
         WHERE id = ?
         FOR UPDATE"
    );

    $productosPedido = [];
    $total = 0;

    foreach ($cantidades as $productoId => $cantidad) {
        $consultaProducto->execute([$productoId]);
        $producto = $consultaProducto->fetch(PDO::FETCH_ASSOC);

        if (!$producto) {
            throw new Exception("Uno de los productos ya no existe.");
        }

        if ((int)$producto["stock"] < $cantidad) {
            throw new Exception(
                "No hay suficiente stock de: " . $producto["nombre"]
            );
        }

        $precio = (float)$producto["precio"];
        $subtotal = $precio * $cantidad;
        $total += $subtotal;

        $productosPedido[] = [
            "id" => (int)$producto["id"],
            "cantidad" => $cantidad,
            "precio" => $precio
        ];
    }

    // Buscar cliente por cédula
    $buscarCliente = $conexion->prepare(
        "SELECT id FROM clientes WHERE cedula = ? LIMIT 1"
    );
    $buscarCliente->execute([$cedula]);
    $cliente = $buscarCliente->fetch(PDO::FETCH_ASSOC);

    if ($cliente) {
        $clienteId = (int)$cliente["id"];

        $actualizarCliente = $conexion->prepare(
            "UPDATE clientes
             SET nombre = ?, telefono = ?, correo = ?, direccion = ?
             WHERE id = ?"
        );

        $actualizarCliente->execute([
            $nombre,
            $telefono,
            $correo,
            $direccion !== "" ? $direccion : null,
            $clienteId
        ]);

    } else {
        $insertarCliente = $conexion->prepare(
            "INSERT INTO clientes
             (nombre, cedula, telefono, correo, direccion)
             VALUES (?, ?, ?, ?, ?)"
        );

        $insertarCliente->execute([
            $nombre,
            $cedula,
            $telefono,
            $correo,
            $direccion !== "" ? $direccion : null
        ]);

        $clienteId = (int)$conexion->lastInsertId();
    }

    // Registrar pedido
    $insertarPedido = $conexion->prepare(
        "INSERT INTO pedidos (cliente_id, fecha, total, estado)
         VALUES (?, NOW(), ?, 'Pendiente')"
    );

    $insertarPedido->execute([$clienteId, $total]);

    $pedidoId = (int)$conexion->lastInsertId();

    // Registrar detalles y descontar existencias
    $insertarDetalle = $conexion->prepare(
        "INSERT INTO detalle_pedido
         (pedido_id, producto_id, cantidad, precio)
         VALUES (?, ?, ?, ?)"
    );

    $descontarStock = $conexion->prepare(
        "UPDATE productos
         SET stock = stock - ?
         WHERE id = ? AND stock >= ?"
    );

    foreach ($productosPedido as $producto) {

        $insertarDetalle->execute([
            $pedidoId,
            $producto["id"],
            $producto["cantidad"],
            $producto["precio"]
        ]);

        $descontarStock->execute([
            $producto["cantidad"],
            $producto["id"],
            $producto["cantidad"]
        ]);

        if ($descontarStock->rowCount() !== 1) {
            throw new Exception("No se pudo actualizar el inventario.");
        }
    }

    // Confirmar todas las operaciones
    $conexion->commit();

    // Limpiar carrito del navegador
    $respuesta = true;

    echo '<!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Pedido confirmado | NovaStore</title>
        <link rel="stylesheet" href="css/estilos.css">
        <style>
            body { background:#080d19; color:white; }
            .resultado {
                max-width:650px; margin:90px auto; padding:40px 25px;
                text-align:center; background:#151f34;
                border:1px solid #263b5b; border-radius:20px;
            }
            .resultado h1 { color:#22c55e; font-size:34px; }
            .resultado p { font-size:19px; line-height:1.8; }
            .resultado a {
                display:inline-block; margin-top:20px; padding:14px 25px;
                background:linear-gradient(90deg,#087fce,#7139c9);
                color:white; text-decoration:none; border-radius:10px;
            }
        </style>
    </head>
    <body>
        <div class="resultado">
            <h1>¡Compra registrada!</h1>
            <p>Gracias, ' . escapar($nombre) . '.</p>
            <p>Tu número de pedido es: <strong>#' . $pedidoId . '</strong></p>
            <p>Total de la compra: <strong>$' . number_format($total, 2) . '</strong></p>
            <p>Estado: Pendiente</p>
            <a href="index.php">Volver a NovaStore</a>
        </div>

        <script>
            localStorage.removeItem("novastore_carrito");
        </script>
    </body>
    </html>';

} catch (Exception $error) {

    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }

    mostrarResultado(
        "No se pudo completar la compra",
        $error->getMessage()
    );
}
