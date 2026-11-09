
<?php
require_once "conexion.php";

$sql = "SELECT * FROM vendedores ORDER BY id DESC";
$consulta = $conexion->query($sql);
$vendedores = $consulta->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendedores | NovaStore</title>
    <link rel="stylesheet" href="css/estilos.css">

    <style>
        body {
            background:
                radial-gradient(circle at 15% 20%, rgba(0, 153, 255, .13), transparent 35%),
                radial-gradient(circle at 85% 70%, rgba(126, 34, 206, .14), transparent 35%),
                #080d19;
            color: #fff;
            min-height: 100vh;
        }

        .vendedores-portada {
            text-align: center;
            padding: 85px 20px 55px;
        }

        .etiqueta {
            display: inline-block;
            padding: 10px 20px;
            border: 1px solid rgba(0, 195, 255, .4);
            background: rgba(0, 195, 255, .08);
            color: #32cfff;
            border-radius: 30px;
            font-size: 15px;
            letter-spacing: 2px;
            margin-bottom: 22px;
        }

        .vendedores-portada h1 {
            font-size: clamp(38px, 6vw, 65px);
            font-weight: 900;
            margin: 0 0 18px;
        }

        .vendedores-portada h1 span {
            background: linear-gradient(90deg, #00c6ff, #8b5cf6);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .vendedores-portada p {
            color: #aab7ce;
            font-size: 20px;
            max-width: 700px;
            line-height: 1.7;
            margin: auto;
        }

        .vendedores-contenedor {
            max-width: 1150px;
            margin: auto;
            padding: 20px 25px 100px;
        }

        .vendedores-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(270px, 1fr));
            gap: 30px;
            margin-top: 25px;
        }

        .vendedor-card {
            position: relative;
            overflow: hidden;
            background: linear-gradient(145deg, #151f34, #0d1424);
            border: 1px solid rgba(0, 195, 255, .2);
            border-radius: 24px;
            padding: 38px 25px;
            text-align: center;
            transition: .35s ease;
            box-shadow: 0 12px 35px rgba(0, 0, 0, .25);
        }

        .vendedor-card::before {
            content: "";
            position: absolute;
            width: 180px;
            height: 180px;
            background: rgba(0, 195, 255, .10);
            border-radius: 50%;
            top: -100px;
            right: -70px;
        }

        .vendedor-card:hover {
            transform: translateY(-10px);
            border-color: #00c6ff;
            box-shadow: 0 18px 45px rgba(0, 170, 255, .18);
        }

        .vendedor-icono {
            width: 105px;
            height: 105px;
            margin: 0 auto 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 48px;
            background: linear-gradient(135deg, #087fce, #7139c9);
            box-shadow: 0 8px 30px rgba(0, 153, 255, .25);
            border: 4px solid rgba(255, 255, 255, .1);
        }

        .vendedor-card h2 {
            font-size: 25px;
            color: #fff;
            margin-bottom: 13px;
            overflow-wrap: anywhere;
        }

        .vendedor-correo {
            color: #aab7ce;
            font-size: 16px;
            overflow-wrap: anywhere;
            margin-bottom: 25px;
        }

        .vendedor-estado {
            display: inline-block;
            padding: 9px 17px;
            border-radius: 30px;
            background: rgba(34, 197, 94, .12);
            color: #4ade80;
            font-size: 14px;
            font-weight: 700;
            border: 1px solid rgba(34, 197, 94, .25);
        }

        .vendedores-vacio {
            text-align: center;
            color: #aab7ce;
            padding: 45px;
            font-size: 20px;
        }

        @media (max-width: 600px) {
            .vendedores-portada {
                padding-top: 55px;
            }

            .vendedores-portada p {
                font-size: 17px;
            }

            .vendedor-card {
                padding: 30px 18px;
            }
        }
    </style>
</head>
<body>

<header class="encabezado">
    <div class="contenedor navegacion">
        <a href="index.php" class="logo">
            <span class="logo-icono">N</span> Nova<span>Store</span>
        </a>

        <nav class="menu">
            <a href="index.php">Inicio</a>
            <a href="index.php#productos">Productos</a>
            <a href="vendedores.php" class="activo">Vendedores</a>
        </nav>

        <a href="carrito.php" class="carrito">🛒 Carrito</a>
    </div>
</header>

<section class="vendedores-portada">
    <div class="etiqueta">NUESTRO EQUIPO</div>
    <h1>Conoce a nuestros <span>vendedores</span></h1>
    <p>
        Profesionales disponibles para ayudarte a encontrar
        los mejores productos tecnológicos en NovaStore.
    </p>
</section>

<main class="vendedores-contenedor">

    <div class="vendedores-grid">

        <?php foreach ($vendedores as $vendedor): ?>
            <article class="vendedor-card">

                <div class="vendedor-icono">👨‍💻</div>

                <h2>
                    <?= htmlspecialchars($vendedor['nombre']) ?>
                </h2>

                <p class="vendedor-correo">
                    ✉️ <?= htmlspecialchars($vendedor['correo'] ?? 'Correo no disponible') ?>
                </p>

                <span class="vendedor-estado">
                    ● Vendedor registrado
                </span>

            </article>
        <?php endforeach; ?>

    </div>

    <?php if (count($vendedores) === 0): ?>
        <div class="vendedores-vacio">
            Todavía no hay vendedores registrados.
        </div>
    <?php endif; ?>

</main>

</body>
</html>
