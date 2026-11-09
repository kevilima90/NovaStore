
<?php
require_once "conexion.php";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi carrito | NovaStore</title>
    <link rel="stylesheet" href="css/estilos.css">

    <style>
        .carrito-contenedor {
            width: 90%;
            max-width: 1100px;
            margin: 50px auto;
        }

        .carrito-titulo {
            font-size: 36px;
            margin-bottom: 30px;
            color: white;
        }

        .producto-carrito {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 22px;
            margin-bottom: 18px;
            background: #151c2c;
            border: 1px solid #29364d;
            border-radius: 15px;
        }

        .producto-carrito img {
            width: 110px;
            height: 110px;
            object-fit: contain;
            background: #202b40;
            border-radius: 10px;
        }

        .producto-info {
            flex: 1;
        }

        .producto-info h3 {
            color: white;
            margin-bottom: 10px;
        }

        .producto-info p {
            color: #b7c3d8;
            margin: 5px 0;
        }

        .controles-cantidad {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 12px;
        }

        .controles-cantidad button {
            width: 35px;
            height: 35px;
            border: none;
            border-radius: 8px;
            background: #087cf0;
            color: white;
            font-size: 20px;
            cursor: pointer;
        }

        .eliminar-producto {
            background: #dc3545 !important;
            padding: 12px 18px;
            border: none;
            border-radius: 8px;
            color: white;
            cursor: pointer;
        }

        .resumen-carrito {
            background: #151c2c;
            border: 1px solid #29364d;
            border-radius: 15px;
            padding: 25px;
            margin-top: 30px;
            text-align: right;
        }

        .total-carrito {
            font-size: 28px;
            color: #00d4ff;
            margin-bottom: 20px;
        }

        .botones-carrito {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            flex-wrap: wrap;
        }

        .boton-volver {
            display: inline-block;
            padding: 14px 22px;
            background: #29364d;
            color: white;
            text-decoration: none;
            border-radius: 9px;
        }

        .carrito-vacio {
            text-align: center;
            padding: 60px 20px;
            background: #151c2c;
            border-radius: 15px;
            color: white;
        }

        @media(max-width: 650px) {
            .producto-carrito {
                flex-wrap: wrap;
            }

            .producto-carrito img {
                width: 85px;
                height: 85px;
            }

            .eliminar-producto {
                width: 100%;
            }
        }
    </style>
</head>

<body>

<header>
    <nav class="navbar">
        <a href="index.php" class="logo">NovaStore</a>

        <div class="menu">
            <a href="index.php">Inicio</a>
            <a href="index.php#productos">Productos</a>
            <a href="vendedores.php">Vendedores</a>
            <a href="carrito.php">
                Carrito (<span id="contador-carrito">0</span>)
            </a>
        </div>
    </nav>
</header>

<main class="carrito-contenedor">

    <h1 class="carrito-titulo">Mi carrito de compras</h1>

    <div id="lista-carrito"></div>

    <div id="resumen-carrito" class="resumen-carrito" style="display:none;">

        <h2 class="total-carrito">
            Total: $<span id="total-carrito">0.00</span>
        </h2>

        <div class="botones-carrito">

            <a href="index.php#productos" class="boton-volver">
                Seguir comprando
            </a>

            <button
                type="button"
                class="boton-principal"
                onclick="continuarCompra()">
                Continuar compra →
            </button>

        </div>
    </div>

</main>

<script src="js/funciones.js"></script>

<script>
function escaparHTML(texto) {
    return String(texto).replace(/[&<>"']/g, function(caracter) {
        return {
            "&": "&amp;",
            "<": "&lt;",
            ">": "&gt;",
            '"': "&quot;",
            "'": "&#039;"
        }[caracter];
    });
}

function mostrarCarrito() {

    const carrito = obtenerCarrito();
    const lista = document.getElementById("lista-carrito");
    const resumen = document.getElementById("resumen-carrito");

    actualizarContador();

    if (carrito.length === 0) {

        lista.innerHTML = `
            <div class="carrito-vacio">
                <h2>Tu carrito está vacío</h2>
                <p>¡Descubre nuestros productos tecnológicos!</p>
                <br>
                <a href="index.php#productos" class="boton-principal">
                    Explorar productos
                </a>
            </div>
        `;

        resumen.style.display = "none";
        return;
    }

    let total = 0;
    let contenido = "";

    carrito.forEach(function(producto, indice) {

        const precio = Number(producto.precio);
        const cantidad = Number(producto.cantidad);
        const subtotal = precio * cantidad;

        total += subtotal;

        const imagen = producto.imagen || "";

        contenido += `
            <div class="producto-carrito">

                <img src="${escaparHTML(imagen)}"
                     alt="${escaparHTML(producto.nombre)}">

                <div class="producto-info">

                    <h3>${escaparHTML(producto.nombre)}</h3>

                    <p>Precio: $${precio.toFixed(2)}</p>

                    <p>Subtotal: $${subtotal.toFixed(2)}</p>

                    <div class="controles-cantidad">

                        <button onclick="cambiarCantidad(${indice}, -1)">
                            -
                        </button>

                        <strong>${cantidad}</strong>

                        <button onclick="cambiarCantidad(${indice}, 1)">
                            +
                        </button>

                    </div>

                </div>

                <button
                    class="eliminar-producto"
                    onclick="eliminarProducto(${indice})">
                    Eliminar
                </button>

            </div>
        `;
    });

    lista.innerHTML = contenido;

    document.getElementById("total-carrito").textContent =
        total.toFixed(2);

    resumen.style.display = "block";
}

function cambiarCantidad(indice, cambio) {

    let carrito = obtenerCarrito();

    if (!carrito[indice]) return;

    const nuevaCantidad =
        Number(carrito[indice].cantidad) + cambio;

    if (nuevaCantidad <= 0) {
        eliminarProducto(indice);
        return;
    }

    if (nuevaCantidad > Number(carrito[indice].stock)) {
        alert("No hay suficiente stock disponible.");
        return;
    }

    carrito[indice].cantidad = nuevaCantidad;

    guardarCarrito(carrito);
    mostrarCarrito();
}

function eliminarProducto(indice) {

    let carrito = obtenerCarrito();

    if (!confirm("¿Deseas eliminar este producto del carrito?")) {
        return;
    }

    carrito.splice(indice, 1);

    guardarCarrito(carrito);
    mostrarCarrito();
}

function continuarCompra() {

    const carrito = obtenerCarrito();

    if (carrito.length === 0) {
        alert("Tu carrito está vacío.");
        return;
    }

    window.location.href = "comprar.php";
}

document.addEventListener("DOMContentLoaded", mostrarCarrito);
</script>

</body>
</html>
