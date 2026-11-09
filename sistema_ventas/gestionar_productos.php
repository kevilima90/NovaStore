
<?php
require_once "proteger.php";
require_once "conexion.php";

$mensaje = "";
$error = "";

// Procesar formularios
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $accion = $_POST["accion"] ?? "";

    // REGISTRAR O ACTUALIZAR
    if ($accion === "guardar") {

        $id = (int)($_POST["id"] ?? 0);
        $vendedor_id = (int)($_POST["vendedor_id"] ?? 0);
        $nombre = trim($_POST["nombre"] ?? "");
        $descripcion = trim($_POST["descripcion"] ?? "");
        $precio = (float)($_POST["precio"] ?? 0);
        $stock = (int)($_POST["stock"] ?? 0);

        // Imagen anterior, si estamos editando
        $imagen = "";

        if ($id > 0) {
            $stmt = $conexion->prepare(
                "SELECT imagen FROM productos WHERE id = ?"
            );
            $stmt->execute([$id]);
            $anterior = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$anterior) {
                $error = "El producto que intentas editar no existe.";
            } else {
                $imagen = $anterior["imagen"] ?? "";
            }
        }

        if (
            !$error &&
            ($vendedor_id <= 0 || $nombre === "" ||
             $precio < 0 || $stock < 0)
        ) {
            $error = "Completa correctamente los campos obligatorios.";
        }

        // Procesar imagen seleccionada
        if (!$error && isset($_FILES["imagen"]) &&
            $_FILES["imagen"]["error"] !== UPLOAD_ERR_NO_FILE) {

            if ($_FILES["imagen"]["error"] !== UPLOAD_ERR_OK) {
                $error = "Ocurrió un error al subir la imagen.";
            } elseif ($_FILES["imagen"]["size"] > 5 * 1024 * 1024) {
                $error = "La imagen no puede superar los 5 MB.";
            } else {

                $tiposPermitidos = [
                    "image/jpeg" => "jpg",
                    "image/png" => "png",
                    "image/webp" => "webp"
                ];

                $tipo = mime_content_type($_FILES["imagen"]["tmp_name"]);

                if (!isset($tiposPermitidos[$tipo]) ||
                    getimagesize($_FILES["imagen"]["tmp_name"]) === false) {

                    $error = "Formato no permitido. Usa JPG, PNG o WEBP.";

                } else {

                    $carpeta = __DIR__ . "/imagenes/";

                    if (!is_dir($carpeta)) {
                        mkdir($carpeta, 0755, true);
                    }

                    $nombreArchivo = "producto_" .
                        bin2hex(random_bytes(12)) . "." .
                        $tiposPermitidos[$tipo];

                    if (move_uploaded_file(
                        $_FILES["imagen"]["tmp_name"],
                        $carpeta . $nombreArchivo
                    )) {
                        $imagen = $nombreArchivo;
                    } else {
                        $error = "No se pudo guardar la imagen.";
                    }
                }
            }
        }

        // Guardar producto
        if (!$error) {
            try {

                if ($id > 0) {

                    $sql = "UPDATE productos
                            SET vendedor_id = ?,
                                nombre = ?,
                                descripcion = ?,
                                precio = ?,
                                imagen = ?,
                                stock = ?
                            WHERE id = ?";

                    $stmt = $conexion->prepare($sql);
                    $stmt->execute([
                        $vendedor_id,
                        $nombre,
                        $descripcion,
                        $precio,
                        $imagen,
                        $stock,
                        $id
                    ]);

                    $mensaje = "Producto actualizado correctamente.";

                } else {

                    $sql = "INSERT INTO productos
                            (vendedor_id, nombre, descripcion, precio, imagen, stock)
                            VALUES (?, ?, ?, ?, ?, ?)";

                    $stmt = $conexion->prepare($sql);
                    $stmt->execute([
                        $vendedor_id,
                        $nombre,
                        $descripcion,
                        $precio,
                        $imagen,
                        $stock
                    ]);

                    $mensaje = "Producto registrado correctamente.";
                }

            } catch (PDOException $e) {
                $error = "No se pudo guardar el producto. Verifica los datos.";
            }
        }
    }

    // ELIMINAR PRODUCTO
    if ($accion === "eliminar") {

        $id = (int)($_POST["id"] ?? 0);

        try {
            $stmt = $conexion->prepare(
                "DELETE FROM productos WHERE id = ?"
            );

            $stmt->execute([$id]);

            $mensaje = "Producto eliminado correctamente.";

        } catch (PDOException $e) {
            $error = "No se puede eliminar porque el producto está relacionado con un pedido.";
        }
    }
}

