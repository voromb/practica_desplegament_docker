# Dockerització de l'aplicació

## Estructura general

El projecte es dockeritza utilitzant **docker-compose** amb quatre serveis principals:

- **mysql**: base de dades MySQL.
- **backend**: API en PHP que accedeix a MySQL.
- **frontend**: aplicació Vue que consumeix l'API.
- **phpmyadmin**: eina web per administrar MySQL.

Els ports utilitzats en aquest projecte estan pensats per a **no xocar amb altres serveis** existents a la màquina. Es poden modificar fàcilment al fitxer `.env` o al `docker-compose.yml` si cal.

---

## Fitxer `docker-compose.yml`

Al directori arrel del projecte hi ha el fitxer `docker-compose.yml`, que defineix els serveis i dependències.

### Servei MySQL (`mysql_contenidor`)

- **Imatge**: `mysql:8.0` (imatge oficial de MySQL).
- **Nom de contenidor**: `mysql_contenidor`.
- **Variables d'entorn**:
  - `MYSQL_ROOT_PASSWORD`: contrasenya de l'usuari root.
  - `MYSQL_DATABASE`: nom de la base de dades que es crearà automàticament (`elementos`).
  - `MYSQL_USER` i `MYSQL_PASSWORD`: usuari i contrasenya d'aplicació (`usuario_db` / `password_db`).
- **Volums**:
  - `mysql-data:/var/lib/mysql`: volum perquè **no es perden les dades** entre arrencades.
  - `./mysql-init:/docker-entrypoint-initdb.d`: carpeta del projecte on hi ha `init.sql`.
    - L'script `init.sql` crea la base de dades `elementos` i la taula `items` si no existeixen.
- **Ports**: exposa el port 3306 de MySQL al host.

Aquest servei s'arrenca **primer** i la resta de serveis depenen que estiga disponible.

Per a no deixar les contrasenyes i els ports escrits a foc dins del `docker-compose.yml`, he creat també un fitxer `.env` a la carpeta arrel. En aquest `.env` tinc:

