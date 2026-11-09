
<?php
require_once "conexion.php";

// Consultar productos y sus vendedores
$sql = "SELECT p.*, v.nombre AS vendedor
        FROM productos p
        INNER JOIN vendedores v ON p.vendedor_id = v.id
        ORDER BY p.id DESC";

$consulta = $conexion->query($sql);
$productos = $consulta->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>NovaStore | Tienda de Tecnología</title>

    <link rel="stylesheet" href="css/estilos.css">
    <script src="js/funciones.js" defer></script>
</head>

<body>

<header class="encabezado">

    <div class="contenedor navegacion">

        <a href="index.php" class="logo">
            <span class="logo-icono">N</span>
            Nova<span>Store</span>
        </a>

        <nav class="menu">
            <a href="index.php" class="activo">Inicio</a>
            <a href="#productos">Productos</a>
            <a href="vendedores.php">Vendedores</a>
            <a href="admin.php">Administración</a>
            <a href="gestionar_pedidos.php">Gestionar pedidos</a>
        </nav>

        <a href="carrito.php" class="carrito">
            🛒 Carrito
            <span id="contador-carrito">0</span>
        </a>

    </div>

</header>

<main>

<section class="hero">

    <div class="contenedor hero-contenido">

        <div class="hero-texto">

            <span class="etiqueta">TECNOLOGÍA PARA TODOS</span>

            <h1>
                Encuentra lo que necesitas.
                <span>Compra inteligentemente.</span>
            </h1>

            <p>
                Descubre productos tecnológicos, compara precios
                y compra directamente a nuestros vendedores.
            </p>

            <a href="#productos" class="boton-principal">
                Explorar productos →
            </a>

        </div>

        <div class="hero-texto">

            <img src="imagenes/principal.jpg"
                 alt="Imagen principal de NovaStore"
                 style="width:100%; max-width:1350px; height:550px; object-fit:contain; display:block;">

        </div>

    </div>

</section>

<section class="contenedor catalogo" id="productos">

    <div class="titulo-seccion">

        <div>
            <span class="subtitulo">NUESTRO CATÁLOGO</span>
            <h2>Productos destacados</h2>
            <p>Encuentra los mejores productos disponibles.</p>
        </div>

        <div class="total-productos">
            <?= count($productos) ?> productos disponibles
        </div>

    </div>

    <div class="productos-grid">

        <?php if (count($productos) > 0): ?>

            <?php foreach ($productos as $producto): ?>

                <?php
                $imagen = $producto['imagen'] ?? '';
                $ruta = "imagenes/" . basename($imagen);
                $stock = (int)$producto['stock'];
                ?>

                <article class="producto-card">

                    <div class="producto-imagen">

                        <?php if (!empty($imagen) && file_exists($ruta)): ?>

                            <img src="<?= htmlspecialchars($ruta) ?>"
                                 alt="<?= htmlspecialchars($producto['nombre']) ?>">

                        <?php else: ?>

                            <div class="imagen-placeholder">📦</div>

                        <?php endif; ?>

                        <span class="disponible">
                            Disponible
                        </span>

                    </div>

                    <div class="producto-info">

                        <span class="categoria">TECNOLOGÍA</span>

                        <h3>
                            <?= htmlspecialchars($producto['nombre']) ?>
                        </h3>

                        <p class="descripcion">
                            <?= htmlspecialchars($producto['descripcion']) ?>
                        </p>

                        <div class="vendedor">
                            <span>Vendido por:</span>
                            <strong>
                                <?= htmlspecialchars($producto['vendedor']) ?>
                            </strong>
                        </div>

                        <div class="producto-pie">

                            <div class="precio">
                                <small>Precio</small>
                                $<?= number_format($producto['precio'], 2) ?>
                            </div>

                            <button
                                type="button"
                                class="boton-comprar"
                                data-id="<?= (int)$producto['id'] ?>"
                                data-nombre="<?= htmlspecialchars($producto['nombre'], ENT_QUOTES, 'UTF-8') ?>"
                                data-precio="<?= (float)$producto['precio'] ?>"
                                data-imagen="<?= htmlspecialchars($ruta, ENT_QUOTES, 'UTF-8') ?>"
                                data-stock="<?= $stock ?>"
                                <?= $stock <= 0 ? 'disabled' : '' ?>>

                                <?= $stock <= 0 ? 'Agotado' : '+ Agregar' ?>

                            </button>

                        </div>

                        <div class="stock">
                            Stock disponible: <?= $stock ?>
                        </div>

                    </div>

                </article>

            <?php endforeach; ?>

        <?php else: ?>

            <p>No hay productos registrados todavía.</p>

        <?php endif; ?>

    </div>

</section>

</main>

<footer class="footer">

    <div class="contenedor">
        <h3>NovaStore</h3>

        <p>Tu tienda de tecnología en un solo lugar.</p>

        <p>© <?= date("Y") ?> NovaStore. Todos los derechos reservados.</p>
    </div>

</footer>

</body>
</html>
