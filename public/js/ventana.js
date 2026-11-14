/*
 * Ventanas emergentes: los enlaces marcados con data-ventana abren su formulario encima de
 * la lista, sin cambiar de página. Si JavaScript no funciona, el enlace abre la página normal.
 */
(function () {
    'use strict';

    var fondo = null;
    var enlaceOrigen = null;

    function extraerFormulario(html) {
        var doc = new DOMParser().parseFromString(html, 'text/html');
        return doc.querySelector('main form');
    }

    function cerrar() {
        if (fondo === null) {
            return;
        }
        fondo.remove();
        fondo = null;
        document.body.classList.remove('sin-scroll');
        document.removeEventListener('keydown', alTeclear);
        if (enlaceOrigen !== null) {
            enlaceOrigen.focus();
            enlaceOrigen = null;
        }
    }

    function alTeclear(evento) {
        if (evento.key === 'Escape') {
            cerrar();
        }
    }

    function crearVentana() {
        fondo = document.createElement('div');
        fondo.className = 'ventana-fondo';
        fondo.innerHTML =
            '<div class="ventana" role="dialog" aria-modal="true">' +
            '<button type="button" class="ventana-cerrar" aria-label="Cerrar">&times;</button>' +
            '<div class="ventana-contenido"><p class="ventana-cargando">Cargando…</p></div>' +
            '</div>';
        document.body.appendChild(fondo);
        document.body.classList.add('sin-scroll');
        document.addEventListener('keydown', alTeclear);

        fondo.addEventListener('mousedown', function (evento) {
            if (evento.target === fondo) {
                cerrar();
            }
        });
        fondo.querySelector('.ventana-cerrar').addEventListener('click', cerrar);
    }

    function mostrar(formulario) {
        var contenido = fondo.querySelector('.ventana-contenido');
        contenido.innerHTML = '';
        contenido.appendChild(document.importNode(formulario, true));

        var nuevo = contenido.querySelector('form');
        // "Cancelar" cierra la ventana en lugar de cambiar de página.
        var cancelar = nuevo.querySelector('.botones a');
        if (cancelar) {
            cancelar.addEventListener('click', function (evento) {
                evento.preventDefault();
                cerrar();
            });
        }
        nuevo.addEventListener('submit', enviar);

        var primero = nuevo.querySelector('input:not([type=hidden]), select');
        if (primero) {
            primero.focus();
        }
    }

    function enviar(evento) {
        evento.preventDefault();
        var formulario = evento.target;
        var boton = formulario.querySelector('button[type=submit]');
        if (boton) {
            boton.disabled = true;
        }

        fetch(formulario.getAttribute('action'), {
            method: 'POST',
            credentials: 'same-origin',
            redirect: 'manual',
            body: new FormData(formulario)
        }).then(function (respuesta) {
            // Guardó bien: el servidor redirige a la lista. No se sigue la redirección aquí
            // (gastaría el aviso, que se muestra una sola vez): se recarga la lista de atrás.
            if (respuesta.type === 'opaqueredirect') {
                window.location.reload();
                return null;
            }
            return respuesta.text();
        }).then(function (html) {
            if (html === null) {
                return;
            }
            var conError = extraerFormulario(html);
            if (conError === null) {
                window.location.reload();
                return;
            }
            mostrar(conError);
        }).catch(function () {
            formulario.submit();
        });
    }

    function abrir(enlace) {
        enlaceOrigen = enlace;
        crearVentana();

        fetch(enlace.href, { credentials: 'same-origin', redirect: 'manual' }).then(function (respuesta) {
            // Sesión vencida o sin permiso: el servidor redirige, así que se abre la página normal.
            if (respuesta.type === 'opaqueredirect') {
                window.location.href = enlace.href;
                return null;
            }
            return respuesta.text();
        }).then(function (html) {
            if (html === null) {
                return;
            }
            var formulario = extraerFormulario(html);
            if (formulario === null) {
                window.location.href = enlace.href;
                return;
            }
            mostrar(formulario);
        }).catch(function () {
            window.location.href = enlace.href;
        });
    }

    document.addEventListener('click', function (evento) {
        var enlace = evento.target.closest('a[data-ventana]');
        if (enlace === null || evento.metaKey || evento.ctrlKey || evento.shiftKey) {
            return;
        }
        evento.preventDefault();
        abrir(enlace);
    });

    // Los avisos de éxito desaparecen solos a los pocos segundos.
    document.querySelectorAll('.aviso.ok').forEach(function (aviso) {
        setTimeout(function () {
            aviso.classList.add('oculto');
        }, 5000);
    });
})();