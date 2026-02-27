#!/bin/bash

echo "🧪 Prueba de Solución nuSOAP - Parsing Manual de XML"
echo "=================================================="

# Definir URL base (ajustar según tu servidor)
BASE_URL="http://localhost/serviceSetex"
EC2_URL="http://54.187.87.75/serviceSetex"

echo ""
echo "1. Probando getVersion..."
curl -X POST \
  -H "Content-Type: text/xml; charset=utf-8" \
  -H "SOAPAction: urn:setexwsdl#getVersion" \
  -d '<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:setexwsdl">
   <soap:Header/>
   <soap:Body>
      <urn:getVersion>
         <valor>test_version_check</valor>
      </urn:getVersion>
   </soap:Body>
</soap:Envelope>' \
  "$BASE_URL/src/setex-wsdl.php"

echo ""
echo ""
echo "2. Probando iniciarParqueo (Formato 1 - básico)..."
curl -X POST \
  -H "Content-Type: text/xml; charset=utf-8" \
  -H "SOAPAction: urn:setexwsdl#iniciarParqueo" \
  -d '<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
   <soap:Body>
      <iniciarParqueo xmlns="urn:setexwsdl">
         <token>dc2fec0f5f08fca379553cc7af20d556</token>
         <plazaId>2</plazaId>
         <zonaId>999</zonaId>
         <identificador>1234567890123</identificador>
         <tiempoParqueo>30</tiempoParqueo>
         <importeParqueo>50</importeParqueo>
         <passwordCps>test123</passwordCps>
         <fechaInicioParqueo>2026-02-27 18:00:00</fechaInicioParqueo>
         <fechaFinParqueo>2026-02-27 18:30:00</fechaFinParqueo>
         <nroTransaccion>TEST_BASIC_001</nroTransaccion>
         <fechaTransaccion>2026-02-27 18:00:00</fechaTransaccion>
      </iniciarParqueo>
   </soap:Body>
</soap:Envelope>' \
  "$BASE_URL/src/setex-wsdl.php"

echo ""
echo ""
echo "3. Probando iniciarParqueo (Formato 2 - con namespaces)..."
curl -X POST \
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
         <identificador>9999999999999</identificador>
         <tiempoParqueo>45</tiempoParqueo>
         <importeParqueo>75</importeParqueo>
         <passwordCps>test456</passwordCps>
         <fechaInicioParqueo>2026-02-27 18:15:00</fechaInicioParqueo>
         <fechaFinParqueo>2026-02-27 19:00:00</fechaFinParqueo>
         <nroTransaccion>TEST_NS_002</nroTransaccion>
         <fechaTransaccion>2026-02-27 18:15:00</fechaTransaccion>
      </urn:iniciarParqueo>
   </soap:Body>
</soap:Envelope>' \
  "$BASE_URL/src/setex-wsdl.php"

echo ""
echo ""
echo "4. Probando iniciarParqueo (Formato 3 - con tipos xsi)..."
curl -X POST \
  -H "Content-Type: text/xml; charset=utf-8" \
  -H "SOAPAction: urn:setexwsdl#iniciarParqueo" \
  -d '<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" 
                   xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                   xmlns:xsd="http://www.w3.org/2001/XMLSchema"
                   SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
   <SOAP-ENV:Body>
      <m:iniciarParqueo xmlns:m="urn:setexwsdl">
         <token xsi:type="xsd:string">dc2fec0f5f08fca379553cc7af20d556</token>
         <plazaId xsi:type="xsd:int">2</plazaId>
         <zonaId xsi:type="xsd:int">999</zonaId>
         <identificador xsi:type="xsd:string">8888888888888</identificador>
         <tiempoParqueo xsi:type="xsd:int">60</tiempoParqueo>
         <importeParqueo xsi:type="xsd:int">100</importeParqueo>
         <passwordCps xsi:type="xsd:string">test789</passwordCps>
         <fechaInicioParqueo xsi:type="xsd:string">2026-02-27 19:00:00</fechaInicioParqueo>
         <fechaFinParqueo xsi:type="xsd:string">2026-02-27 20:00:00</fechaFinParqueo>
         <nroTransaccion xsi:type="xsd:string">TEST_XSI_003</nroTransaccion>
         <fechaTransaccion xsi:type="xsd:string">2026-02-27 19:00:00</fechaTransaccion>
      </m:iniciarParqueo>
   </SOAP-ENV:Body>
</SOAP-ENV:Envelope>' \
  "$BASE_URL/src/setex-wsdl.php"

echo ""
echo ""
echo "🔍 Verificando logs generados..."
echo "=========================="

if [ -f "/var/www/html/serviceSetex/logs/iniciarParqueo_debug_$(date +%Y-%m-%d).txt" ]; then
    echo ""
    echo "📋 Últimas entradas del log de debug:"
    tail -20 "/var/www/html/serviceSetex/logs/iniciarParqueo_debug_$(date +%Y-%m-%d).txt"
else
    echo "❌ No se encontró el archivo de log de debug"
fi

if [ -f "/var/www/html/serviceSetex/logs/raw_xml_debug_$(date +%Y-%m-%d).txt" ]; then
    echo ""
    echo "📋 XML raw recibido (últimas 10 líneas):"
    tail -10 "/var/www/html/serviceSetex/logs/raw_xml_debug_$(date +%Y-%m-%d).txt"
else
    echo "❌ No se encontró el archivo de log XML"
fi

echo ""
echo "✅ Pruebas completadas!"
echo ""
echo "🔍 Para análisis detallado, revisa:"
echo "   - /var/www/html/serviceSetex/logs/iniciarParqueo_debug_$(date +%Y-%m-%d).txt"
echo "   - /var/www/html/serviceSetex/logs/raw_xml_debug_$(date +%Y-%m-%d).txt"
echo "   - /var/www/html/serviceSetex/logs/getVersion_debug_$(date +%Y-%m-%d).txt"
echo ""
echo "🎯 Busca estas líneas en los logs:"
echo "   ✅ 'Parámetros extraídos del XML' = Parsing manual funcionó"
echo "   ✅ 'PARÁMETROS REALES DETECTADOS' = nuSOAP funcionó normal"
echo "   ❌ 'usando valores de prueba' = Ninguno de los dos funcionó"