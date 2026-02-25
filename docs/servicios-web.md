# Documentación de Servicios Web - SETEX

Este documento describe los servicios web expuestos por el proyecto SETEX utilizando la biblioteca `nusoap`.

## Servicios Expuestos

### 1. `iniciarParqueo`
- **Descripción:** Permite iniciar un parqueo desde un parquímetro.
- **Entradas:**
  - `token` (string): Token de autenticación.
  - `plazaId` (int): ID de la plaza.
  - `zonaId` (int): ID de la zona.
  - `identificador` (string): Identificador del vehículo.
  - `tiempoParqueo` (int): Tiempo de parqueo en minutos.
  - `importeParqueo` (int): Importe del parqueo.
  - `passwordCps` (string): Contraseña del CPS.
  - `fechaInicioParqueo` (string): Fecha de inicio del parqueo.
  - `fechaFinParqueo` (string): Fecha de fin del parqueo.
  - `nroTransaccion` (string): Número de transacción.
  - `fechaTransaccion` (string): Fecha de la transacción.
- **Salida:**
  - `iniciarParqueoReturn` (complexType): Un objeto de tipo `codigoRespuestaComplex` que contiene:
    - `codigoRespuesta` (int): Código de respuesta que indica el resultado de la operación.
- **Modo de Operación:**
  - Estilo: `rpc`
  - Codificación: `encoded`
- **Descripción Extendida:** Inicia un parqueo desde el parquímetro.

---

### 2. `getVersion`
- **Descripción:** Permite obtener la versión del servicio web.
- **Entradas:**
  - `valor` (string): Valor de entrada (puede ser un identificador o parámetro genérico).
- **Salida:**
  - `getVersionReturn` (complexType): Un objeto de tipo `codigoRespuestaStringComplex` que contiene:
    - `codigoRespuesta` (string): Código de respuesta que indica el resultado de la operación.
- **Modo de Operación:**
  - Estilo: `rpc`
  - Codificación: `encoded`
- **Descripción Extendida:** Verifica la disponibilidad del servicio web.

---

## Tipos Complejos Definidos

### `codigoRespuestaComplex`
- **Descripción:** Tipo complejo que contiene un código de respuesta numérico.
- **Estructura:**
  - `codigoRespuesta` (int): Código de respuesta.

### `codigoRespuestaStringComplex`
- **Descripción:** Tipo complejo que contiene un código de respuesta en formato de cadena.
- **Estructura:**
  - `codigoRespuesta` (string): Código de respuesta.

---

## Configuración del Servidor
- **Nombre del WSDL:** `SETEX`
- **Namespace:** `urn:setexwsdl`

---

## Notas Adicionales
- El servidor utiliza la biblioteca `nusoap` para la implementación de los servicios web.
- Los servicios están configurados con estilo `rpc` y codificación `encoded`, lo que es típico en servicios SOAP.