#!/bin/bash

echo "🔍 SETEX SOAP Test desde Terminal"
echo "================================="

# Crear archivo XML temporal
XML_FILE="/tmp/soap_request.xml"
cat > $XML_FILE << 'EOF'
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope 
    xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" 
    xmlns:urn="urn:setexwsdl">
    <soap:Header/>
    <soap:Body>
        <urn:iniciarParqueo>
            <token>dc2fec0f5f08fca379553cc7af20d556</token>
            <plazaId>1</plazaId>
            <zonaId>1</zonaId>
            <identificador>1234567890123</identificador>
            <tiempoParqueo>60</tiempoParqueo>
            <importeParqueo>100</importeParqueo>
            <password>test123</password>
            <fechaInicioParqueo>2026-02-25 21:30:00</fechaInicioParqueo>
            <fechaFinParqueo>2026-02-25 22:30:00</fechaFinParqueo>
            <nroTransaccion>TXN123456</nroTransaccion>
            <fechaTransaccion>2026-02-25 21:30:00</fechaTransaccion>
        </urn:iniciarParqueo>
    </soap:Body>
</soap:Envelope>
EOF

echo "📤 Enviando request SOAP..."
echo "URL: http://54.187.87.75/serviceSetex/src/setex-wsdl.php"
echo "Método: POST"
echo ""

# Ejecutar SOAP request
curl -X POST \
    http://54.187.87.75/serviceSetex/src/setex-wsdl.php \
    -H "Content-Type: text/xml; charset=utf-8" \
    -H "SOAPAction: \"iniciarParqueo\"" \
    -H "Accept: text/xml" \
    -d @$XML_FILE \
    --verbose \
    --write-out "\n\n📊 STATS:\nHTTP Code: %{http_code}\nTotal Time: %{time_total}s\nSize Downloaded: %{size_download} bytes\n" \
    --output /tmp/soap_response.xml

echo ""
echo "📨 RESPUESTA XML:"
echo "=================="
cat /tmp/soap_response.xml
echo ""

# Limpiar archivo temporal
rm -f $XML_FILE

echo ""
echo "📋 LOGS GENERADOS:"
echo "=================="

# Mostrar logs existentes
LOGS_DIR="/var/www/html/serviceSetex/logs"
TODAY=$(date +%Y-%m-%d)

echo "Archivos de log de hoy:"
ls -la $LOGS_DIR/*$TODAY* 2>/dev/null | tail -10

echo ""
echo "📄 CONTENIDO DE LOGS NUEVOS:"
echo "=============================="

# Mostrar logs del debug específico
if [ -f "$LOGS_DIR/iniciarParqueo_debug_$TODAY.txt" ]; then
    echo "🎯 LOG DE FUNCIÓN iniciarParqueo:"
    echo "-------------------------------"
    cat "$LOGS_DIR/iniciarParqueo_debug_$TODAY.txt"
else
    echo "❌ NO SE ENCONTRÓ: iniciarParqueo_debug_$TODAY.txt"
    echo "   Esto significa que la función NO se ejecutó"
fi

echo ""
if [ -f "$LOGS_DIR/soap_service$TODAY.txt" ]; then
    echo "🌐 LOG DE SOAP SERVICE:"
    echo "----------------------"
    tail -20 "$LOGS_DIR/soap_service$TODAY.txt"
else
    echo "❌ NO SE ENCONTRÓ: soap_service$TODAY.txt"
fi

echo ""
echo "🔍 ÚLTIMOS LOGS (cualquier archivo):"
echo "===================================="
find $LOGS_DIR -name "*$TODAY*" -type f -exec tail -5 {} \; 2>/dev/null

echo ""
echo "✅ Test completado. Revisar resultados arriba."