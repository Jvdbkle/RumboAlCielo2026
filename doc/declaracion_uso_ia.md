# Declaración de uso de IA

**Estudiante:** José Vidal Bautista (UAB)  
**Actividad:** Remedial ISW – Rumbo al Cielo  
**Herramienta utilizada:** Claude (Anthropic)  
**Registro de conversaciones:** `doc/bitacora.md`

Durante el desarrollo de este trabajo utilicé Claude como apoyo para avanzar con el proyecto, resolver dudas y corregir algunos errores que fueron apareciendo. También me ayudó con algunas ideas iniciales para organizar las historias de usuario, los diagramas, el código y las pruebas.

A medida que avanzaba, fui revisando los resultados, realizando cambios y probando el sistema para comprobar que estuviera de acuerdo con lo solicitado en la actividad. A continuación, detallo qué partes realicé, en cuáles recibí ayuda de la IA y cómo fui verificando el trabajo.

| Entregable | Qué hice yo | En qué me ayudó la IA | Cómo lo verifiqué |
|---|---|---|---|
| Backlog e historias de usuario | Revisé y corregí el documento, principalmente HU-01 y HU-03, y algunos criterios repetidos. | Me ayudó con los primeros borradores de las historias y sus criterios. | Comparé las historias con el enunciado de la actividad. |
| Diagramas de casos de uso | Trabajé en los diagramas de los 7 módulos en draw.io. | Me dio una primera orientación sobre los casos de uso y sus relaciones. | Revisé que los casos de uso correspondieran con las historias de usuario. |
| Diagrama de clases | Realicé los ajustes en las clases Reserva y Liquidación y sus relaciones. | Me ayudó con una propuesta inicial de clases y métodos. | Comparé el diagrama con las clases utilizadas en el código. |
| Código del sistema | Ejecuté el proyecto, revisé las pantallas, reporté errores y realicé correcciones durante el desarrollo. También fui guardando los avances en GitHub. | Me apoyó con el desarrollo de los 7 módulos y la solución de errores. | Probé el sistema en el navegador, tanto en el administrador como en el empleado. |
| Pruebas | Ejecuté las pruebas y revisé los errores que aparecieron durante el proceso. | Me ayudó a preparar las pruebas unitarias. | Ejecuté PHPUnit y al finalizar obtuve 144 pruebas satisfactorias. |
| README y documentación | Revisé el contenido y lo adapté al proyecto. | Me ayudó con el primer borrador del README. | Comprobé que la información correspondiera con el contenido del proyecto. |

**Observaciones:** Durante el desarrollo también tomé algunas decisiones sobre el funcionamiento del sistema, como permitir cancelar una reserva y realizar otra el mismo día, siempre que se cumplan las condiciones establecidas. También decidí no agregar una pantalla de inicio porque no estaba contemplada en el backlog.

Al finalizar, quedaron pendientes de mostrar en pantalla los avisos correspondientes a HU-20 y HU-12, que por el momento están contemplados como eventos del dominio. El escaneo QR con cámara también depende de la compatibilidad del navegador con `BarcodeDetector`.