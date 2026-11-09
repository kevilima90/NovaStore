
<?php
require_once "conexion.php";
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Finalizar compra | NovaStore</title>

    <link rel="stylesheet" href="css/estilos.css">

    <style>
        .compra-contenedor {
            width: 90%;
            max-width: 750px;
            margin: 50px auto;
        }

        .compra-tarjeta {
            background: #151c2c;
            border: 1px solid #29364d;
            padding: 35px;
            border-radius: 18px;
        }

        .compra-tarjeta h1 {
            color: white;
            font-size: 32px;
            margin-bottom: 12px;
        }

        .compra-descripcion {
            color: #b7c3d8;
            margin-bottom: 30px;
        }

        .grupo-formulario {
            margin-bottom: 22px;
        }

        .grupo-formulario label {
            display: block;
            color: white;
            margin-bottom: 9px;
            font-weight: bold;
        }

        .grupo-formulario input,
        .grupo-formulario textarea {
            width: 100%;
            padding: 15px;
            border: 1px solid #34445f;
            border-radius: 9px;
            background: #202b40;
            color: white;
            font-size: 16px;
            box-sizing: border-box;
        }

        .grupo-formulario input:focus,
        .grupo-formulario textarea:focus {
            outline: none;
            border-color: #00bfff;
        }

        .grupo-formulario textarea {
            min-height: 100px;
            resize: vertical;
        }

        .resumen-compra {
            padding: 18px;
            background: #202b40;
            border-radius: 10px;
            color: white;
            margin-bottom: 25px;
        }

        .resumen-compra strong {
            color: #00d4ff;
            font-size: 24px;
        }

        .acciones-compra {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }

        .boton-regresar {
            padding: 14px 20px;
            background: #29364d;
            color: white;
            text-decoration: none;
            border-radius: 9px;
        }

        .boton-confirmar {
            border: none;
            cursor: pointer;
            font-size: 16px;
        }

        .boton-confirmar:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        @media(max-width: 600px) {
            .compra-tarjeta {
                padding: 22px;
            }

            .compra-tarjeta h1 {
                font-size: 26px;
            }

            .acciones-compra > * {
                width: 100%;
                text-align: center;
                box-sizing: border-box;
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
            <a href="carrito.php">Mi carrito</a>
        </div>

    </nav>
</header>

<main class="compra-contenedor">

    <div class="compra-tarjeta">

        <h1>Finalizar compra</h1>

        <p class="compra-descripcion">
            Completa tus datos para registrar tu pedido en NovaStore.
        </p>

        <div class="resumen-compra">
            <p>Tu pedido se registrará con los productos de tu carrito.</p>
            <p>El estado inicial será: <strong>Pendiente</strong></p>
        </div>

        <form
            class="formulario-compra"
            action="procesar-pedido.php"
            method="POST"
            id="formulario-compra">

            <div class="grupo-formulario">

                <label for="nombre">Nombre completo *</label>

                <input
                    type="text"
                    id="nombre"
                    name="nombre"
                    placeholder="Escribe tu nombre completo"
                    maxlength="150"
                    autocomplete="name"
                    required>

            </div>

            <div class="grupo-formulario">

                <label for="cedula">Cédula *</label>

                <input
                    type="text"
                    id="cedula"
                    name="cedula"
                    placeholder="Ingresa tu número de cédula"
                    maxlength="30"
                    autocomplete="off"
                    required>

            </div>

            <div class="grupo-formulario">

                <label for="telefono">Teléfono *</label>

                <input
                    type="tel"
                    id="telefono"
                    name="telefono"
                    placeholder="Ejemplo: 0991234567"
                    maxlength="30"
                    autocomplete="tel"
                    required>

            </div>

            <div class="grupo-formulario">

                <label for="correo">Correo electrónico *</label>

                <input
                    type="email"
                    id="correo"
                    name="correo"
                    placeholder="ejemplo@gmail.com"
                    maxlength="150"
                    autocomplete="email"
                    required>

            </div>

            <div class="grupo-formulario">

                <label for="direccion">Dirección de entrega</label>

                <textarea
                    id="direccion"
                    name="direccion"
                    maxlength="500"
                    autocomplete="street-address"
                    placeholder="Escribe tu dirección de entrega"></textarea>

            </div>

            <input
                type="hidden"
                name="carrito"
                id="carrito-json">

            <div class="acciones-compra">

                <a href="carrito.php" class="boton-regresar">
                    Regresar al carrito
                </a>

                <button
                    type="submit"
                    class="boton-principal boton-confirmar"
                    id="boton-confirmar">
                    Confirmar pedido →
                </button>

            </div>

        </form>

    </div>

</main>

<script>
document.getElementById("formulario-compra").addEventListener(
    "submit",
    function(event) {

        const boton = document.getElementById("boton-confirmar");
        const campoCarrito = document.getElementById("carrito-json");

        let carrito = [];

        try {
            carrito = JSON.parse(
                localStorage.getItem("novastore_carrito") || "[]"
            );
        } catch (error) {
            carrito = [];
        }

        if (!Array.isArray(carrito) || carrito.length === 0) {

            event.preventDefault();

            alert("Tu carrito está vacío. Agrega productos antes de comprar.");

            window.location.href = "carrito.php";

            return;
        }

        campoCarrito.value = JSON.stringify(carrito);

        if (campoCarrito.value === "") {
            event.preventDefault();
            alert("No se pudo preparar el carrito. Intenta nuevamente.");
            return;
        }

        boton.disabled = true;
        boton.textContent = "Procesando pedido...";

    }
);
</script>

</body>
</html>
