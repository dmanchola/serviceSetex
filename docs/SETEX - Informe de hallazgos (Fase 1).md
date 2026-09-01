# **SETEX — Informe de hallazgos** 

### **Fase 1 — Discovery y auditoría técnica** 

|**Fecha de emisión**|28 de agosto de 2026|**Período auditado**|25 al 28 de agosto de 2026|
|---|---|---|---|
|**Servicio**|SETEX - Integración de parqueo con operador<br>español|**Estado**|Cierre de fase|
|**Componentes**|Servicio SOAP, base de datos de producción,<br>configuración del servidor de aplicación|**Versiones de código**|a028b2e, e74e075, 689fe8f (rama<br>serverSetexfull250826)|



## **Resumen ejecutivo** 

Desde el 2 de agosto de 2026, el sistema del operador español reenvía de forma repetida las mismas transacciones al servicio SETEX, sin detenerse. La auditoría identificó **dos problemas independientes** que conviene no mezclar, porque tienen causas distintas y se corrigen por vías distintas. 

**Problema 1 - El servicio no completa transacciones.** Se comprobó que una transacción real contra el servidor de producción no obtiene respuesta. Los datos de producción descartan la base de datos como causa y sitúan el problema en el agotamiento de la capacidad de procesamiento del servidor de aplicación. 

**Problema 2 - El formato de la respuesta cambió.** La reimplementación puesta en producción devuelve un mensaje estructuralmente distinto al de la versión anterior, aunque el dato de negocio sea el mismo. Se identificaron diez diferencias observables por el sistema receptor. Esta es la explicación más consistente del reenvío indefinido. 

##### **Resumen de hallazgos:** 

|**Id**|**Hallazgo**|**Severidad**|**Confianza**|
|---|---|---|---|
|H-01|El servicio no completa transacciones en producción|**Crítica**|**Confirmado**|
|H-02|El formato de la respuesta difiere de la versión anterior|**Alta**|**Confirmado**|
|H-03|La diferencia de formato explica el reenvío desde España|**Alta**|**Probable**|
|H-04|La base de datos no es el cuello de botella|-|**Confirmado**|
|H-05|Los procesos del servidor de aplicación están agotados|**Crítica**|**Confirmado**|
|H-06|La causa del agotamiento es la contención sobre el archivo de registro|**Crítica**|**Por confirmar**|
|H-07|Dos defectos en el componente de registro|**Media**|**Confirmado**|
|H-08|Archivos internos del servidor accesibles|**Media**|**Confirmado**|
|H-09|El servicio no deja registro de su operación|**Alta**|**Confirmado**|



**Conclusión de la fase:** Esta auditoría no entrega el incidente resuelto: entrega el incidente acotado. Se descartaron con evidencia las causas que parecían más probables al inicio, se identificó la causa vigente y se documentaron las preguntas que hoy no pueden responderse. El obstáculo de fondo está en el hallazgo H-09: **el servicio no registra lo que hace** , y sin ese registro cualquier afirmación sobre el volumen y la naturaleza de las fallas sería especulación. La sección 5 detalla qué preguntas quedan abiertas y qué se necesita para cerrarlas. 

SETEX — Auditoría Técnica de Producción | 28 de agosto de 2026 

Página 1 de 8 

**SETEX — Informe de hallazgos** 

Fase 1 - Discovery y auditoría 

## **1. Alcance y método** 

#### **1.1 Qué se analizó** 

- Historial completo del repositorio del servicio, incluidas las versiones anterior y actual. 

- Comportamiento del servicio en producción mediante peticiones controladas. 

- Esquema de la base de datos de producción, contadores de bloqueo y listado de procesos activos. 

- Configuración expuesta del servidor de aplicación. 

- Reconstrucción local de ambas versiones del servicio en entornos aislados, para comparación directa. 

#### **1.2 Fuentes de evidencia** 

|**Fuente**|**Origen**|**Fecha**|
|---|---|---|
|Repositorio de código|Acceso directo|25–28 ago|
|Respuestas del servicio en producción|Peticiones controladas al servidor productivo|26–28 ago|
|Esquema, contadores y procesos de base de datos|Entregados por el administrador de base de datos|28 ago|
|Comportamiento comparado de ambas versiones|Entorno de reconstrucción local|27–28 ago|



