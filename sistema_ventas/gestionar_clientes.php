
<?php
require_once "proteger.php";
require_once "conexion.php";

// Seguridad: token CSRF
if (empty($_SESSION["token_clientes"])) {
    $_SESSION["token_clientes"] = bin2hex(random_bytes(32));
}

$token = $_SESSION["token_clientes"];
$mensaje = $_SESSION["mensaje_clientes"] ?? "";
$tipoMensaje = $_SESSION["tipo_mensaje_clientes"] ?? "info";

unset($_SESSION["mensaje_clientes"], $_SESSION["tipo_mensaje_clientes"]);

// Función para mostrar texto seguro
function e($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, "UTF-8");
}

// Procesar acciones
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (
        !isset($_POST["token"]) ||
        !hash_equals($_SESSION["token_clientes"], $_POST["token"])
    ) {
        die("Solicitud no válida. Recarga la página e inténtalo nuevamente.");
    }

    $accion = $_POST["accion"] ?? "";

    // EDITAR CLIENTE
    if ($accion === "editar") {

        $id = (int)($_POST["id"] ?? 0);
        $nombre = trim($_POST["nombre"] ?? "");
        $cedula = trim($_POST["cedula"] ?? "");
        $telefono = trim($_POST["telefono"] ?? "");
        $correo = trim($_POST["correo"] ?? "");
        $direccion = trim($_POST["direccion"] ?? "");

        if (
            $id <= 0 ||
            $nombre === "" ||
            $cedula === "" ||
            $telefono === "" ||
            $correo === "" ||
            $direccion === ""
        ) {
            $_SESSION["mensaje_clientes"] = "Completa todos los campos.";
            $_SESSION["tipo_mensaje_clientes"] = "error";

        } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {

            $_SESSION["mensaje_clientes"] = "Ingresa un correo electrónico válido.";
            $_SESSION["tipo_mensaje_clientes"] = "error";

        } else {

            $stmt = $conexion->prepare(
                "UPDATE clientes
                 SET nombre = ?,
                     cedula = ?,
                     telefono = ?,
                     correo = ?,
                     direccion = ?
                 WHERE id = ?"
            );

            $stmt->execute([
                $nombre,
                $cedula,
                $telefono,
                $correo,
                $direccion,
                $id
            ]);

            $_SESSION["mensaje_clientes"] = "Los datos del cliente se actualizaron correctamente.";
            $_SESSION["tipo_mensaje_clientes"] = "exito";
        }

        header("Location: gestionar_clientes.php");
        exit;
    }

    // ELIMINAR CLIENTE
    if ($accion === "eliminar") {

        $id = (int)($_POST["id"] ?? 0);

        if ($id <= 0) {

            $_SESSION["mensaje_clientes"] = "El cliente seleccionado no es válido.";
            $_SESSION["tipo_mensaje_clientes"] = "error";

        } else {

            // Comprobar si el cliente tiene pedidos
            $stmt = $conexion->prepare(
                "SELECT COUNT(*) FROM pedidos WHERE cliente_id = ?"
            );

            $stmt->execute([$id]);
            $totalPedidos = (int)$stmt->fetchColumn();

            if ($totalPedidos > 0) {

                $_SESSION["mensaje_clientes"] =
                    "No se puede eliminar este cliente porque tiene pedidos registrados.";

                $_SESSION["tipo_mensaje_clientes"] = "error";

            } else {

                $stmt = $conexion->prepare(
                    "DELETE FROM clientes WHERE id = ?"
                );

                $stmt->execute([$id]);

                if ($stmt->rowCount() > 0) {
                    $_SESSION["mensaje_clientes"] = "Cliente eliminado correctamente.";
                    $_SESSION["tipo_mensaje_clientes"] = "exito";
                } else {
                    $_SESSION["mensaje_clientes"] = "No se encontró el cliente.";
                    $_SESSION["tipo_mensaje_clientes"] = "error";
                }
            }
        }

        header("Location: gestionar_clientes.php");
        exit;
    }
}

// Buscar clientes
$busqueda = trim($_GET["buscar"] ?? "");

$sql = "SELECT
            c.*,
            COUNT(p.id) AS total_pedidos,
            COALESCE(SUM(
                CASE
                    WHEN p.estado != 'Cancelado' THEN p.total
                    ELSE 0
                END
            ), 0) AS total_compras
        FROM clientes c
        LEFT JOIN pedidos p ON p.cliente_id = c.id";

if ($busqueda !== "") {
    $sql .= " WHERE c.nombre LIKE :buscar
              OR c.cedula LIKE :buscar
              OR c.correo LIKE :buscar";
}

$sql .= " GROUP BY c.id ORDER BY c.id DESC";

$stmt = $conexion->prepare($sql);

if ($busqueda !== "") {
    $stmt->bindValue(":buscar", "%" . $busqueda . "%");
}

$stmt->execute();
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Cliente que se va a editar
$clienteEditar = null;

