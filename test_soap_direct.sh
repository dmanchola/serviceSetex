#!/bin/bash

echo "🧪 Prueba directa del servicio SOAP con cURL"
echo "=========================================="

# Verificar WSDL del servicio real
echo ""
echo "1. Verificando WSDL del servicio real..."
curl -s "http://54.187.87.75/serviceSetex/src/setex-wsdl.php?wsdl" | head -20

echo ""
echo "2. Probando getVersion..."
curl -X POST \
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
  "http://54.187.87.75/serviceSetex/src/setex-wsdl.php"

echo ""
echo "3. Probando iniciarParqueo con datos mínimos..."
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
         <identificador>1234567890123</identificador>
         <tiempoParqueo>30</tiempoParqueo>
         <importeParqueo>50</importeParqueo>
         <passwordCps>test123</passwordCps>
         <fechaInicioParqueo>2026-02-27 17:00:00</fechaInicioParqueo>
         <fechaFinParqueo>2026-02-27 17:30:00</fechaFinParqueo>
         <nroTransaccion>TEST_123456</nroTransaccion>
         <fechaTransaccion>2026-02-27 17:00:00</fechaTransaccion>
      </urn:iniciarParqueo>
   </soap:Body>
</soap:Envelope>' \
  "http://54.187.87.75/serviceSetex/src/setex-wsdl.php"

echo ""
echo "✅ Pruebas completadas"
echo ""
echo "Ahora revisa los logs en /var/www/html/serviceSetex/logs/ para ver:"
echo "- raw_xml_debug_*.txt - para ver el XML que llegó"
echo "- iniciarParqueo_debug_*.txt - para ver los parámetros parseados"
echo "- debug_simple.txt - para ver el flujo del servicio"