#### **1.3 Límites de este informe** 

En resguardo de la precisión, se declaran explícitamente los límites del trabajo realizado: 

- **No se tuvo acceso directo al servidor de aplicación.** Toda afirmación sobre su configuración se deriva de archivos accesibles o de inferencia sobre datos de la base. Los puntos que requieren verificación en el servidor están marcados como tales. 

- **No se conoce con certeza qué versión del código está desplegada en producción.** El análisis contempla las versiones candidatas y señala dónde la conclusión depende de esa variable. 

- **No existe una captura de la respuesta original del servicio anterior.** La comparación se basa en la reconstrucción local y en la documentación disponible, que presenta una contradicción señalada en la sección 5. 

- **No hubo comunicación con el operador español.** Todas las conclusiones sobre su comportamiento son inferencias a partir del patrón observado. 

## **2. El incidente** 

#### **2.1 Cronología** 

|**Fecha**|**Evento**|
|---|---|
|25 feb – 2 mar 2026|Desarrollo de la reimplementación del servicio. 37 cambios registrados.|
|2 mar – 24 ago 2026|Sin actividad en el repositorio.|
|**2 ago 2026**|**Comienza el reenvío masivo desde España.**No hay cambios de código ese día.|
|24 – 26 ago 2026|Ocho cambios correctivos de emergencia.|
|28 ago 2026|Cambio adicional sobre el componente de registro y el conteo de duplicados.|



##### **Observación Clave** 

**Observación relevante:** El incidente comienza el 2 de agosto, en una fecha sin modificaciones de código y tras cinco meses de inactividad en el repositorio. El disparador, por lo tanto, **no fue un cambio de programación sino la puesta en producción de la versión reescrita o un cambio de infraestructura asociado** . Esta observación acota el punto de partida de forma significativa. 

#### **2.2 Dos problemas independientes** 

Conviene separarlos porque su tratamiento es distinto: 

|**Aspecto**|**Problema 1**|**Problema 2**|
|---|---|---|
|**Qué ocurre**|El servicio no responde|La respuesta tiene otro formato|
|**A quién afecta**|A toda transacción entrante|Al sistema que interpreta la respuesta|
|**Causa**|Agotamiento de capacidad (H-05, H-06)|Cambio de implementación (H-02)|
|**Desde cuándo**|Verificado el 28 de agosto|Desde la puesta en producción|
|**Relación con el reenvío**|Agrava el volumen|Explica el origen|



Es importante notar que **el problema 2 explica el inicio del incidente y el problema 1 lo perpetúa** . Corregir uno solo de los dos no restablece el servicio. 

SETEX — Auditoría Técnica de Producción | 28 de agosto de 2026 

Página 2 de 8 

**SETEX — Informe de hallazgos** 

Fase 1 - Discovery y auditoría 

## **3. Hallazgos** 

Cada hallazgo indica su nivel de confianza según la siguiente escala: **Confirmado** (reproducido directamente o respaldado por datos de producción), **Probable** (consistente con evidencia sin prueba directa), **Por confirmar** (hipótesis fundamentada que requiere verificación en servidor). 

#### **H-01 — El servicio no completa transacciones en producción** 

##### **Severidad: Crítica** | **Confianza: Confirmado** 

Se ejecutó una transacción real contra el servidor productivo, con credenciales válidas. La petición no obtuvo respuesta. La consulta de versión, que no accede a la base de datos, responde de forma inmediata contra el mismo servidor. 

**Implicación:** El problema no está en la conectividad ni en la autenticación, sino en el procesamiento de las operaciones que involucran trabajo real. 

#### **H-02 — El formato de la respuesta difiere de la versión anterior** 

##### **Severidad: Alta** | **Confianza: Confirmado** 

Se identificaron **diez diferencias estructurales** entre la respuesta actual y la de la implementación anterior, detalladas en el Anexo A. Incluyen el nombre del elemento que contiene el dato de negocio, la declaración de codificación de caracteres, la ubicación y duplicación de declaraciones de espacio de nombres, el orden de atributos y varios encabezados de la comunicación. 

