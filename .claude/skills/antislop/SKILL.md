---
name: antislop
description: Filtro contra el "slop" de IA en Los Primos Ramírez - texto de interfaz genérico o inflado, diseño de plantilla (degradados, brillos, emojis, tarjetas de adorno), código de relleno (comentarios obvios, try/catch inútiles, abstracciones de más, archivos de resumen) y reportes vacíos. Úsala siempre antes de dar por terminado un cambio en vistas, textos, CSS, controladores o modelos, y al revisar trabajo hecho por IA.
---

# Antislop

"Slop" es lo que se nota hecho en automático: suena bien, no dice nada y no se parece al resto del sistema.
Este sistema lo usa personal de una distribuidora de frutas y verduras, desde el teléfono, con prisa.
Cada palabra, adorno o línea de código tiene que ganarse su lugar.

Regla general: **si se puede quitar sin que nadie lo extrañe, se quita.**

## 1. Texto de interfaz

Prohibido:
- Saludos y relleno de marketing: "¡Bienvenido a tu panel!", "Gestiona tu negocio de forma eficiente",
  "Potencia tus ventas", "Todo en un solo lugar", "Experiencia", "solución integral", "sin complicaciones".
- Exclamaciones de celebración: "¡Listo! 🎉", "¡Genial!", "¡Excelente trabajo!". El toast dice el hecho:
  "Venta registrada.", "Caja cerrada con $12.50 de diferencia."
- Emojis en cualquier parte (vistas, mensajes, PDF, CSV, comentarios). Se usa `<x-icon>`.
- Subtítulos que repiten el título ("Proveedores — Lista de proveedores"). El subtítulo dice para qué sirve
  o qué hacer: "Quién te surte la mercancía. Se eligen al registrar una compra."
- Palabras vacías: "simplemente", "fácilmente", "rápidamente", "de manera sencilla", "potente", "intuitivo",
  "robusto", "optimizado", "inteligente", "mágico", "seamless".
- Tecnicismos al usuario: "registro", "entidad", "ID", "null", "error 500", "operación exitosa", "procesando solicitud".
  Se dice qué pasó y qué hacer: "Ese producto ya tiene ventas. Desactívalo en su lugar."
- Mayúsculas de título en inglés ("Nueva Venta De Contado"). En español: "Nueva venta de contado".
- Tríadas de adorno ("rápido, fácil y seguro") y listas de beneficios.
- Anglicismos con equivalente común: "dashboard" en texto visible → "Inicio" o "Resumen"; "stock" se acepta
  porque el personal lo usa; "click" → "toca" o "presiona".

Bien:
- Verbo + objeto en botones: "Registrar venta", "Guardar proveedor", "Cerrar caja".
- Estados vacíos que llevan a la acción: "Todavía no hay compras. Registra la primera."
- Cifras concretas en vez de adjetivos: "3 productos agotados" en vez de "algunos productos requieren atención".

## 2. Diseño

Prohibido (son la firma de una plantilla genérica):
- Degradados de adorno (`bg-gradient-*`, `from-purple-*`, `via-*`), sobre todo violeta/índigo/rosa.
  La paleta es `stone` + verde/naranja/rojo (+ azul para información).
- Vidrio esmerilado y brillos: `backdrop-blur` fuera del nav/modal, `shadow-2xl`, sombras de color
  (`shadow-green-500/50`), `ring` decorativos, `blur-3xl` de fondo, "glow".
- Animaciones gratuitas: `animate-bounce`, `animate-pulse` en cosas que no están cargando, `hover:scale-105`
  en tarjetas, transiciones largas. Se permite el loader y el `spinner`.
- Tarjetas de héroe con ícono gigante en círculo de color, "stats" inventadas o decorativas, ilustraciones
  de relleno, insignias "Nuevo"/"Pro"/"Beta".
- Bordes muy redondeados (`rounded-xl`, `rounded-2xl`, `rounded-3xl`, píldoras `rounded-full` en etiquetas o
  filtros). Todo va `rounded-md` con las piezas de `app.css`; `rounded-full` solo en avatares y puntos.
- Tarjetas de cifras con franja de color arriba (`border-t-4 border-…`) o ícono decorativo en la esquina.
- Íconos dentro de cuadros de color (`w-11 h-11 rounded-xl bg-…`) para adornar accesos o cifras.
- Encabezados armados a mano o anchos de página distintos por vista: siempre `<x-encabezado>` y `pagina` / `pagina-angosta`.
- Cadenas largas de Tailwind copiadas: si existe pieza (`tarjeta`, `btn-primario`, `insignia-*`, `aviso-*`), se usa.
- Íconos en cada título, cada etiqueta y cada celda. El ícono acompaña acciones y estados, no decora.
- Colores sin su par `dark:`.

