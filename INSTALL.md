# 🚀 **Instalación SETEX con Archivo .env**

## **Para Ubuntu 24 (Recomendado)**

### **1️⃣ Preparar el Sistema**
```bash
# Actualizar sistema
sudo apt update && sudo apt upgrade -y

# Instalar Apache + PHP + Extensiones
sudo apt install apache2 php8.3 libapache2-mod-php8.3 php8.3-mysql php8.3-soap php8.3-xml php8.3-curl php8.3-mbstring git -y

# Configurar Apache
sudo a2enmod rewrite php8.3
sudo systemctl restart apache2
sudo systemctl enable apache2
```

### **2️⃣ Clonar el Proyecto**
```bash
# Ir al directorio web
cd /var/www/html

# Remover archivos por defecto
sudo rm -f index.html

# Clonar proyecto
sudo git clone https://github.com/tu-usuario/tu-repositorio.git serviceSetex
cd serviceSetex

# Configurar permisos
sudo chown -R www-data:www-data /var/www/html/serviceSetex
sudo chmod -R 755 /var/www/html/serviceSetex
sudo chmod -R 777 /var/www/html/serviceSetex/logs
```

### **3️⃣ Configurar Variables de Entorno (.env)**
```bash
# Copiar archivo de ejemplo
sudo cp .env.example .env

# Editar configuración
sudo nano .env
```

### **4️⃣ Personalizar el archivo .env**

**Para Producción en EC2:**
```env
# Servidor y URLs
SETEX_SERVER_HOST="tu-ip-publica"
SETEX_PROTOCOL="http"
SETEX_PROJECT_NAME="serviceSetex"
SETEX_SERVICE_URL="http://tu-ip-publica/serviceSetex/src/setex-wsdl.php"

# Base de Datos
DB_HOST="alpha-msj-db-server-dev.celntjvopzqm.us-west-2.rds.amazonaws.com"
DB_PORT="3306"
DB_NAME="alpha_msj"
DB_USER="userAlphaMsj"
DB_PASS="alpha2000@"
DB_CHARSET="utf8"

# Rutas del Sistema  
SETEX_ROOT_PATH="/var/www/html/serviceSetex"
SETEX_LOGS_PATH="/var/www/html/serviceSetex/logs"

# Configuración de Producción
SETEX_DEBUG="false"
SETEX_LOG_ENABLED="false"
ENVIRONMENT="production"
```

**Para Desarrollo Local:**
```env
# Servidor y URLs
SETEX_SERVER_HOST="localhost"
SETEX_PROTOCOL="http"
SETEX_PROJECT_NAME="serviceSetex"
SETEX_SERVICE_URL="http://localhost/serviceSetex/src/setex-wsdl.php"

# Base de Datos (misma configuración)
DB_HOST="alpha-msj-db-server-dev.celntjvopzqm.us-west-2.rds.amazonaws.com"
DB_PORT="3306"
DB_NAME="alpha_msj"
DB_USER="userAlphaMsj"
DB_PASS="alpha2000@"

# Rutas locales
SETEX_ROOT_PATH="/ruta/a/tu/proyecto/serviceSetex"
SETEX_LOGS_PATH="/ruta/a/tu/proyecto/serviceSetex/logs"

# Configuración de desarrollo
SETEX_DEBUG="true"
SETEX_LOG_ENABLED="true"
ENVIRONMENT="development"
```

### **5️⃣ Verificar Instalación**
```bash
# Probar servicio
curl http://54.187.87.75/serviceSetex/src/testphp.php
curl http://54.187.87.75/serviceSetex/src/setex-wsdl.php?wsdl
```

### **6️⃣ Configuración Avanzada (Opcional)**

**Virtual Host de Apache:**
```bash
sudo nano /etc/apache2/sites-available/setex.conf
```

```apache
<VirtualHost *:80>
    DocumentRoot /var/www/html/serviceSetex
    ServerName tu-dominio.com
    
    <Directory /var/www/html/serviceSetex>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/setex_error.log
    CustomLog ${APACHE_LOG_DIR}/setex_access.log combined
</VirtualHost>
```

```bash
# Activar sitio
sudo a2ensite setex.conf
sudo a2dissite 000-default.conf
sudo systemctl reload apache2
```

## **✅ Ventajas del Sistema .env**

### **🔒 Seguridad**
- Datos sensibles no se suben al repositorio
- Variables específicas por entorno
- Fácil rotación de credenciales

### **🌍 Flexibilidad**
- Mismo código para desarrollo/producción
- Configuración sin modificar código
- Variables específicas por servidor

### **🔧 Mantenimiento**
- Configuración centralizada
- Sin hardcoding de valores
- Fácil debugging y logs configurables

## **📋 URLs Finales del Servicio**

```bash
# 🔥 Servicio SOAP Principal
http://tu-ip-publica/serviceSetex/src/setex-wsdl.php

# 📋 WSDL
http://tu-ip-publica/serviceSetex/src/setex-wsdl.php?wsdl

# 🔧 Dashboard
http://tu-ip-publica/serviceSetex/src/testphp.php  

# 🧪 Cliente de pruebas
http://tu-ip-publica/serviceSetex/test-client.php
```

## **🔄 Actualizaciones Futuras**

```bash
cd /var/www/html/serviceSetex
sudo git pull origin main

# No hace falta tocar el .env, se mantiene la configuración
sudo chown -R www-data:www-data .
sudo chmod -R 755 .
sudo systemctl reload apache2
```

---

¡Tu servicio SETEX ahora es **mucho más profesional** con configuración mediante archivo .env! 🎉