**Implicación:** El dato de negocio es equivalente, pero la envoltura que lo transporta no lo es. Un sistema automatizado que localice el valor por su posición o por el nombre exacto del elemento no lo encuentra. 

#### **H-03 — La diferencia de formato explica el reenvío desde España** 

**Severidad: Alta** | **Confianza: Probable** 

La versión vigente el 2 de agosto operaba en un modo que nombra el elemento de retorno de forma genérica, en lugar del nombre específico que emitía la implementación anterior. Esa es la más significativa de las diez diferencias, y su fecha de aparición coincide con el inicio del reenvío. 

El comportamiento observado —reintento indefinido de las mismas operaciones— es el que corresponde a un sistema que no logra interpretar la confirmación como exitosa y, en consecuencia, nunca da la operación por cerrada. 

**Por qué no está confirmado:** No existe una captura de la respuesta real del servicio anterior, y la documentación interna disponible se contradice a sí misma respecto de cuál era esa respuesta. La sección 5 detalla este punto. 

#### **H-04 — La base de datos no es el cuello de botella** 

##### **Severidad:** -  | **Confianza: Confirmado** 

Los datos de producción del 28 de agosto descartan las tres hipótesis que se habían formulado sobre la base de datos: 

|**Hipótesis inicial**|**Dato que la descarta**|
|---|---|
|Falta de índices en la tabla de transacciones|Los índices ya estaban aplicados, incluido uno que cubre exactamente la consulta en cuestión.|
|Volumen excesivo en la tabla de parqueos|La tabla contiene 1.638 registros reales, no los millones que sugería el contador.|
|Bloqueos de tabla por el motor de almacenamiento|La proporción de esperas es del 0,95 %, por debajo del umbral considerado normal.|



El listado de procesos es concluyente: de 314 procesos capturados, **312 se encontraban inactivos y ninguno ejecutando consultas** . La base de datos está ociosa mientras el servicio deja de responder. 

**Implicación:** Las recomendaciones previas de optimización de base de datos quedan sin efecto. El esfuerzo debe dirigirse al servidor de aplicación. 

SETEX — Auditoría Técnica de Producción | 28 de agosto de 2026 

Página 3 de 8 

**SETEX — Informe de hallazgos** 

Fase 1 - Discovery y auditoría 

#### **H-05 — Los procesos del servidor de aplicación están agotados** 

##### **Severidad: Crítica** | **Confianza: Confirmado** 

Las conexiones abiertas se distribuyen en dos servidores, con aproximadamente 152 conexiones cada uno. Ese número coincide con el límite por omisión de peticiones simultáneas del servidor web utilizado. Además, esas conexiones permanecen abiertas entre 70 y 178 segundos **sin ejecutar una sola consulta** . 

**Implicación:** Los dos servidores están operando en su techo de capacidad. Cuando se alcanza ese límite, las peticiones nuevas quedan encoladas antes de llegar a la aplicación, y el sistema emisor recibe un error de tiempo agotado sin que quede constancia en ningún registro. Esto explica por qué el incidente se retroalimenta: cada tiempo agotado genera un reintento, y cada reintento consume más capacidad. 

#### **H-06 — La causa del agotamiento es la contención sobre el archivo de registro** 

##### **Severidad: Crítica** | **Confianza: Por confirmar** 

El componente de registro de la aplicación escribe sobre un **archivo único compartido por todas las peticiones** , tomando un bloqueo exclusivo en cada escritura. Cada petición realiza entre 19 y 47 de estas operaciones, según la versión del código. Con centenares de peticiones concurrentes, las escrituras se serializan y cada proceso espera su turno. 

Existe un agravante técnico que explica por qué el límite de tiempo de ejecución no corta el problema: **ese límite no contabiliza el tiempo que un proceso pasa esperando una operación de entrada y salida** . Un proceso detenido esperando el archivo puede permanecer así indefinidamente. Esto es coherente con las conexiones observadas de hasta 178 segundos. 