if (isset($_GET["editar"])) {

    $idEditar = (int)$_GET["editar"];

    $stmt = $conexion->prepare(
        "SELECT * FROM clientes WHERE id = ?"
    );

    $stmt->execute([$idEditar]);
    $clienteEditar = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Historial del cliente
$historial = [];
$clienteSeleccionado = null;

if (isset($_GET["historial"])) {

    $clienteId = (int)$_GET["historial"];

    $stmt = $conexion->prepare(
        "SELECT * FROM clientes WHERE id = ?"
    );

    $stmt->execute([$clienteId]);
    $clienteSeleccionado = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($clienteSeleccionado) {

        $stmt = $conexion->prepare(
            "SELECT * FROM pedidos
             WHERE cliente_id = ?
             ORDER BY fecha DESC"
        );

        $stmt->execute([$clienteId]);
        $historial = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Gestionar clientes | NovaStore</title>

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
    max-width: 1250px;
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
    color: #38d9ff;
}

.panel {
    background: #111a2d;
    padding: 25px;
    border-radius: 14px;
    border: 1px solid #263b59;
    margin-bottom: 25px;
}

.boton {
    display: inline-block;
    padding: 11px 17px;
    border-radius: 8px;
    background: #087ea4;
    color: white;
    text-decoration: none;
    border: none;
    cursor: pointer;
    font-weight: bold;
    font-size: 14px;
}

.boton:hover {
    background: #08a9d8;
}

.boton-editar {
    background: #805ad5;
}

.boton-editar:hover {
    background: #6b46c1;
}

.boton-eliminar {
    background: #c0394b;
}

.boton-eliminar:hover {
    background: #e3344b;
}

.boton-secundario {
    background: #344763;
}

input {
    padding: 12px;
    border-radius: 7px;
    border: 1px solid #344763;
    background: #080f20;
    color: white;
    width: 100%;
    font-size: 15px;
}

.campo {
    margin-bottom: 16px;
}

.campo label {
    display: block;
    margin-bottom: 7px;
    color: #b8c9df;
    font-weight: bold;
}

.formulario {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.formulario-busqueda input {
    width: 320px;
}

.formulario-editar {
    max-width: 650px;
}

.acciones {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
}

.tabla-scroll {
    overflow-x: auto;
}

table {
    width: 100%;
    min-width: 1050px;
    border-collapse: collapse;
}

th, td {
    padding: 14px;
    border-bottom: 1px solid #263b59;
    text-align: left;
}

th {
    color: #38d9ff;
    background: #17243b;
}

.enlace {
    color: #38d9ff;
    text-decoration: none;
}

.enlace:hover {
    text-decoration: underline;
}

.datos {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.dato {
    background: #17243b;
    padding: 18px;
    border-radius: 9px;
    overflow-wrap: anywhere;
}

.dato strong {
    color: #38d9ff;
}

.mensaje {
    padding: 15px;
    border-radius: 9px;
    margin-bottom: 20px;
    font-weight: bold;
}

.exito {
    background: #123e32;
    border: 1px solid #20a879;
    color: #8fffd0;
}

.error {
    background: #4a2029;
    border: 1px solid #d94b5b;
    color: #ffb6bf;
}

.info {
    background: #172f49;
    border: 1px solid #38a6d8;
    color: #a9e4ff;
}

@media(max-width: 600px) {
    body {
        padding: 15px;
    }

    .panel {
        padding: 17px;
    }

    .formulario-busqueda input {
        width: 100%;
    }

    .formulario-busqueda {
        flex-direction: column;
    }
}
</style>

</head>

<body>

<div class="contenedor">

    <div class="encabezado">

        <div>
            <h1>NovaStore</h1>
            <p>Administración de clientes</p>
        </div>

        <a href="admin.php" class="boton">
            Volver al panel
        </a>

    </div>

    <?php if ($mensaje !== ""): ?>

        <div class="mensaje <?= e($tipoMensaje) ?>">
            <?= e($mensaje) ?>
        </div>

    <?php endif; ?>


    <!-- FORMULARIO PARA EDITAR -->

    <?php if ($clienteEditar): ?>

        <div class="panel">

            <h2>Editar cliente</h2>

            <p>Modifica la información del cliente seleccionado.</p>

            <form method="POST" class="formulario-editar">

                <input type="hidden" name="token" value="<?= e($token) ?>">
                <input type="hidden" name="accion" value="editar">
                <input type="hidden" name="id" value="<?= (int)$clienteEditar["id"] ?>">

                <div class="campo">
                    <label>Nombre completo</label>
                    <input
                        type="text"
                        name="nombre"
                        value="<?= e($clienteEditar["nombre"]) ?>"
                        required>
                </div>

                <div class="campo">
                    <label>Cédula</label>
                    <input
                        type="text"
                        name="cedula"
                        value="<?= e($clienteEditar["cedula"]) ?>"
                        required>
                </div>

                <div class="campo">
                    <label>Teléfono</label>
                    <input
                        type="text"
                        name="telefono"
                        value="<?= e($clienteEditar["telefono"]) ?>"
                        required>
                </div>

                <div class="campo">
                    <label>Correo electrónico</label>
                    <input
                        type="email"
                        name="correo"
                        value="<?= e($clienteEditar["correo"]) ?>"
                        required>
                </div>

                <div class="campo">
                    <label>Dirección</label>
                    <input
                        type="text"
                        name="direccion"
                        value="<?= e($clienteEditar["direccion"]) ?>"
                        required>
                </div>

                <button type="submit" class="boton boton-editar">
                    Guardar cambios
                </button>

                <a href="gestionar_clientes.php" class="boton boton-secundario">
                    Cancelar
                </a>

            </form>

        </div>

    <?php endif; ?>


    <!-- HISTORIAL DEL CLIENTE -->

    <?php if ($clienteSeleccionado): ?>

        <div class="panel">

            <h2>Información del cliente</h2>

            <div class="datos">

                <div class="dato">
                    <strong>Nombre</strong>
                    <p><?= e($clienteSeleccionado["nombre"]) ?></p>
                </div>

                <div class="dato">
                    <strong>Cédula</strong>
                    <p><?= e($clienteSeleccionado["cedula"]) ?></p>
                </div>

                <div class="dato">
                    <strong>Teléfono</strong>
                    <p><?= e($clienteSeleccionado["telefono"]) ?></p>
                </div>

                <div class="dato">
                    <strong>Correo</strong>
                    <p><?= e($clienteSeleccionado["correo"]) ?></p>
                </div>

                <div class="dato">
                    <strong>Dirección</strong>
                    <p><?= e($clienteSeleccionado["direccion"]) ?></p>
                </div>

            </div>

            <h2>Historial de compras</h2>

            <div class="tabla-scroll">

                <table>

                    <thead>
                        <tr>
                            <th>Pedido</th>
                            <th>Fecha</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Detalle</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php foreach ($historial as $pedido): ?>

                        <tr>
                            <td>#<?= (int)$pedido["id"] ?></td>

                            <td><?= e($pedido["fecha"]) ?></td>

                            <td>
                                $<?= number_format((float)$pedido["total"], 2) ?>
                            </td>

                            <td><?= e($pedido["estado"]) ?></td>

                            <td>
                                <a class="enlace"
                                   href="detalle_pedido.php?id=<?= (int)$pedido["id"] ?>">
                                    Ver pedido
                                </a>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                    <?php if (empty($historial)): ?>

                        <tr>
                            <td colspan="5">
                                Este cliente todavía no tiene compras.
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

            <br>

            <a href="gestionar_clientes.php" class="boton">
                Volver a clientes
            </a>

        </div>

    <?php endif; ?>


    <!-- LISTA DE CLIENTES -->

    <div class="panel">

        <h2>Clientes registrados</h2>

        <form method="GET" class="formulario formulario-busqueda">

            <input
                type="text"
                name="buscar"
                placeholder="Buscar por nombre, cédula o correo"
                value="<?= e($busqueda) ?>">

            <button type="submit" class="boton">
                Buscar
            </button>

            <a href="gestionar_clientes.php" class="boton boton-secundario">
                Limpiar
            </a>

        </form>

        <br>

        <div class="tabla-scroll">

            <table>

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Cédula</th>
                        <th>Teléfono</th>
                        <th>Correo</th>
                        <th>Pedidos</th>
                        <th>Total compras</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody>

                <?php foreach ($clientes as $cliente): ?>

                    <tr>

                        <td><?= (int)$cliente["id"] ?></td>

                        <td><?= e($cliente["nombre"]) ?></td>

                        <td><?= e($cliente["cedula"]) ?></td>

                        <td><?= e($cliente["telefono"]) ?></td>

                        <td><?= e($cliente["correo"]) ?></td>

                        <td><?= (int)$cliente["total_pedidos"] ?></td>

                        <td>
                            $<?= number_format((float)$cliente["total_compras"], 2) ?>
                        </td>

                        <td>

                            <div class="acciones">

                                <a
                                    class="boton boton-editar"
                                    href="gestionar_clientes.php?editar=<?= (int)$cliente["id"] ?>">
                                    Editar
                                </a>

                                <a
                                    class="enlace"
                                    href="gestionar_clientes.php?historial=<?= (int)$cliente["id"] ?>">
                                    Historial
                                </a>

                                <form
                                    method="POST"
                                    onsubmit="return confirm('¿Estás seguro de eliminar este cliente? Esta acción no se puede deshacer.');">

                                    <input type="hidden" name="token" value="<?= e($token) ?>">
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="id" value="<?= (int)$cliente["id"] ?>">

                                    <button type="submit" class="boton boton-eliminar">
                                        Eliminar
                                    </button>

                                </form>

                            </div>

                        </td>

                    </tr>

                <?php endforeach; ?>

                <?php if (empty($clientes)): ?>

                    <tr>
                        <td colspan="8">
                            No se encontraron clientes.
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
