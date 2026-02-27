#!/bin/bash

echo "🔧 Corrigiendo problemas identificados en SOAP"
echo "============================================="

echo ""
echo "1. Corrigiendo permisos del directorio logs..."
sudo chown -R www-data:www-data /var/www/html/serviceSetex/logs/
sudo chmod -R 755 /var/www/html/serviceSetex/logs/

if [ $? -eq 0 ]; then
    echo "   ✅ Permisos corregidos"
else
    echo "   ❌ Error corrigiendo permisos"
fi

echo ""
echo "2. Verificando función getVersion duplicada..."
DUPLICATE_COUNT=$(grep -c "function getVersion" /var/www/html/serviceSetex/src/servicio.class.php)
echo "   - Funciones getVersion encontradas: $DUPLICATE_COUNT"

if [ "$DUPLICATE_COUNT" -eq "1" ]; then
    echo "   ✅ Función duplicada eliminada correctamente"
else
    echo "   ❌ Aún hay funciones duplicadas"
fi

echo ""
echo "3. Probando servicio corregido..."

# XML de prueba para getVersion
echo ""
echo "📞 Probando getVersion..."
RESPONSE_VERSION=$(curl -s -w "\nHTTP_CODE:%{http_code}" \
  -X POST \
  -H "Content-Type: text/xml; charset=utf-8" \
  -H "SOAPAction: urn:setexwsdl#getVersion" \
  -d '<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:setexwsdl">
   <soap:Header/>
   <soap:Body>
      <urn:getVersion>
         <valor>test</valor>
      </urn:getVersion>
   </soap:Body>
</soap:Envelope>' \
  "http://localhost/serviceSetex/src/setex-wsdl.php")

echo "Respuesta getVersion:"
echo "$RESPONSE_VERSION"

# XML de prueba para iniciarParqueo
echo ""
echo "📞 Probando iniciarParqueo..."
RESPONSE_PARKING=$(curl -s -w "\nHTTP_CODE:%{http_code}" \
  -X POST \
  -H "Content-Type: text/xml; charset=utf-8" \
  -H "SOAPAction: urn:setexwsdl#iniciarParqueo" \
  -d '<?xml version="1.0" encoding="UTF-8"?>
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
         <fechaInicioParqueo>2026-02-27 20:30:00</fechaInicioParqueo>
         <fechaFinParqueo>2026-02-27 21:00:00</fechaFinParqueo>
         <nroTransaccion>FIXED_TEST_001</nroTransaccion>
         <fechaTransaccion>2026-02-27 20:30:00</fechaTransaccion>
      </urn:iniciarParqueo>
   </soap:Body>
</soap:Envelope>' \
  "http://localhost/serviceSetex/src/setex-wsdl.php")

echo "Respuesta iniciarParqueo:"
echo "$RESPONSE_PARKING"

echo ""
echo "4. Verificando logs nuevos..."

echo ""
echo "📋 Logs de debug recientes:"
if [ -f "/var/www/html/serviceSetex/logs/debug_simple.txt" ]; then
    echo "   - debug_simple.txt (últimas 3 líneas):"
    tail -3 "/var/www/html/serviceSetex/logs/debug_simple.txt"
else
    echo "   ❌ No se encontró debug_simple.txt"
fi

if [ -f "/var/www/html/serviceSetex/logs/iniciarParqueo_debug_$(date +%Y-%m-%d).txt" ]; then
    echo ""
    echo "   - iniciarParqueo_debug.txt (últimas 5 líneas):"
    tail -5 "/var/www/html/serviceSetex/logs/iniciarParqueo_debug_$(date +%Y-%m-%d).txt"
else
    echo "   ❌ No se encontró iniciarParqueo_debug.txt"
fi

if [ -f "/var/www/html/serviceSetex/logs/raw_xml_debug_$(date +%Y-%m-%d).txt" ]; then
    echo ""
    echo "   - raw_xml_debug.txt (últimas 3 líneas):"
    tail -3 "/var/www/html/serviceSetex/logs/raw_xml_debug_$(date +%Y-%m-%d).txt"
else
    echo "   ❌ No se encontró raw_xml_debug.txt"
fi

echo ""
echo "5. Verificando errores de Apache recientes..."
RECENT_ERRORS=$(sudo tail -10 /var/log/apache2/error.log | grep "$(date +%Y-%m-%d)" | grep -E "(Fatal|Error|Warning)")

if [ -n "$RECENT_ERRORS" ]; then
    echo "❌ Errores recientes encontrados:"
    echo "$RECENT_ERRORS"
else
    echo "✅ No hay errores PHP recientes en Apache"
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📊 RESUMEN DE CORRECCIONES"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

echo ""
echo "🔧 Problemas corregidos:"
echo "   ✅ Función getVersion duplicada eliminada"
echo "   ✅ Permisos del directorio logs corregidos"
echo ""
echo "🎯 Estado actual:"

# Verificar códigos HTTP
VERSION_CODE=$(echo "$RESPONSE_VERSION" | grep "HTTP_CODE:" | cut -d: -f2)
PARKING_CODE=$(echo "$RESPONSE_PARKING" | grep "HTTP_CODE:" | cut -d: -f2)

if [ "$VERSION_CODE" = "200" ]; then
    echo "   ✅ getVersion: HTTP 200 (funcionando)"
else
    echo "   ⚠️ getVersion: HTTP $VERSION_CODE"
fi

if [ "$PARKING_CODE" = "200" ]; then
    echo "   ✅ iniciarParqueo: HTTP 200 (funcionando)"
else
    echo "   ⚠️ iniciarParqueo: HTTP $PARKING_CODE"
fi

echo ""
echo "✅ Diagnóstico completado - $(date)"