## 3. Código

Prohibido:
- Comentarios que repiten el código (`// Guardar el cliente` sobre `$cliente->save()`), separadores de
  ASCII, comentarios de "paso 1/paso 2", o "// Actualizado por IA". Comentar solo el **por qué** de una regla
  de negocio que no se ve en el código.
- `try { … } catch (\Exception $e) { return back()->with('error', 'Ocurrió un error'); }` que traga el error real.
  Errores esperados se validan antes; los inesperados se dejan subir (Laravel los registra).
- Validaciones duplicadas (la regla ya está en `$request->validate()` y se repite a mano).
- Abstracciones para un solo uso: interfaces, repositorios, DTOs, "managers", "helpers" genéricos o servicios
  nuevos cuando un método en el modelo o en `App\Support` basta. Revisa primero lo que ya existe
  (`Moneda`, `Cantidad`, `Contacto`, `Almacen`, `SqlFecha`, `AlertasInventarioService`, `ActividadService`).
- Código "por si acaso": parámetros opcionales que nadie usa, ramas para casos imposibles, `?? 0` encadenados
  que esconden datos faltantes, configuraciones sin uso.
- Código muerto o comentado, `dd()`, `dump()`, `console.log`, `Log::info('aquí')`.
- Nombres genéricos: `$data`, `$result`, `$item`, `$temp`, `procesar()`, `handleData()`. Nombres en español que
  digan qué es: `$ventasDelDia`, `$saldoPendiente`, `calcularEfectivoEsperado()`.
- Mezclar inglés y español en nombres nuevos.
- Archivos que nadie pidió: `RESUMEN.md`, `CAMBIOS.md`, `NOTAS_IA.md`, `README` por carpeta, scripts de prueba
  sueltos en la raíz. Lo temporal va al scratchpad, no al proyecto.
- Pruebas de relleno: `assertTrue(true)`, pruebas que solo verifican que una ruta devuelve 200 sin mirar datos,
  o que copian la implementación. Cada prueba demuestra una regla de negocio (ver `IntegridadHistorialTest`).
- Reescribir archivos completos para un cambio pequeño, o "mejorar" formato/estilo de código que no se tocó.

## 4. Reportes al usuario

- Decir qué cambió, dónde y cómo se verificó. Sin "¡Perfecto!", "¡Excelente pregunta!", ni resúmenes
  que repiten la lista de archivos.
- No afirmar "funciona" sin haberlo probado; si algo no se probó, decirlo.
- No inventar cifras, rutas, nombres de columnas ni funciones: comprobar en el código.

## Revisión antes de terminar

1. Relee cada texto nuevo de interfaz como si fueras el encargado con el teléfono en la mano: ¿dice qué pasa
   y qué hacer, sin adornos?
2. Busca señales automáticas en lo que tocaste (ajusta la ruta a tus archivos):
   ```bash
   # Emojis y símbolos decorativos en vistas, código y textos (LC_ALL hace falta en Windows)
   LC_ALL=C.UTF-8 grep -rnP "[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}]" resources/views app lang
   # Diseño de plantilla
   grep -rnE "bg-gradient|from-(purple|indigo|pink|violet)|backdrop-blur|shadow-2xl|blur-3xl|animate-bounce|hover:scale-1|rounded-(xl|2xl|3xl)|border-t-4|hover:shadow-md" resources/views resources/css
   grep -rnE 'class="max-w-[0-9a-z]+ mx-auto px-4' resources/views   # contenedor a mano en vez de .pagina
   # Frases de relleno
   grep -rniE "bienvenid[oa] a|de forma (eficiente|sencilla)|f[aá]cilmente|simplemente|potencia|todo en un solo lugar|operaci[oó]n exitosa|ocurri[oó] un error" resources/views app lang
   # Restos de depuración
   grep -rnE "\b(dd|dump|ray)\(|console\.log|Log::(info|debug)\('" app resources/views resources/js
   ```
   Un resultado no siempre es error: el `backdrop-blur` del nav y del fondo del modal de confirmación están
   permitidos. Cualquier otro se justifica o se quita.
3. Mira el diff completo (`git diff`) buscando comentarios obvios, código muerto y archivos que nadie pidió.
4. Sigue con la skill `probar-cambios`.
