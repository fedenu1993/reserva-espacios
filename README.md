<!-- Reserva de Espacios para Eventos - Backend (API REST) -->

Este proyecto es la API RESTful desarrollada con Laravel para la gestión de espacios y reservas en una aplicación de reserva de espacios para eventos.

<!-- Requisitos previos -->

Antes de comenzar, asegúrate de tener los siguientes requisitos instalados:
PHP >= 8.0
Composer
MySQL (o cualquier otra base de datos que prefieras)
Laravel 9.x (este proyecto fue construido con Laravel)

<!-- Configuración inicial -->

1. Instalar dependencias
   composer install

2. Configurar las variables de entorno  
   cp .env.example .env

Abre el archivo .env y configura los valores correctos para tu base de datos y otras variables necesarias (como el APP*URL y el DB*\*):

3. Una vez que hayas configurado el archivo .env, debes ejecutar las migraciones para crear las tablas necesarias en la base de datos. También puedes ejecutar los seeders para agregar datos de ejemplo a la base de datos.

    //Solo tablas sin datos de prueba
    php artisan migrate

    //Tablas y datos de prueba
    php artisan migrate --seed

4. Generar la clave de la aplicación
   php artisan key:generate

5. Crear el Enlace de Almacenamiento:
   php artisan storage:link

6. Levantar el servidor
   php artisan serve

<!-- Autenticación y uso de tokens -->

Este proyecto utiliza Sanctum para la autenticación de usuarios con tokens. Debes incluir el token de autenticación en las solicitudes que requieren autenticación.

Haz login usando el endpoint POST /api/login con tu email y contraseña.
El servidor te devolverá un token que debes incluir en el encabezado de las peticiones:
Authorization: Bearer {TOKEN}

    //Si ejecutas los seeders podes usar
    email: test@example.com
    password: password

    Caso contrario vas a tener que crear un user