// PRODUCTO PARA EDITAR
$productoEditar = null;

if (isset($_GET["editar"])) {

    $id = (int)$_GET["editar"];

    $stmt = $conexion->prepare(
        "SELECT * FROM productos WHERE id = ?"
    );

    $stmt->execute([$id]);
    $productoEditar = $stmt->fetch(PDO::FETCH_ASSOC);
}

// VENDEDORES
$vendedores = $conexion->query(
    "SELECT id, nombre FROM vendedores ORDER BY nombre ASC"
)->fetchAll(PDO::FETCH_ASSOC);

// PRODUCTOS
$sql = "SELECT p.*, v.nombre AS vendedor
        FROM productos p
        INNER JOIN vendedores v ON p.vendedor_id = v.id
        ORDER BY p.id DESC";

$productos = $conexion->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Gestionar productos | NovaStore</title>

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
    max-width: 1200px;
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
    color: #38d9ff;
    margin: 0;
}

h2 {
    color: #38d9ff;
}

a {
    color: #38d9ff;
    text-decoration: none;
}

.boton {
    display: inline-block;
    padding: 11px 17px;
    border: none;
    border-radius: 8px;
    background: #087ea4;
    color: white;
    cursor: pointer;
    font-weight: bold;
    text-decoration: none;
}

.boton:hover {
    background: #08a9d8;
}

.formulario,
.tabla-contenedor {
    background: #111a2d;
    padding: 25px;
    border-radius: 14px;
    margin-bottom: 30px;
    border: 1px solid #263b59;
}

.campos {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 17px;
}

.campo label {
    display: block;
    margin-bottom: 8px;
    color: #cbd5e1;
}

input,
select,
textarea {
    width: 100%;
    padding: 12px;
    border-radius: 7px;
    border: 1px solid #344763;
    background: #080f20;
    color: white;
    font-size: 15px;
}

input[type="file"] {
    background: #17243b;
}

textarea {
    resize: vertical;
}

.completo {
    grid-column: 1 / -1;
}

