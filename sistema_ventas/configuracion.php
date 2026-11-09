
<?php
require_once "proteger.php";
require_once "conexion.php";

$mensaje = "";
$error = "";

// Obtener configuración
$consulta = $conexion->query(
    "SELECT * FROM configuracion ORDER BY id ASC LIMIT 1"
);

$config = $consulta->fetch(PDO::FETCH_ASSOC);

// Guardar cambios
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nombre = trim($_POST["nombre_tienda"] ?? "");
    $correo = trim($_POST["correo"] ?? "");
    $telefono = trim($_POST["telefono"] ?? "");
    $direccion = trim($_POST["direccion"] ?? "");
    $moneda = trim($_POST["moneda"] ?? "USD");

    if ($nombre === "") {
        $error = "El nombre de la tienda es obligatorio.";
    } elseif ($correo !== "" && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = "El correo electrónico no es válido.";
    } else {

        $actualizar = $conexion->prepare(
            "UPDATE configuracion
             SET nombre_tienda = ?,
                 correo = ?,
                 telefono = ?,
                 direccion = ?,
                 moneda = ?
             WHERE id = ?"
        );

        $actualizar->execute([
            $nombre,
            $correo,
            $telefono,
            $direccion,
            $moneda,
            $config["id"]
        ]);

        $mensaje = "La configuración se guardó correctamente.";

        $consulta = $conexion->query(
            "SELECT * FROM configuracion ORDER BY id ASC LIMIT 1"
        );

        $config = $consulta->fetch(PDO::FETCH_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Configuración | NovaStore</title>

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
    max-width: 850px;
    margin: auto;
}

.encabezado {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 25px;
}

h1, h2 {
    color: #38d9ff;
}

.panel {
    background: #111a2d;
    border: 1px solid #263b59;
    border-radius: 15px;
    padding: 30px;
}

label {
    display: block;
    margin: 18px 0 8px;
    color: #dce8f5;
    font-weight: bold;
}

input, textarea, select {
    width: 100%;
    padding: 13px;
    border: 1px solid #344966;
    border-radius: 8px;
    background: #080d1b;
    color: white;
    font-size: 16px;
}

textarea {
    resize: vertical;
    min-height: 100px;
}

.boton {
    display: inline-block;
    padding: 13px 20px;
    margin-top: 22px;
    border: none;
    border-radius: 8px;
    background: #087ea4;
    color: white;
    text-decoration: none;
    font-weight: bold;
    cursor: pointer;
    font-size: 15px;
}

.boton:hover {
    background: #08a9d8;
}

.mensaje {
    padding: 14px;
    background: #124b37;
    color: #a8ffd5;
    border-radius: 8px;
    margin-bottom: 15px;
}

.error {
    padding: 14px;
    background: #632b36;
    color: #ffd0d6;
    border-radius: 8px;
    margin-bottom: 15px;
}

@media(max-width: 600px) {
    body {
        padding: 15px;
    }

    .panel {
        padding: 20px;
    }
}
</style>
</head>

<body>

<div class="contenedor">

    <div class="encabezado">
        <div>
            <h1>NovaStore</h1>
            <p>Configuración general de la tienda</p>
        </div>

        <a href="admin.php" class="boton">Volver al panel</a>
    </div>

    <div class="panel">

        <h2>Datos del negocio</h2>

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

        <form method="POST">

            <label>Nombre de la tienda</label>
            <input
                type="text"
                name="nombre_tienda"
                value="<?= htmlspecialchars($config["nombre_tienda"]) ?>"
                required
            >

            <label>Correo electrónico</label>
            <input
                type="email"
                name="correo"
                value="<?= htmlspecialchars($config["correo"] ?? "") ?>"
            >

            <label>Teléfono</label>
            <input
                type="text"
                name="telefono"
                value="<?= htmlspecialchars($config["telefono"] ?? "") ?>"
            >

            <label>Dirección del negocio</label>
            <textarea name="direccion"><?= htmlspecialchars($config["direccion"] ?? "") ?></textarea>

            <label>Moneda</label>
            <select name="moneda">
                <option value="USD" <?= $config["moneda"] === "USD" ? "selected" : "" ?>>
                    Dólar estadounidense (USD)
                </option>
            </select>

            <button type="submit" class="boton">
                Guardar configuración
            </button>

        </form>

    </div>

</div>

</body>
</html>
