/*
 * Menú semanal: zoom de las fotos y pestañas de día en pantallas pequeñas.
 * Sin JavaScript la carta se ve completa; esto solo agrega comodidad.
 */
(function () {
    'use strict';

    // ---------- Pestañas de día (celular) ----------
    var carta = document.querySelector('.carta');
    var botones = document.querySelectorAll('.pestanas-dia button');

    function activarDia(indice) {
        document.querySelectorAll('.carta-dia').forEach(function (dia) {
            dia.classList.toggle('activo', dia.getAttribute('data-dia') === String(indice));
        });
        botones.forEach(function (boton) {
            boton.classList.toggle('activa', boton.getAttribute('data-dia') === String(indice));
        });
    }

    if (carta && botones.length > 0) {
        carta.classList.add('con-pestanas');
        var inicial = 0;
        var hoyEnCarta = carta.querySelector('.carta-dia.es-hoy');
        if (hoyEnCarta) {
            inicial = Number(hoyEnCarta.getAttribute('data-dia'));
        }
        activarDia(inicial);
        botones.forEach(function (boton) {
            boton.addEventListener('click', function () {
                activarDia(boton.getAttribute('data-dia'));
            });
        });
    }

    // ---------- Zoom de fotos ----------
    var fondo = null;

    function cerrarZoom() {
        if (fondo === null) {
            return;
        }
        fondo.remove();
        fondo = null;
        document.body.classList.remove('sin-scroll');
        document.removeEventListener('keydown', alTeclear);
    }

    function alTeclear(evento) {
        if (evento.key === 'Escape') {
            cerrarZoom();
        }
    }

    function abrirZoom(ruta, pie) {
        cerrarZoom();
        fondo = document.createElement('div');
        fondo.className = 'zoom-fondo';
        fondo.setAttribute('role', 'dialog');
        fondo.setAttribute('aria-modal', 'true');

        var figura = document.createElement('figure');
        var imagen = document.createElement('img');
        imagen.src = ruta;
        imagen.alt = pie;
        var leyenda = document.createElement('figcaption');
        leyenda.textContent = pie;
        var cerrar = document.createElement('button');
        cerrar.type = 'button';
        cerrar.className = 'ventana-cerrar';
        cerrar.setAttribute('aria-label', 'Cerrar');
        cerrar.innerHTML = '&times;';

        figura.appendChild(imagen);
        figura.appendChild(leyenda);
        figura.appendChild(cerrar);
        fondo.appendChild(figura);
        document.body.appendChild(fondo);
        document.body.classList.add('sin-scroll');
        document.addEventListener('keydown', alTeclear);

        fondo.addEventListener('mousedown', function (evento) {
            if (evento.target === fondo) {
                cerrarZoom();
            }
        });
        cerrar.addEventListener('click', cerrarZoom);
    }

    document.addEventListener('click', function (evento) {
        var miniatura = evento.target.closest ? evento.target.closest('[data-zoom]') : null;
        if (miniatura) {
            abrirZoom(miniatura.getAttribute('data-zoom'), miniatura.getAttribute('data-pie') || '');
        }
    });
})();