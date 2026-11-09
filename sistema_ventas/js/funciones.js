
// ======================================
// NOVASTORE - CARRITO DE COMPRAS
// ======================================

function obtenerCarrito() {
    try {
        return JSON.parse(localStorage.getItem("novastore_carrito")) || [];
    } catch (error) {
        return [];
    }
}

function guardarCarrito(carrito) {
    localStorage.setItem("novastore_carrito", JSON.stringify(carrito));
    actualizarContador();
}

function actualizarContador() {
    const carrito = obtenerCarrito();

    const total = carrito.reduce((suma, producto) => {
        return suma + Number(producto.cantidad || 0);
    }, 0);

    const contador = document.getElementById("contador-carrito");

    if (contador) {
        contador.textContent = total;
    }
}

function agregarAlCarrito(id, nombre, precio, imagen, stock) {
    let carrito = obtenerCarrito();

    id = Number(id);
    precio = Number(precio);
    stock = Number(stock);

    const productoExistente = carrito.find(
        producto => Number(producto.id) === id
    );

    const cantidadActual = productoExistente
        ? Number(productoExistente.cantidad)
        : 0;

    if (cantidadActual >= stock) {
        alert("No hay más unidades disponibles de este producto.");
        return;
    }

    if (productoExistente) {
        productoExistente.cantidad++;
    } else {
        carrito.push({
            id: id,
            nombre: nombre,
            precio: precio,
            imagen: imagen,
            cantidad: 1,
            stock: stock
        });
    }

    guardarCarrito(carrito);

    alert("¡Producto agregado correctamente al carrito!");
}

// Conectar los botones de todos los productos
document.addEventListener("DOMContentLoaded", function() {

    actualizarContador();

    const botones = document.querySelectorAll(".boton-comprar");

    botones.forEach(function(boton) {

        boton.addEventListener("click", function() {

            agregarAlCarrito(
                boton.dataset.id,
                boton.dataset.nombre,
                boton.dataset.precio,
                boton.dataset.imagen,
                boton.dataset.stock
            );

        });

    });

});
