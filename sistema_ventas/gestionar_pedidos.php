
<?php
require_once "proteger.php";
require_once "conexion.php";

// Estados permitidos
$estadosPermitidos = [
    "Pendiente",
    "Preparando",
    "Enviado",
    "Entregado",
    "Cancelado"
];

// Token de seguridad
if (empty($_SESSION["token_pedidos"])) {
    $_SESSION["token_pedidos"] = bin2hex(random_bytes(32));
}

$token = $_SESSION["token_pedidos"];

$mensaje = $_SESSION["mensaje_pedidos"] ?? "";
$tipoMensaje = $_SESSION["tipo_mensaje_pedidos"] ?? "info";

unset($_SESSION["mensaje_pedidos"], $_SESSION["tipo_mensaje_pedidos"]);

$error = "";

function e($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, "UTF-8");
}

// ACTUALIZAR ESTADO
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (
        !isset($_POST["token"]) ||
        !hash_equals($_SESSION["token_pedidos"], $_POST["token"])
    ) {
        die("Solicitud no válida. Recarga la página.");
    }

    $pedido_id = filter_input(
        INPUT_POST,
        "pedido_id",
        FILTER_VALIDATE_INT
    );

    $nuevo_estado = $_POST["estado"] ?? "";

    if (!$pedido_id || !in_array($nuevo_estado, $estadosPermitidos, true)) {

        $_SESSION["mensaje_pedidos"] = "Los datos enviados no son válidos.";
        $_SESSION["tipo_mensaje_pedidos"] = "error";

    } else {

        try {

            $consulta = $conexion->prepare(
                "UPDATE pedidos SET estado = ? WHERE id = ?"
            );

            $consulta->execute([$nuevo_estado, $pedido_id]);

            if ($consulta->rowCount() >= 0) {

                $verificar = $conexion->prepare(
                    "SELECT id FROM pedidos WHERE id = ?"
                );

                $verificar->execute([$pedido_id]);

                if ($verificar->fetch()) {
                    $_SESSION["mensaje_pedidos"] =
                        "El estado del pedido #".$pedido_id." se actualizó correctamente.";

                    $_SESSION["tipo_mensaje_pedidos"] = "exito";
                } else {
                    $_SESSION["mensaje_pedidos"] = "El pedido no existe.";
                    $_SESSION["tipo_mensaje_pedidos"] = "error";
                }
            }

        } catch (PDOException $e) {

            $_SESSION["mensaje_pedidos"] = "No se pudo actualizar el pedido.";
            $_SESSION["tipo_mensaje_pedidos"] = "error";
        }
    }

    header("Location: gestionar_pedidos.php");
    exit;
}

// FILTROS
$busqueda = trim($_GET["buscar"] ?? "");
$filtroEstado = trim($_GET["estado"] ?? "");

if (!in_array($filtroEstado, $estadosPermitidos, true)) {
    $filtroEstado = "";
}

// ESTADÍSTICAS GENERALES
$estadisticas = $conexion->query("
    SELECT
        COUNT(*) AS total_pedidos,

        COALESCE(SUM(
            CASE WHEN estado = 'Pendiente' THEN 1 ELSE 0 END
        ), 0) AS pendientes,

        COALESCE(SUM(
            CASE WHEN estado = 'Preparando' THEN 1 ELSE 0 END
        ), 0) AS preparando,

        COALESCE(SUM(
            CASE WHEN estado = 'Enviado' THEN 1 ELSE 0 END
        ), 0) AS enviados,

        COALESCE(SUM(
            CASE WHEN estado = 'Entregado' THEN 1 ELSE 0 END
        ), 0) AS entregados,

        COALESCE(SUM(
            CASE WHEN estado = 'Cancelado' THEN 1 ELSE 0 END
        ), 0) AS cancelados,

        COALESCE(SUM(
            CASE WHEN estado != 'Cancelado' THEN total ELSE 0 END
        ), 0) AS ventas

    FROM pedidos
")->fetch(PDO::FETCH_ASSOC);

// CONSULTAR PEDIDOS
$sql = "
    SELECT
        p.id,
        p.fecha,
        p.total,
        p.estado,
        c.nombre AS cliente,
        c.cedula,
        c.telefono,
        c.correo,
        c.direccion

    FROM pedidos p

    INNER JOIN clientes c ON p.cliente_id = c.id

    WHERE 1=1
";

$parametros = [];

if ($busqueda !== "") {

    $sql .= "
        AND (
            CAST(p.id AS CHAR) LIKE ?
            OR c.nombre LIKE ?
            OR c.cedula LIKE ?
            OR c.correo LIKE ?
        )
    ";

    $termino = "%" . $busqueda . "%";

    $parametros[] = $termino;
    $parametros[] = $termino;
    $parametros[] = $termino;
    $parametros[] = $termino;
}

if ($filtroEstado !== "") {
    $sql .= " AND p.estado = ?";
    $parametros[] = $filtroEstado;
}

$sql .= " ORDER BY p.id DESC";

$consulta = $conexion->prepare($sql);
$consulta->execute($parametros);

$pedidos = $consulta->fetchAll(PDO::FETCH_ASSOC);

$totalResultados = count($pedidos);
?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Gestión de pedidos | NovaStore</title>

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
    gap: 15px;
    margin-bottom: 30px;
}

