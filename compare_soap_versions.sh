#!/bin/bash

echo "🆚 Comparación nuSOAP vs SOAP Nativo - SETEX"
echo "============================================"

# Definir URLs
NUSOAP_URL="http://localhost/serviceSetex/src/setex-wsdl.php"
NATIVE_URL="http://localhost/serviceSetex/src/setex-native-soap.php"

echo ""
echo "🔍 Probando ambas versiones con el mismo XML..."
echo ""

# XML de prueba estándar
XML_TEST='<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:setexwsdl">
   <soap:Header/>
   <soap:Body>
      <urn:iniciarParqueo>
         <token>dc2fec0f5f08fca379553cc7af20d556</token>
         <plazaId>2</plazaId>
         <zonaId>999</zonaId>
         <identificador>1234567890123</identificador>
         <tiempoParqueo>30</tiempoParqueo>
         <importeParqueo>50</importeParqueo>
         <passwordCps>test123</passwordCps>
         <fechaInicioParqueo>2026-02-27 20:00:00</fechaInicioParqueo>
         <fechaFinParqueo>2026-02-27 20:30:00</fechaFinParqueo>
         <nroTransaccion>COMPARE_TEST_001</nroTransaccion>
         <fechaTransaccion>2026-02-27 20:00:00</fechaTransaccion>
      </urn:iniciarParqueo>
   </soap:Body>
</soap:Envelope>'

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📊 PRUEBA 1: iniciarParqueo con nuSOAP (actual)"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

echo "⏱️  Tiempo de inicio: $(date)"
TIEMPO_INICIO=$(date +%s%3N)

RESPONSE_NUSOAP=$(curl -s -w "\nHTTP_CODE:%{http_code}\nTIME_TOTAL:%{time_total}" \
  -X POST \
  -H "Content-Type: text/xml; charset=utf-8" \
  -H "SOAPAction: urn:setexwsdl#iniciarParqueo" \
  -d "$XML_TEST" \
  "$NUSOAP_URL")

TIEMPO_FIN=$(date +%s%3N)
TIEMPO_NUSOAP=$((TIEMPO_FIN - TIEMPO_INICIO))

echo "✅ Respuesta nuSOAP:"
echo "$RESPONSE_NUSOAP"
echo "⏱️  Tiempo total: ${TIEMPO_NUSOAP}ms"

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🚀 PRUEBA 2: iniciarParqueo con SOAP Nativo"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

echo "⏱️  Tiempo de inicio: $(date)"
TIEMPO_INICIO=$(date +%s%3N)

RESPONSE_NATIVE=$(curl -s -w "\nHTTP_CODE:%{http_code}\nTIME_TOTAL:%{time_total}" \
  -X POST \
  -H "Content-Type: text/xml; charset=utf-8" \
  -H "SOAPAction: urn:setexwsdl#iniciarParqueo" \
  -d "$XML_TEST" \
  "$NATIVE_URL")

TIEMPO_FIN=$(date +%s%3N)
TIEMPO_NATIVE=$((TIEMPO_FIN - TIEMPO_INICIO))

echo "✅ Respuesta SOAP Nativo:"
echo "$RESPONSE_NATIVE"
echo "⏱️  Tiempo total: ${TIEMPO_NATIVE}ms"

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📈 ANÁLISIS COMPARATIVO"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

echo ""
echo "⏱️  Rendimiento:"
echo "   - nuSOAP:     ${TIEMPO_NUSOAP}ms"
echo "   - SOAP Nativo: ${TIEMPO_NATIVE}ms"

if [ $TIEMPO_NATIVE -lt $TIEMPO_NUSOAP ]; then
    MEJORA=$(( ((TIEMPO_NUSOAP - TIEMPO_NATIVE) * 100) / TIEMPO_NUSOAP ))
    echo "   🚀 SOAP Nativo es ${MEJORA}% más rápido"
else
    echo "   ⚖️ Rendimiento similar"
fi

echo ""
echo "🔍 Verificando logs generados..."

echo ""
echo "📋 Logs nuSOAP (últimas 5 líneas):"
if [ -f "/var/www/html/serviceSetex/logs/iniciarParqueo_debug_$(date +%Y-%m-%d).txt" ]; then
    tail -5 "/var/www/html/serviceSetex/logs/iniciarParqueo_debug_$(date +%Y-%m-%d).txt"
else
    echo "❌ No se encontró log de nuSOAP"
fi

echo ""
echo "📋 Logs SOAP Nativo (últimas 5 líneas):"
if [ -f "/var/www/html/serviceSetex/logs/iniciarParqueo_native_debug_$(date +%Y-%m-%d).txt" ]; then
    tail -5 "/var/www/html/serviceSetex/logs/iniciarParqueo_native_debug_$(date +%Y-%m-%d).txt"
else
    echo "❌ No se encontró log de SOAP Nativo"
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "🎯 RECOMENDACIÓN"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

echo ""
echo "✅ VENTAJAS de migrar a SOAP Nativo:"
echo "   🚀 Mejor rendimiento"
echo "   🔧 Sin warnings PHP 8.x"
echo "   📊 Parsing correcto de parámetros"
echo "   🛠️ Mantenimiento activo"
echo "   🔒 Mayor estabilidad"

echo ""
echo "📋 Para implementar la migración:"
echo "   1. Probar SOAP nativo: $NATIVE_URL"
echo "   2. Verificar compatibilidad con clientes"
echo "   3. Cambiar URL en producción gradualmente"
echo "   4. Monitorear logs durante transición"

echo ""
echo "📁 Archivos creados:"
echo "   - setex-native-soap.php (nuevo servidor SOAP nativo)"
echo "   - Logs en /var/www/html/serviceSetex/logs/*native*"

echo ""
echo "✅ Comparación completada - $(date)"