**Qué falta para confirmarlo:** Verificar en el servidor si el registro de depuración está efectivamente activo. La configuración tiene ese registro **habilitado por omisión** , de modo que puede estar operando aunque se lo suponga desactivado. 

**Prueba de confirmación disponible:** Desactivar explícitamente el registro de depuración en producción. Es un cambio de una línea, reversible. Si los tiempos agotados desaparecen, la hipótesis queda demostrada. 

#### **H-07 — Dos defectos en el componente de registro** 

**Severidad: Media** | **Confianza: Confirmado** 

• **Defecto 1 - Escritura duplicada:** Cada evento de registro se escribe dos veces en el mismo archivo, por una ruta de código redundante. Esto duplica tanto el volumen generado como la cantidad de bloqueos, sin aportar información adicional. 

• **Defecto 2 - La purga automática nunca se ejecuta:** El mecanismo previsto para eliminar registros antiguos compara la antigüedad del archivo contra un período de retención, pero utiliza la fecha de última modificación, que se actualiza en cada escritura. Mientras exista tráfico, esa condición **nunca se cumple** . 

**Implicación:** El crecimiento del archivo de registro que se venía atribuyendo al volumen de escritura tiene en realidad esta causa: **el archivo nunca se purga** . Ambos defectos se corrigen con cambios menores y acotados. 

#### **H-08 — Archivos internos del servidor accesibles** 

**Severidad: Media** | **Confianza: Confirmado** 

El servidor web está configurado de manera que expone archivos que deberían permanecer internos, entre ellos el archivo de configuración que contiene las credenciales de acceso a la base de datos. 

**Alcance actual:** La exposición **no alcanza a internet abierto** . Solo es accesible desde las direcciones habilitadas en las reglas de firewall. Esto contiene el riesgo de forma significativa. 

No obstante, corresponden dos precisiones: (1) La única barrera que contiene la exposición es esa regla de firewall (no hay segunda capa); (2) Las credenciales quedaron registradas en el historial del repositorio, por lo que **su rotación es necesaria** . 

**Clasificación:** Prioridad media. No constituye una emergencia, pero no debería permanecer en este estado. La corrección es menor; la rotación requiere coordinación. 

#### **H-09 — El servicio no deja registro de su operación** 

**Severidad: Alta** | **Confianza: Confirmado** 

Este es el hallazgo del que depende todo lo demás. 

El registro de errores del lenguaje está desactivado y la presentación de errores también. En consecuencia, **cuando una petición falla no queda constancia en ningún lugar** : ni en la respuesta al cliente ni en el servidor. 

A esto se suma una limitación estructural del registro de la aplicación: una petición que queda bloqueada y muere cuando el cliente corta la conexión **no alcanza a escribir nada** . El tiempo agotado no es un evento registrado, es la ausencia de un registro. Medir las fallas a partir de ese archivo equivale a contar a los ausentes preguntando únicamente a los presentes. 

**Implicación:** No es posible cuantificar el incidente con la información disponible hoy. Esta es la razón de fondo por la que las preguntas de la sección 5 permanecen abiertas. 

SETEX — Auditoría Técnica de Producción | 28 de agosto de 2026 

Página 4 de 8 

**SETEX — Informe de hallazgos** 

Fase 1 - Discovery y auditoría 

## **4. Hipótesis descartadas** 

Esta sección documenta las líneas de investigación que fueron evaluadas y desestimadas con evidencia. Se deja constancia para evitar que se retomen y para acotar el alcance de la siguiente etapa. 