h1 {
    color: #35d9ff;
    margin-bottom: 8px;
}

.subtitulo {
    color: #aab6cc;
}

.botones {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.boton {
    display: inline-block;
    background: #087da5;
    color: white;
    text-decoration: none;
    padding: 12px 17px;
    border-radius: 9px;
    border: none;
    cursor: pointer;
    font-weight: bold;
}

.boton:hover {
    background: #08a9d8;
}

.boton-gris {
    background: #344763;
}

.estadisticas {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 18px;
    margin-bottom: 25px;
}

.tarjeta {
    background: #111c30;
    padding: 23px;
    border-radius: 14px;
    border: 1px solid #243752;
}

.tarjeta p {
    color: #aab6cc;
    margin-bottom: 12px;
    font-size: 14px;
}

.tarjeta h2 {
    color: #35d9ff;
    font-size: 29px;
}

.tarjeta.ventas h2 {
    color: #54e6a5;
}

.panel {
    background: #111c30;
    padding: 25px;
    border-radius: 14px;
    border: 1px solid #243752;
    margin-bottom: 25px;
}

.panel h2 {
    color: #35d9ff;
    margin-bottom: 20px;
}

.filtros {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 20px;
}

input, select {
    background: #080d19;
    color: white;
    padding: 12px;
    border: 1px solid #344763;
    border-radius: 8px;
    font-size: 14px;
}

input {
    width: 320px;
    max-width: 100%;
}

select {
    min-width: 180px;
}

.resultados {
    color: #aab6cc;
    margin-bottom: 18px;
}

.tabla-scroll {
    overflow-x: auto;
}

table {
    width: 100%;
    min-width: 1100px;
    border-collapse: collapse;
}

th, td {
    padding: 14px 12px;
    text-align: left;
    border-bottom: 1px solid #283850;
}

th {
    color: #35d9ff;
    background: #17243a;
}

.estado {
    display: inline-block;
    padding: 7px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
}

.pendiente {
    background: #59461b;
    color: #ffd36c;
}

.preparando {
    background: #273b67;
    color: #9ebcff;
}

.enviado {
    background: #174c60;
    color: #7cdeff;
}

.entregado {
    background: #164b36;
    color: #81f0b3;
}

.cancelado {
    background: #582631;
    color: #ffabb7;
}

.form-estado {
    min-width: 145px;
}

.form-estado select {
    width: 100%;
    min-width: 130px;
}

.form-estado button {
    width: 100%;
    margin-top: 7px;
}

button {
    background: #087da5;
    color: white;
    border: none;
    padding: 10px 13px;
    border-radius: 7px;
    cursor: pointer;
}

button:hover {
    background: #08a9d8;
}

.detalle {
    color: #35d9ff;
    text-decoration: none;
    font-weight: bold;
}

.detalle:hover {
    text-decoration: underline;
}

.mensaje {
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 18px;
}

.exito {
    background: #123b2d;
    color: #8df0bb;
    border: 1px solid #20a879;
}

.error {
    background: #491d27;
    color: #ffb2b2;
    border: 1px solid #d94b5b;
}

.info {
    background: #172f49;
    color: #a9e4ff;
    border: 1px solid #38a6d8;
}

@media(max-width: 600px) {

    body {
        padding: 15px;
    }

    .panel {
        padding: 16px;
    }

    input, select {
        width: 100%;
    }

    .filtros {
        flex-direction: column;
    }

    .botones {
        width: 100%;
    }

    .boton {
        text-align: center;
    }
}

</style>

</head>

<body>

<div class="contenedor">

    <!-- ENCABEZADO -->

    <div class="encabezado">

        <div>
            <h1>NovaStore | Pedidos</h1>
            <p class="subtitulo">
                Administra y controla las compras de tus clientes.
            </p>
        </div>

        <div class="botones">

            <a href="admin.php" class="boton">
                ← Volver al panel
            </a>

            <a href="cerrar_sesion.php" class="boton boton-gris">
                Cerrar sesión
            </a>

        </div>

    </div>


    <!-- MENSAJES -->

    <?php if ($mensaje !== ""): ?>

        <div class="mensaje <?= e($tipoMensaje) ?>">
            <?= e($mensaje) ?>
        </div>

    <?php endif; ?>

    <?php if ($error !== ""): ?>

        <div class="mensaje error">
            <?= e($error) ?>
        </div>

    <?php endif; ?>


    <!-- ESTADÍSTICAS -->

    <div class="estadisticas">

        <div class="tarjeta">
            <p>Total de pedidos</p>
            <h2><?= (int)$estadisticas["total_pedidos"] ?></h2>
        </div>

        <div class="tarjeta">
            <p>Pendientes</p>
            <h2><?= (int)$estadisticas["pendientes"] ?></h2>
        </div>

        <div class="tarjeta">
            <p>Preparando</p>
            <h2><?= (int)$estadisticas["preparando"] ?></h2>
        </div>

        <div class="tarjeta">
            <p>Enviados</p>
            <h2><?= (int)$estadisticas["enviados"] ?></h2>
        </div>

        <div class="tarjeta">
            <p>Entregados</p>
            <h2><?= (int)$estadisticas["entregados"] ?></h2>
        </div>

        <div class="tarjeta">
            <p>Cancelados</p>
            <h2><?= (int)$estadisticas["cancelados"] ?></h2>
        </div>

        <div class="tarjeta ventas">
            <p>Ventas no canceladas</p>
            <h2>$<?= number_format((float)$estadisticas["ventas"], 2) ?></h2>
        </div>

    </div>


    <!-- FILTROS Y LISTADO -->

    <div class="panel">

        <h2>Listado de compras</h2>

        <form method="GET" class="filtros">

            <input
                type="text"
                name="buscar"
                placeholder="Buscar pedido, cliente, cédula o correo"
                value="<?= e($busqueda) ?>">

            <select name="estado">

                <option value="">Todos los estados</option>

                <?php foreach ($estadosPermitidos as $estado): ?>

                    <option
                        value="<?= e($estado) ?>"
                        <?= $filtroEstado === $estado ? "selected" : "" ?>>

                        <?= e($estado) ?>

                    </option>

                <?php endforeach; ?>

            </select>

            <button type="submit" class="boton">
                Buscar
            </button>

            <a href="gestionar_pedidos.php" class="boton boton-gris">
                Limpiar filtros
            </a>

        </form>

        <p class="resultados">
            Pedidos encontrados: <strong><?= $totalResultados ?></strong>
        </p>


        <div class="tabla-scroll">

            <table>

                <thead>

                    <tr>
                        <th>N.º Pedido</th>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Cédula</th>
                        <th>Teléfono</th>
                        <th>Correo</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Actualizar</th>
                        <th>Detalles</th>
                    </tr>

                </thead>

                <tbody>

                <?php if (!empty($pedidos)): ?>

                    <?php foreach ($pedidos as $pedido): ?>

                        <?php

                        $claseEstado = match ($pedido["estado"]) {
                            "Pendiente" => "pendiente",
                            "Preparando" => "preparando",
                            "Enviado" => "enviado",
                            "Entregado" => "entregado",
                            "Cancelado" => "cancelado",
                            default => ""
                        };

                        ?>

                        <tr>

                            <td>
                                <strong>#<?= (int)$pedido["id"] ?></strong>
                            </td>

                            <td><?= e($pedido["fecha"]) ?></td>

                            <td><?= e($pedido["cliente"]) ?></td>

                            <td><?= e($pedido["cedula"]) ?></td>

                            <td><?= e($pedido["telefono"]) ?></td>

                            <td><?= e($pedido["correo"]) ?></td>

                            <td>
                                <strong>
                                    $<?= number_format((float)$pedido["total"], 2) ?>
                                </strong>
                            </td>

                            <td>

                                <span class="estado <?= $claseEstado ?>">
                                    <?= e($pedido["estado"]) ?>
                                </span>

                            </td>

                            <td>

                                <form method="POST" class="form-estado">

                                    <input
                                        type="hidden"
                                        name="token"
                                        value="<?= e($token) ?>">

                                    <input
                                        type="hidden"
                                        name="pedido_id"
                                        value="<?= (int)$pedido["id"] ?>">

                                    <select name="estado" required>

                                        <?php foreach ($estadosPermitidos as $estado): ?>

                                            <option
                                                value="<?= e($estado) ?>"
                                                <?= $pedido["estado"] === $estado ? "selected" : "" ?>>

                                                <?= e($estado) ?>

                                            </option>

                                        <?php endforeach; ?>

                                    </select>

                                    <button type="submit">
                                        Guardar
                                    </button>

                                </form>

                            </td>

                            <td>

                                <a
                                    class="detalle"
                                    href="detalle_pedido.php?id=<?= (int)$pedido["id"] ?>">

                                    Ver detalles

                                </a>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="10">
                            No se encontraron pedidos con esos filtros.
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
