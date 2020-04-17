# Rating - Servicio de valoración

Framework: Phalcon 3.4
Base de Datos: Mysql

Antes de empezar crear archivo de configuracion dentro de la carpeta config.

Para iniciar el servicio ejecutar el comando desde la carpeta rating:
```
docker-compose up
```
Para cambiar algun puerto modificar el archivo [Dockerfile](docker-compose.yml).

Para agregar, modificar o eliminar dentro de la base de datos utilizamos Adminer,
se accede ingresando a localhost:8080.


Agregar una url al sistema.
```
curl --data "url=https://www.google.com/" localhost/create_url
```

Devuelve la valoración, lista de comentarios(si los hay) y el formulario para ingresar nuevos comentarios.
```
curl --data "url=https://www.google.com/" localhost/read_url
```

Agregar un comentario al sistema.
```
curl --data "url=https://www.google.com/" --data "comment=Excelente servicio" --data "score=5"  localhost/create_comment
```

- [x] Microservicio de Urls
- [ ] Microservicio de Comentarios
- [x] Microservicio de templates