|**Hipótesis evaluada**|**Motivo del descarte**|
|---|---|
|Truncamiento de la respuesta por longitud mal<br>declarada|Se reprodujo el escenario bajo el servidor web real. El servidor recalcula el valor y la respuesta llega íntegra.<br>El problema solo aparece bajo el servidor de desarrollo, que no se usa en producción.|
|Los encabezados revelan la versión del lenguaje|La respuesta de producción no incluye ese encabezado. La configuración que lo genera está desactivada.|
|Diferencia de codificación de caracteres en la<br>respuesta exitosa|La respuesta exitosa contiene únicamente caracteres básicos, idénticos byte a byte en ambas<br>codificaciones. La diferencia solo podría manifestarse en mensajes de error con acentos.|
|Compresión de la respuesta|La compresión solo se activa si el cliente la solicita. La compresión observada inicialmente la introducía la<br>herramienta de prueba, no el servidor.|
|Consulta de duplicados sin índice|El índice que cubre esa consulta ya estaba aplicado en producción.|
|Bloqueo de tabla por el motor de almacenamiento|La tabla involucrada contiene 1.638 registros. Los contadores de espera se ubican en el 0,95 %.|
|La consulta de duplicados originó el incidente|Esa consulta se incorporó el 24 de agosto. El incidente comenzó el 2 de agosto. Es un agravante posterior,<br>no la causa.|



**Valor de esta sección:** Siete líneas de investigación quedaron cerradas con evidencia. Cada una de ellas representa trabajo que la siguiente etapa no necesita repetir. 

## **5. Lo que todavía no se puede afirmar** 

Esta sección es, junto con la anterior, el resultado más importante de esta fase. Declara con precisión qué se desconoce, por qué, y qué se requiere para resolverlo. 

|**Pregunta abierta**|**Qué impide responderla hoy**|**Qué la resolvería**|
|---|---|---|
|¿Qué proporción de transacciones está fallando?|El servicio no registra peticiones ni respuestas|Registro de transacciones|
|¿Con qué frecuencia reenvía España la misma<br>operación?|No se conserva el contenido de las peticiones<br>recibidas|Registro con identificador de transacción|
|¿Cuántas de las operaciones que llegan son<br>nuevas y cuántas repetidas?|Ídem|Ídem|
|¿Las operaciones fallidas fallan por tiempo<br>agotado o por rechazo de formato?|No hay registro de la respuesta emitida ni del tiempo<br>empleado|Registro de transacciones y tiempo de respuesta en el<br>servidor web|
|¿El comportamiento del emisor se agota solo o<br>se sostiene indefinidamente?|Sin registro de llegadas no puede medirse la<br>tendencia|Registro de transacciones sostenido en el tiempo|
|¿Está activo el registro de depuración en<br>producción?|Sin acceso al servidor de aplicación|Verificación en el servidor|
|¿Qué versión del código está desplegada?|Sin acceso al servidor de aplicación|Verificación en el servidor|
|¿Cuál era exactamente la respuesta de la<br>versión anterior?|Nunca se capturó, y la documentación interna se<br>contradice|Entorno de comparación, ya construido|



**Lectura de esta tabla:** Cinco de las ocho preguntas se resuelven con el mismo instrumento: un registro de transacciones que hoy no existe. Dos se resuelven con acceso al servidor. Una se resuelve con el entorno de comparación ya disponible. 

Dicho de otro modo: **el diagnóstico no está detenido por falta de análisis, sino por falta de instrumentación.** Continuar analizando el código sin datos de operación tiene rendimientos decrecientes. El paso siguiente lógico es dotar al servicio de la capacidad de registrar lo que hace. 

SETEX — Auditoría Técnica de Producción | 28 de agosto de 2026 

Página 5 de 8 

**SETEX — Informe de hallazgos** 

Fase 1 - Discovery y auditoría 

## **6. Recomendaciones** 

Ordenadas por relación entre impacto y esfuerzo. El detalle técnico de ejecución se encuentra en el documento complementario de trabajo. 

#### **Prioridad 1 - Recuperar visibilidad** 

|**Acción**|**Naturaleza**<br>**Reversible**|
|---|---|
|Activar el registro de errores del lenguaje|Cambio de configuración<br>Sí|
|Incorporar el tiempo de respuesta al registro de accesos del servidor web|Cambio de configuración<br>Sí|
|Implementar el registro de transacciones|Cambio de código acotado<br>Sí|
|**Advertencia para la Ejecución**||
|**Advertencia para la ejecución:**Se trata del registro de**errores**del leng<br>desactivado, ya que es la causa probable del problema de capacidad de<br>distintos y no deben confundirse.|uaje. El registro de**depuración de la aplicación**debe permanecer<br>scrito en H-06. Activarlo agravaría la situación. Son dos mecanismos|



