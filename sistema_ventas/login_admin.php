
<?php
session_start();
require_once "conexion.php";

if (isset($_SESSION["administrador_id"])) {
    header("Location: admin.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $usuario = trim($_POST["usuario"] ?? "");
    $contrasena = $_POST["contrasena"] ?? "";

    if ($usuario === "" || $contrasena === "") {
        $error = "Completa todos los campos.";
    } else {
        try {
            $sql = "SELECT id, usuario, contrasena
                    FROM administradores
                    WHERE usuario = ?
                    LIMIT 1";

            $consulta = $conexion->prepare($sql);
            $consulta->execute([$usuario]);

            $admin = $consulta->fetch(PDO::FETCH_ASSOC);

            if ($admin && password_verify($contrasena, $admin["contrasena"])) {
                session_regenerate_id(true);

                $_SESSION["administrador_id"] = $admin["id"];
                $_SESSION["administrador_usuario"] = $admin["usuario"];

                header("Location: admin.php");
                exit;
            } else {
                $error = "Usuario o contraseña incorrectos.";
            }

        } catch (PDOException $e) {
            $error = "No se pudo verificar el acceso. Comprueba la base de datos.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Acceso administrativo | NovaStore</title>

<style>
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
    font-family: Arial, sans-serif;
}

body {
    min-height: 100vh;
    background: #080d19;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.login {
    width: 100%;
    max-width: 440px;
    background: #111c30;
    border: 1px solid #243752;
    border-radius: 18px;
    padding: 35px;
}

.logo {
    text-align: center;
    font-size: 32px;
    font-weight: bold;
    color: #35d9ff;
    margin-bottom: 15px;
}

h1 {
    text-align: center;
    font-size: 24px;
    margin-bottom: 10px;
}

.descripcion {
    text-align: center;
    color: #aab6cc;
    margin-bottom: 25px;
}

label {
    display: block;
    margin: 18px 0 8px;
}

input {
    width: 100%;
    padding: 14px;
    background: #080d19;
    border: 1px solid #354b69;
    border-radius: 9px;
    color: white;
    font-size: 16px;
}

button {
    width: 100%;
    padding: 15px;
    margin-top: 25px;
    background: #087da5;
    color: white;
    border: none;
    border-radius: 9px;
    font-size: 16px;
    cursor: pointer;
}

button:hover {
    background: #06a6d4;
}

.error {
    background: #491d27;
    color: #ffb2b2;
    padding: 13px;
    border-radius: 8px;
    margin-bottom: 15px;
}

.volver {
    display: block;
    text-align: center;
    color: #35d9ff;
    margin-top: 22px;
    text-decoration: none;
}
</style>
</head>

<body>

<div class="login">

    <div class="logo">NovaStore</div>

    <h1>Acceso administrativo</h1>

    <p class="descripcion">
        Ingresa tus credenciales para administrar la tienda.
    </p>

    <?php if ($error !== ""): ?>
        <div class="error">
            <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <label>Usuario</label>
        <input type="text" name="usuario"
               autocomplete="username" required>

        <label>Contraseña</label>
        <input type="password" name="contrasena"
               autocomplete="current-password" required>

        <button type="submit">Iniciar sesión</button>

    </form>

    <a href="index.php" class="volver">← Volver a NovaStore</a>

</div>

</body>
</html>
