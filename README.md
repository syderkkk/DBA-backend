# DBA-backend

Backend desarrollado en Laravel para la gestión de bases de datos académicas. Este proyecto proporciona una API RESTful para administrar usuarios, cursos, inscripciones y otros recursos relacionados con la gestión académica.

## Características

- Gestión de usuarios (estudiantes, docentes, administradores)
- Administración de cursos y asignaturas
- Inscripción y matrícula de estudiantes
- Control de notas y evaluaciones
- Autenticación y autorización basada en roles
- API documentada con Swagger/OpenAPI

## Requisitos

- PHP >= 8.1
- Composer
- PostgreSQL

## Instalación

1. Clona el repositorio:
   ```bash
   git clone https://github.com/tu-usuario/DBA-backend.git
   cd DBA-backend
   ```

2. Instala las dependencias de PHP:
   ```bash
   composer install
   ```

3. Copia el archivo de entorno y configura tus variables:
   ```bash
   cp .env.example .env
   ```
   Edita el archivo `.env` con tus credenciales de base de datos y otros parámetros.

4. Genera la clave de la aplicación:
   ```bash
   php artisan key:generate
   ```

5. Ejecuta las migraciones:
   ```bash
   php artisan migrate
   ```

6. (Opcional) Pobla la base de datos con datos de ejemplo:
   ```bash
   php artisan db:seed
   ```

7. Inicia el servidor de desarrollo:
   ```bash
   php artisan serve
   ```

## Uso

La API estará disponible en `http://localhost:8000`. Puedes consultar la documentación de los endpoints accediendo a `/api/documentation` si tienes Swagger configurado.

## Pruebas

Para ejecutar las pruebas automatizadas:
```bash
php artisan test
```

## Contribución

¡Las contribuciones son bienvenidas! Por favor, abre un issue o un pull request para sugerir mejoras o reportar errores.

## Licencia

Este proyecto está bajo la licencia MIT.

---

Desarrollado por [Tu Nombre o Equipo].