- **Prioridad 2 - Confirmar la causa del agotamiento:** Ejecutar la prueba de confirmación descrita en H-06 y corregir los dos defectos del componente de registro identificados en H-07. 

- • **Prioridad 3 - Resolver la diferencia de formato:** Determinar la respuesta exacta que emitía la implementación anterior mediante el entorno de comparación ya construido, y ajustar la configuración del servicio para reproducirla. Este trabajo **no depende de obtener respuesta del operador español** . 

- **Prioridad 4 - Corregir la exposición de archivos:** Ajustar la configuración del servidor web y coordinar la rotación de las credenciales expuestas. 

- **Prioridad 5 - Revisar el dimensionamiento:** Evaluar el límite de peticiones simultáneas del servidor web, hoy en su valor por omisión, a la luz de los datos de H-05. 

## **7. Cierre de la fase** 

Esta auditoría se ejecutó entre el 25 y el 28 de agosto de 2026 y produjo: 

- La separación del incidente en dos problemas independientes, con causas distintas. 

- Nueve hallazgos documentados con su nivel de confianza y su evidencia. 

- Siete líneas de investigación cerradas con evidencia, que no requieren volver a explorarse. 

- La identificación de la causa vigente del problema de capacidad, con una prueba de confirmación de bajo costo. 

- Un hallazgo de seguridad no relacionado con el incidente. 

- Un entorno de comparación que permite validar la corrección del formato sin depender de terceros. 

- Ocho preguntas abiertas, documentadas junto con el instrumento que las resolvería. 

El obstáculo central para cerrar el diagnóstico está identificado y es de naturaleza instrumental, no analítica. El servicio no registra su operación, y sin ese registro no es posible cuantificar el incidente ni verificar el efecto de ninguna corrección. 

La etapa siguiente que se desprende de este informe consiste en **dotar al servicio de observabilidad y, con los datos obtenidos, cerrar las preguntas de la sección 5** . 

SETEX — Auditoría Técnica de Producción | 28 de agosto de 2026 

Página 6 de 8 

**SETEX — Informe de hallazgos** 

Fase 1 - Discovery y auditoría 

## **Anexo A — Diferencias de respuesta** 

Respuesta actual del servicio en producción para la consulta de versión: 

```
HTTP/1.1 200 OK
Server: Apache/2.4.58 (Ubuntu)
Content-Length: 599
Vary: Accept-Encoding
Content-Type: text/xml; charset=utf-8
<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope
  xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"
  xmlns:tns="urn:setexwsdl"
  xmlns:ns1="urn:setexwsdl"
  xmlns:xsd="http://www.w3.org/2001/XMLSchema"
  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/"
  SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
  <SOAP-ENV:Body>
    <ns1:getVersionResponse>
      <return xsi:type="SOAP-ENC:Struct">
        <codigoRespuesta xsi:type="xsd:string">3.4</codigoRespuesta>
      </return>
    </ns1:getVersionResponse>
  </SOAP-ENV:Body>
</SOAP-ENV:Envelope>
```

##### **Diferencias identificadas respecto de la implementación anterior:** 

|**#**|**Aspecto**|**Implementación anterior**|**Implementación actual**|
|---|---|---|---|
|1|Codificación declarada|ISO-8859-1|UTF-8|
|2|Tipo de contenido|text/xml; charset=ISO-8859-1|text/xml; charset=utf-8|
|3|Encabezado Server|Incluye identificación del componente SOAP|Solo el servidor web|
|4|Encabezado X-SOAP-Server|Presente|Ausente|
|5|Ubicación espacios de nombres|Sobre el elemento de respuesta|Sobre el sobre del mensaje|
|6|Prefijos duplicados|Uno por identificador|Dos prefijos apuntando al mismo identificador|
|7|Orden de atributos del sobre|Estilo de codificación primero|Espacios de nombres primero|
|8|**Nombre del elemento de retorno**|**iniciarParqueoReturn**|**return, o iniciarParqueoReturn según el modo de**<br>**operación**|
|9|Tipo declarado del retorno|Tipo específico del contrato|Tipo genérico de estructura|
|10|Espacio de nombres de la respuesta|Refleja el de la petición entrante|Siempre el declarado en el contrato|



