# MentorLMS Production Deployment

This procedure deploys MentorLMS with Docker Compose, PHP-FPM, Nginx, MySQL 8, and Redis. MySQL data and uploaded files use named Docker volumes so they survive application image updates.

## Server requirements

- Linux server with Docker Engine and the Docker Compose plugin
- DNS A/AAAA record pointing your domain to the server
- 2 CPU cores, 4 GB RAM, and at least 20 GB free disk space
- Firewall ports 80 and 443 open; do not expose MySQL or Redis publicly

## First deployment

Run these commands from the repository root on the server:

```bash
git clone <repository-url> mentor_lms
cd mentor_lms
cp .env.production.example .env.production
cp docker/env/mysql.prod.env.example docker/env/mysql.prod.env
```

Edit both copied files. Use the same database name, username, and password in both files. Set `APP_URL` to the real HTTPS URL and create unique strong passwords. Obtain a certificate for the domain before starting Nginx. Start the data and PHP services first, create the Nginx container, install the certificate, and then start Nginx:

```bash
docker compose -f docker-compose.prod.yaml up -d --build mysql redis php
docker compose -f docker-compose.prod.yaml create nginx
docker compose -f docker-compose.prod.yaml cp ./cert.pem nginx:/etc/nginx/ssl/cert.pem
docker compose -f docker-compose.prod.yaml cp ./key.pem nginx:/etc/nginx/ssl/key.pem
docker compose -f docker-compose.prod.yaml start nginx
docker compose -f docker-compose.prod.yaml exec php php artisan key:generate --force
docker compose -f docker-compose.prod.yaml exec php php artisan migrate --force
docker compose -f docker-compose.prod.yaml exec php php artisan storage:link
docker compose -f docker-compose.prod.yaml exec php php artisan optimize
```

The bundled Nginx configuration redirects port 80 to HTTPS and expects certificates at `cert.pem` and `key.pem` in the `ssl_certs` volume.

For Let's Encrypt, obtain the certificate on the host with Certbot or use a TLS reverse proxy in front of this stack. Renew the certificate before expiry, copy the renewed files into the volume, and restart Nginx. Do not commit certificates or environment files.

## Database

MySQL is the `mysql` Compose service, so Laravel must use `DB_HOST=mysql`, not `127.0.0.1`. MySQL data is stored in the `mysql_data` volume. The initial database and application user are created from `docker/env/mysql.prod.env` on the first startup.

Check the database and migrations:

```bash
docker compose -f docker-compose.prod.yaml ps
docker compose -f docker-compose.prod.yaml exec php php artisan about
docker compose -f docker-compose.prod.yaml exec php php artisan migrate:status
```

Create a backup before upgrades or destructive maintenance:

```bash
mkdir -p backups
docker compose -f docker-compose.prod.yaml exec -T mysql \
  mysqldump -u root -p"$(grep '^MYSQL_ROOT_PASSWORD=' docker/env/mysql.prod.env | cut -d= -f2-)" \
  --single-transaction --routines --triggers mentor_lms_prod > "backups/mentor_lms_$(date +%Y%m%d_%H%M%S).sql"
```

Restore a backup during a maintenance window:

```bash
docker compose -f docker-compose.prod.yaml exec -T mysql \
  mysql -u root -p"<root-password>" mentor_lms_prod < backups/backup.sql
```

Keep backups outside the server as well. A Docker volume is persistence, not a backup.

## Updates and operations

```bash
git pull
docker compose -f docker-compose.prod.yaml up -d --build
docker compose -f docker-compose.prod.yaml exec php php artisan migrate --force
docker compose -f docker-compose.prod.yaml exec php php artisan optimize
docker compose -f docker-compose.prod.yaml logs -f --tail=100 php nginx
```

The PHP container runs PHP-FPM and the Laravel queue worker under Supervisor. Confirm queued jobs are being processed with `docker compose -f docker-compose.prod.yaml logs php`. If scheduled tasks are added later, run Laravel's scheduler from host cron every minute:

```cron
* * * * * cd /srv/mentor_lms && docker compose -f docker-compose.prod.yaml exec -T php php artisan schedule:run >> /dev/null 2>&1
```

## Security checklist

- Keep `.env.production`, MySQL credentials, and TLS private keys out of Git.
- Set `APP_DEBUG=false` and use HTTPS before accepting users.
- Restrict the server firewall to ports 80 and 443.
- Back up the `mysql_data` and `storage` volumes regularly.
- Review `docker compose -f docker-compose.prod.yaml ps` and logs after every deployment.
