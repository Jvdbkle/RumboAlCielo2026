// HU-22: mostrador de entrega. Valida el código sin recargar la página y responde con
// una alerta visual (verde o roja) y un sonido. Si el navegador lo permite, también lee con la cámara.
(function () {
    'use strict';

    var formulario = document.getElementById('mostrador');
    var campo = document.getElementById('codigo');
    var panel = document.getElementById('resultado');
    var titulo = document.getElementById('resultado-titulo');
    var detalle = document.getElementById('resultado-detalle');
    if (!formulario || !campo || !panel) {
        return;
    }

    var contexto = null;
    function pitar(correcto) {
        try {
            var Audio = window.AudioContext || window.webkitAudioContext;
            contexto = contexto || new Audio();
            var tonos = correcto ? [880] : [220, 180];
            tonos.forEach(function (frecuencia, i) {
                var oscilador = contexto.createOscillator();
                var volumen = contexto.createGain();
                oscilador.frequency.value = frecuencia;
                oscilador.type = correcto ? 'sine' : 'square';
                volumen.gain.value = 0.15;
                oscilador.connect(volumen);
                volumen.connect(contexto.destination);
                var inicio = contexto.currentTime + i * 0.28;
                oscilador.start(inicio);
                oscilador.stop(inicio + (correcto ? 0.25 : 0.4));
            });
        } catch (e) { /* sin sonido disponible */ }
    }

    function mostrar(correcto, textoTitulo, textoDetalle) {
        panel.hidden = false;
        panel.className = 'mostrador-resultado ' + (correcto ? 'ok' : 'error');
        titulo.textContent = textoTitulo;
        detalle.textContent = textoDetalle;
        pitar(correcto);
    }

    var enCurso = false;
    function validar(codigo) {
        if (enCurso || codigo.trim() === '') {
            return;
        }
        enCurso = true;
        var datos = new FormData(formulario);
        datos.set('codigo', codigo.trim());
        fetch(formulario.action, { method: 'POST', body: datos, credentials: 'same-origin' })
            .then(function (respuesta) { return respuesta.json(); })
            .then(function (r) {
                if (r.ok) {
                    mostrar(true, r.nombre, 'Entrega registrada a las ' + r.hora);
                } else {
                    mostrar(false, 'Código rechazado', r.mensaje);
                }
            })
            .catch(function () {
                mostrar(false, 'Sin respuesta', 'No se pudo validar el código. Revisa la conexión e inténtalo de nuevo.');
            })
            .then(function () {
                enCurso = false;
                campo.value = '';
                campo.focus();
            });
    }

    formulario.addEventListener('submit', function (evento) {
        evento.preventDefault();
        validar(campo.value);
    });

    // Cámara (solo si el navegador sabe leer códigos QR).
    var botonCamara = document.getElementById('usar-camara');
    var video = document.getElementById('camara');
    if (botonCamara && video && 'BarcodeDetector' in window && navigator.mediaDevices) {
        botonCamara.hidden = false;
        botonCamara.addEventListener('click', function () {
            var detector = new window.BarcodeDetector({ formats: ['qr_code'] });
            navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } }).then(function (flujo) {
                video.srcObject = flujo;
                video.hidden = false;
                video.play();
                botonCamara.hidden = true;
                var ultimo = '';
                var ultimaVez = 0;
                (function leer() {
                    detector.detect(video).then(function (codigos) {
                        var ahora = Date.now();
                        if (codigos.length > 0 && (codigos[0].rawValue !== ultimo || ahora - ultimaVez > 4000)) {
                            ultimo = codigos[0].rawValue;
                            ultimaVez = ahora;
                            validar(ultimo);
                        }
                    }).catch(function () {}).then(function () { setTimeout(leer, 300); });
                })();
            }).catch(function () {
                mostrar(false, 'Cámara no disponible', 'Escribe el código a mano o usa un lector USB.');
            });
        });
    }
})();