**La diferencia 8 es la de mayor peso.** Se verificaron ambos modos de operación en el entorno de reconstrucción, con idéntico contrato de servicio: 

```
<!-- Modo sin contrato -->
<ns1:iniciarParqueoResponse>
  <return xsi:type="SOAP-ENC:Struct">
    <codigoRespuesta xsi:type="xsd:int">6</codigoRespuesta>
  </return>
</ns1:iniciarParqueoResponse>
<!-- Modo con contrato -->
<ns1:iniciarParqueoResponse>
  <iniciarParqueoReturn xsi:type="ns1:codigoRespuestaComplex">
    <codigoRespuesta xsi:type="xsd:int">6</codigoRespuesta>
  </iniciarParqueoReturn>
</ns1:iniciarParqueoResponse>
```

Un sistema receptor que localice el valor por la ruta iniciarParqueoReturn/codigoRespuesta **no encuentra dato alguno en el primer caso** . La versión vigente al inicio del incidente operaba en ese primer modo. 

SETEX — Auditoría Técnica de Producción | 28 de agosto de 2026 

Página 7 de 8 

**SETEX — Informe de hallazgos** 

Fase 1 - Discovery y auditoría 

## **Anexo B — Datos de producción** 

Información entregada por el administrador de base de datos el 28 de agosto de 2026. 

##### **Volumen real de las tablas** 

|**Tabla**|**Valor del contador**|**Registros reales**|**Observaciones**|
|---|---|---|---|
|Parqueos|17.483.576|**1.638**|Se purga periódicamente. El contador refleja el histórico acumulado.|
|Transacciones|14.187.001|14.178.186|Volumen acumulado real en producción.|
|**Contadores de bloqu**|**eo**|||
|**Contador**||**Valor**|**Diagnóstico**|
|Bloqueos concedidos de in|mediato|3.060.420|Comportamiento normal|
|Bloqueos con espera||29.273|Comportamiento normal|
|**Proporción de espera**||**0,95 %**|**Por debajo del 1 % (considerado normal)**|



##### **Listado de procesos activos y distribución de conexiones** 

|**Categoría / Métrica**|**Valor**|**Detalle / Antigüedad**|
|---|---|---|
|Procesos inactivos|312|Base de datos ociosa mientras el servicio no responde|
|Proceso interno del motor|1|-|
|Ejecutando consulta|1|-|
|**Total procesos BD**|**314**|**Ninguna consulta en ejecución. Ninguna espera de bloqueo.**|
|Conexiones Servidor A|154|Tope de capacidad (~152 por omisión del web server)|
|Conexiones Servidor B|151|Tope de capacidad (~152 por omisión del web server)|
|Antigüedad de conexiones|Mediana: 70s | P90: 158s |<br>Max: 178s|Retenidas esperando E/S en archivo de log|



## **Anexo C — Cronología de cambios** 

|**Período**|**Actividad**|
|---|---|
|25 feb – 2 mar 2026|Desarrollo de la reimplementación. 37 cambios registrados.|
|2 mar – 24 ago 2026|Sin actividad en el repositorio.|
|**2 ago 2026**|**Inicio del reenvío masivo.**Sin cambios de código en esa fecha.|
|24 – 26 ago 2026|Ocho cambios correctivos: modo de operación del servicio, detección de duplicados, zona horaria y ajustes sobre el<br>mensaje de salida.|
|28 ago 2026|Ajustes sobre el componente de registro y el conteo de duplicados.|



La versión vigente el 2 de agosto operaba el servicio en modo sin contrato, lo que produce el nombre genérico del elemento de retorno descrito en el Anexo A, diferencia 8. La coincidencia entre esa característica y la fecha de inicio del incidente es el fundamento del hallazgo H-03. 

La detección de duplicados, en cambio, se incorporó el 24 de agosto: **no puede explicar un incidente iniciado el 2 de agosto** . 

SETEX — Auditoría Técnica de Producción | 28 de agosto de 2026 

Página 8 de 8 

