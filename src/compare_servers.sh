#!/bin/bash

echo "🔍 TEST AL SERVIDOR ACTUAL (52.39.146.172)"
echo "=========================================="

# Crear archivo XML (igual que nuestro test)
XML_FILE="/tmp/soap_request_original.xml"
cat > $XML_FILE << 'EOF'
<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
    <soap:Body>
        <iniciarParqueo xmlns="urn:setexwsdl">
            <token>dc2fec0f5f08fca379553cc7af20d556</token>
            <plazaId>1</plazaId>
            <zonaId>1</zonaId>
            <identificador>1234567890123</identificador>
            <tiempoParqueo>60</tiempoParqueo>
            <importeParqueo>100</importeParqueo>
            <passwordCps>test123</passwordCps>
            <fechaInicioParqueo>2026-02-25 21:30:00</fechaInicioParqueo>
            <fechaFinParqueo>2026-02-25 22:30:00</fechaFinParqueo>
            <nroTransaccion>TXN123456</nroTransaccion>
            <fechaTransaccion>2026-02-25 21:30:00</fechaTransaccion>
        </iniciarParqueo>
    </soap:Body>
</soap:Envelope>
EOF

echo "📤 Enviando al servidor ACTUAL (52.39.146.172)..."

# Test al servidor actual
curl -X POST \
    http://52.39.146.172/serviceSetex/src/setex-wsdl.php \
    -H "Content-Type: text/xml; charset=utf-8" \
    -H "SOAPAction: \"urn:setexwsdl#iniciarParqueo\"" \
    -H "Accept: text/xml" \
    -d @$XML_FILE \
    --verbose \
    --connect-timeout 10 \
    --write-out "\n\n📊 SERVIDOR ACTUAL:\nHTTP Code: %{http_code}\nTotal Time: %{time_total}s\n"

echo ""
echo "📤 Enviando a NUESTRO servidor (54.187.87.75)..."

# Test a nuestro servidor
curl -X POST \
    http://54.187.87.75/serviceSetex/src/setex-wsdl.php \
    -H "Content-Type: text/xml; charset=utf-8" \
    -H "SOAPAction: \"urn:setexwsdl#iniciarParqueo\"" \
    -H "Accept: text/xml" \
    -d @$XML_FILE \
    --verbose \
    --connect-timeout 10 \
    --write-out "\n\n📊 NUESTRO SERVIDOR:\nHTTP Code: %{http_code}\nTotal Time: %{time_total}s\n"

# Limpiar
rm -f $XML_FILE

echo ""
echo "✅ Comparación completada."