- Les variables de MySQL (`MYSQL_ROOT_PASSWORD`, `MYSQL_DATABASE`, `MYSQL_USER`, `MYSQL_PASSWORD`).
- Les credencials que utilitza el backend i phpMyAdmin (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`).
- Els ports que expose al host (`BACKEND_PORT`, `FRONTEND_PORT`, `PHPMYADMIN_PORT`).

Al `docker-compose.yml` faig servir `env_file: - .env` als serveis que ho necessiten i també expressions del tipus `${NOM_VARIABLE}`. D'aquesta manera, si vull canviar alguna contrasenya o algun port, només he de tocar el `.env` i no anar buscant per tot el `docker-compose.yml`.

### Servei Backend (`backend_contenidor`)

- **Construcció**:
  - Es construeix amb el `Dockerfile` situat a `./backend/Dockerfile`.
  - Es fa servir un **multi-stage build** amb Alpine.
- **Nom de contenidor**: `backend_contenidor`.
- **Dependències**: `depends_on: [mysql]` perquè no s'intente connectar abans que MySQL estiga aixecat.
- **Ports**: mapeig d'un port del host al port 8000 del contenidor (servidor PHP integrat).
- **Variables d'entorn** (opcionalment usades al codi PHP):
  - `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`, apuntant al servei `mysql_contenidor`.

A l'arrencar, el contenidor executa un script `wait-for-it.sh` que comprova que el port de MySQL estiga disponible i, només llavors, inicia el servidor PHP.

### Servei Frontend (`frontend_contenidor`)

- **Construcció**:
  - Es construeix amb el `Dockerfile` situat a `./frontend/Dockerfile`.
  - Imatge base: `node:18-alpine`.
- **Nom de contenidor**: `frontend_contenidor`.
- **Dependències**: `depends_on: [backend]` perquè el frontend s'alce després del backend.
- **Ports**: es publica un port lliure del host que es redirigeix al `8080` intern del contenidor (on corre `npm run serve`).

El frontend té un component `ItemsList.vue` que fa peticions HTTP al backend (API PHP).

### Servei phpMyAdmin (`adminMySQL_contenidor`)

- **Imatge**: `phpmyadmin/phpmyadmin` (imatge oficial de phpMyAdmin).
- **Nom de contenidor**: `adminMySQL_contenidor`.
- **Dependències**: `depends_on: [mysql]` perquè només s'alce després de MySQL.
- **Variables d'entorn**:
  - `PMA_HOST`: nom del servei de MySQL (`mysql_contenidor`).
  - `PMA_USER`: usuari de la base de dades (`usuario_db`).
  - `PMA_PASSWORD`: contrasenya de l'usuari (`password_db`).
- **Ports**: es mapeja un port lliure del host cap al port 80 del contenidor per accedir a la interfície web.

---

## Dockerfile del backend (`backend/Dockerfile`)

El backend és una API PHP simple que accedeix a MySQL. El `Dockerfile` del backend fa un **multi-stage build** i utilitza una imatge final basada en Alpine.

### Estructura del Dockerfile

1. **Etapa builder (opcional)**

   ```dockerfile
   FROM alpine:3.18 AS builder

   RUN apk add --no-cache bash
   ```

   Aquesta etapa es pot utilitzar per preparar eines addicionals si cal. No és la imatge final que s'executa en producció.

2. **Etapa final: imatge PHP sobre Alpine**

   ```dockerfile
   FROM php:8.2-cli-alpine

   RUN apk add --no-cache bash \
       && docker-php-ext-install pdo pdo_mysql

   WORKDIR /var/www/html

   COPY index.php config.php items.php ./
   COPY wait-for-it.sh /usr/local/bin/wait-for-it.sh

   RUN chmod +x /usr/local/bin/wait-for-it.sh

   EXPOSE 8000

   CMD ["sh", "-c", "wait-for-it.sh mysql_contenidor:3306 -- php -S 0.0.0.0:8000 index.php"]
   ```

   Punts importants:

   - **Imatge final**: `php:8.2-cli-alpine`.
   - **`bash` instal·lat**: imprescindible perquè l'script `wait-for-it.sh` utilitza bash.
   - **Extensions PHP**: s'instal·len `pdo` i `pdo_mysql` per poder connectar amb MySQL.
   - **Còpia de fitxers**: es copien `index.php`, `config.php` i `items.php` al directori de treball.
   - **`wait-for-it.sh`**: script que espera que el port de MySQL (`mysql_contenidor:3306`) estiga disponible avant de llançar el servidor PHP.
   - **Comando final (`CMD`)**: arrenca el servidor PHP integrat a `0.0.0.0:8000` després d'esperar MySQL.

Amb això, es garanteix que el backend **no intente connectar massa prompte** a MySQL.

---

## Dockerfile del frontend (`frontend/Dockerfile`)

El frontend és una aplicació Vue creada amb Vue CLI. El `Dockerfile` està pensat per a entorn de desenvolupament (servei amb `npm run serve`).

```dockerfile
FROM node:18-alpine

WORKDIR /app

COPY package*.json ./

RUN npm install

COPY . .

EXPOSE 8080

CMD ["npm", "run", "serve", "--", "--host", "0.0.0.0", "--port", "8080"]
```

Detalls clau:

- **Imatge base**: `node:18-alpine` (lleugera).
- **`WORKDIR /app`**: directori de treball per al projecte Vue.
- **Instal·lació de dependències**:
  - Es copien primer `package.json` i `package-lock.json`.
  - S'executa `npm install` per instal·lar totes les dependències.
- **Còpia del codi**: `COPY . .` per copiar la resta de fitxers del frontend.
- **Port exposat**: 8080 (port per defecte de `vue-cli-service serve`).
- **Comando d'arrencada**: `npm run serve -- --host 0.0.0.0 --port 8080` perquè el servidor siga accessible des de fora del contenidor.

---

## Arrencada dels serveis

### Arrencar el projecte

Des de la carpeta arrel del projecte (on hi ha `docker-compose.yml`):

```bash
docker-compose up --build
```

Aquesta comanda:

- Construeix les imatges de backend i frontend segons els respectius `Dockerfile`.
- Crea i arrenca els contenidors de MySQL, backend, frontend i phpMyAdmin.

### Aturar els serveis

Per aturar i eliminar els contenidors (sense esborrar el volum de dades de MySQL):

```bash
docker-compose down
```

El volum `mysql-data` es manté, per tant les dades de la base de dades **persistixen** entre arrencades.

---

## Resum

- S'ha creat un **entorn complet** amb `docker-compose` que inclou MySQL, un backend PHP i un frontend Vue, a més de phpMyAdmin per administrar la base de dades.
- El `Dockerfile` del backend usa **multi-stage build** i una imatge final Alpine amb PHP i les extensions necessàries per a MySQL, i utilitza `wait-for-it.sh` per esperar que la base de dades estiga operativa.
- El `Dockerfile` del frontend prepara un contenidor lleuger de Node amb la comanda `npm run serve`.
- Els ports assignats estan triats entre els que queden lliures a la màquina, i es poden ajustar fàcilment al fitxer `docker-compose.yml` si es necessita.