.acciones {
    margin-top: 20px;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.mensaje {
    background: #123c31;
    padding: 14px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.error {
    background: #4a2028;
    padding: 14px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.tabla-scroll {
    overflow-x: auto;
}

table {
    width: 100%;
    min-width: 850px;
    border-collapse: collapse;
}

th,
td {
    padding: 14px;
    border-bottom: 1px solid #263b59;
    text-align: left;
}

th {
    color: #38d9ff;
    background: #17243b;
}

.imagen {
    width: 70px;
    height: 65px;
    object-fit: contain;
    background: white;
    border-radius: 7px;
}

.eliminar {
    background: #b83248;
}

small {
    color: #aebdd0;
    display: block;
    margin-top: 8px;
}

@media(max-width: 650px) {
    body {
        padding: 15px;
    }

    .campos {
        grid-template-columns: 1fr;
    }

    .completo {
        grid-column: auto;
    }
}
</style>
</head>

<body>

<div class="contenedor">

    <div class="encabezado">
        <div>
            <h1>NovaStore</h1>
            <p>Administración de productos</p>
        </div>

        <a href="admin.php" class="boton">Volver al panel</a>
    </div>

    <?php if ($mensaje): ?>
        <div class="mensaje">
            <?= htmlspecialchars($mensaje) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="formulario">

        <h2>
            <?= $productoEditar ? "Editar producto" : "Registrar nuevo producto" ?>
        </h2>

        <form method="POST" enctype="multipart/form-data">

            <input type="hidden" name="accion" value="guardar">

            <input type="hidden" name="id"
                value="<?= $productoEditar ? (int)$productoEditar["id"] : 0 ?>">

            <div class="campos">

                <div class="campo">
                    <label>Nombre del producto *</label>

                    <input type="text"
                        name="nombre"
                        required
                        value="<?= htmlspecialchars($productoEditar["nombre"] ?? "") ?>">
                </div>

                <div class="campo">
                    <label>Vendedor *</label>

                    <select name="vendedor_id" required>

                        <option value="">Seleccionar vendedor</option>

                        <?php foreach ($vendedores as $v): ?>

                            <option value="<?= (int)$v["id"] ?>"
                                <?= isset($productoEditar["vendedor_id"]) &&
                                $productoEditar["vendedor_id"] == $v["id"]
                                ? "selected" : "" ?>>

                                <?= htmlspecialchars($v["nombre"]) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>
                </div>

                <div class="campo completo">
                    <label>Descripción</label>

                    <textarea name="descripcion" rows="3"><?= htmlspecialchars($productoEditar["descripcion"] ?? "") ?></textarea>
                </div>

                <div class="campo">
                    <label>Precio ($) *</label>

                    <input type="number"
                        name="precio"
                        min="0"
                        step="0.01"
                        required
                        value="<?= htmlspecialchars((string)($productoEditar["precio"] ?? "")) ?>">
                </div>

                <div class="campo">
                    <label>Stock *</label>

                    <input type="number"
                        name="stock"
                        min="0"
                        required
                        value="<?= htmlspecialchars((string)($productoEditar["stock"] ?? "0")) ?>">
                </div>

                <div class="campo completo">

                    <label>Fotografía del producto</label>

                    <input type="file"
                        name="imagen"
                        accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">

                    <small>
                        Formatos permitidos: JPG, PNG y WEBP. Máximo 5 MB.
                        Si no seleccionas otra imagen al editar, se conservará la anterior.
                    </small>

                    <?php if ($productoEditar && !empty($productoEditar["imagen"])): ?>

                        <p>Imagen actual:</p>

                        <img
                            src="imagenes/<?= rawurlencode(basename($productoEditar["imagen"])) ?>"
                            class="imagen"
                            alt="Imagen actual">

                    <?php endif; ?>

                </div>

            </div>

            <div class="acciones">

                <button type="submit" class="boton">
                    <?= $productoEditar ? "Guardar cambios" : "Registrar producto" ?>
                </button>

                <?php if ($productoEditar): ?>

                    <a href="gestionar_productos.php" class="boton">
                        Cancelar edición
                    </a>

                <?php endif; ?>

            </div>

        </form>

    </div>

    <div class="tabla-contenedor">

        <h2>Productos registrados</h2>

        <div class="tabla-scroll">

            <table>

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Imagen</th>
                        <th>Producto</th>
                        <th>Vendedor</th>
                        <th>Precio</th>
                        <th>Stock</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody>

                <?php foreach ($productos as $p): ?>

                    <tr>

                        <td><?= (int)$p["id"] ?></td>

                        <td>

                            <?php if (!empty($p["imagen"])): ?>

                                <img
                                    class="imagen"
                                    src="imagenes/<?= rawurlencode(basename($p["imagen"])) ?>"
                                    alt="Producto">

                            <?php else: ?>

                                Sin imagen

                            <?php endif; ?>

                        </td>

                        <td><?= htmlspecialchars($p["nombre"]) ?></td>

                        <td><?= htmlspecialchars($p["vendedor"]) ?></td>

                        <td>
                            $<?= number_format((float)$p["precio"], 2) ?>
                        </td>

                        <td><?= (int)$p["stock"] ?></td>

                        <td>

                            <a class="boton"
                                href="gestionar_productos.php?editar=<?= (int)$p["id"] ?>">
                                Editar
                            </a>

                            <form method="POST"
                                style="display:inline"
                                onsubmit="return confirm('¿Seguro que deseas eliminar este producto?');">

                                <input type="hidden" name="accion" value="eliminar">

                                <input type="hidden" name="id"
                                    value="<?= (int)$p["id"] ?>">

                                <button type="submit" class="boton eliminar">
                                    Eliminar
                                </button>

                            </form>

                        </td>

                    </tr>

                <?php endforeach; ?>

                <?php if (empty($productos)): ?>

                    <tr>
                        <td colspan="7">
                            Todavía no hay productos registrados.
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
