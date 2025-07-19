# Optimized VWA Classifieds

An optimization for the [Visual Web Arena](https://arxiv.org/pdf/2401.13649) classified environment.

The current environment is very large (78GB). Most of the space (~73 GB) is from PNG files for classifieds
* 336,564 png images (84,141 uploads with 4 variations each).
* 48 JPEGs (48, from 12 uploads with 4 variations each)

By converting these to AVIF, we can optimize the uploads dir from **73 GB** to **4.9G GB**, and the container image from 77.8 GB to 5.69 GB. This repo also includes a few other improvements for reproducibility (with a preconfigured mysql container that reset to a known good state on startup).

## Using the pre-built images

You can use `ghcr.io/bgrins/vwa_classifieds_web:latest` and `ghcr.io/bgrins/vwa_classifieds_db:latest` directly. See [docker-compose.images.yml] for an example.

## Preparing the environment

You shouldn't have to do these steps if you just want to run the environment. But if you want to customize, here are some tips to reproduce.

* Follow [instructions here](https://github.com/web-arena-x/visualwebarena/tree/89f5af29305c3d1e9f97ce4421462060a70c9a03/environment_docker#classifieds-website) to pull the docker compose and SQL files.
* `docker compose -f docker-compose.original.yml up -d` (slow, needs to download the full original image)
* Sanity checks `docker ps -s | grep classifieds` -> `virtual 78.4GB`, `docker exec classifieds du -sh /usr/src/myapp/oc-content/uploads` -> `73G`, 
* `./prepare-build.sh` (slow, copies all of the images onto host). This also removes a couple large log files from the original image (~50MB and 20MB respectively).
* Make sure [libvips](https://www.libvips.org/) is installed on your machine
* `./convert.sh` (slow, converts to AVIF and removes the PNG).
* Sanity checks: `find myapp/oc-content/uploads -name "*.avif" -type f | wc -l` -> `336613` and `find myapp/oc-content/uploads \( -name "*.png" -o -name "*.jpg" -o -name "*.jpeg" \) -type f | wc -l` -> 3 (a few non user-uploaded assets)

## Update DB Paths
* First, `docker exec classifieds_db mysqldump --no-tablespaces -u root osclass > mysql-baked/classifieds_import.sql && GIT_PAGER="less -S" git diff` to confirm there are not any meaningful changes.
* `docker-compose exec db mysql -u root osclass -e "SELECT pk_i_id, s_content_type, s_path FROM oc_t_item_resource LIMIT 5;"`
* Test a single change: `docker-compose exec db mysql -u root osclass -e "UPDATE oc_t_item_resource SET s_extension = 'avif', s_content_type = 'image/avif' WHERE pk_i_id = 1;"` and `GIT_PAGER="less -S" git diff`
* `docker-compose exec db mysql -u root osclass -e "UPDATE oc_t_preference SET s_value = 'png,gif,jpg,jpeg,avif' WHERE s_section = 'osclass' AND s_name = 'allowedExt';"`
* `docker-compose exec db mysql -u root osclass -e "UPDATE oc_t_item_resource SET s_extension = 'avif', s_content_type = 'image/avif' WHERE s_extension IN ('png', 'jpg', 'jpeg');"`
* Test a single change: `docker-compose exec db mysql -u root osclass -e "UPDATE oc_t_item_resource SET s_extension = 'avif', s_content_type = 'image/avif' WHERE pk_i_id = 1;"` 
* `docker exec classifieds_db mysqldump --no-tablespaces -u root osclass > mysql-baked/classifieds_import.sql && GIT_PAGER="less -S" git diff`

## Testing

* Load [http://127.0.0.1:9980/].
* Log in with [these credentials](https://github.com/web-arena-x/visualwebarena/blob/89f5af29305c3d1e9f97ce4421462060a70c9a03/browser_env/env_config.py#L84) (`blake.sullivan@gmail.com` / `Password.123` and make a change, confirm it persists.
* You can [`curl -X POST "http://127.0.0.1:9980/index.php?page=reset" -d "token=4b61655535e7ed388f0d40a93600254c"`](https://github.com/web-arena-x/visualwebarena/blob/89f5af29305c3d1e9f97ce4421462060a70c9a03/scripts/run_classifieds_som.sh#L21) to confirm a reset (new activity should be gone). Or simply restart the db service (`docker-compose restart db`).

## Other Changes

* Baked the initial state into the mysql image so the golden state is [reset on container restart](mysql-baked/custom-entrypoint.sh) rather than requiring reset endpoint or init scripts. Copied classifieds_restore.sql into `myapp` so `myapp/oc-includes/osclass/controller/reset.php` can still be used if necessary.
* Added health check dependency to the `docker-compose.yml`
* Make the reset flow work by copying classifieds_restore.sql into myapp.

## Building/Publishing

Test locally with:

```
docker compose build
docker compose up -d
```

```
docker build -t ghcr.io/bgrins/vwa_classifieds_web:latest .
docker build -t ghcr.io/bgrins/vwa_classifieds_db:latest ./mysql-baked

docker push ghcr.io/bgrins/vwa_classifieds_web:latest
docker push ghcr.io/bgrins/vwa_classifieds_db:latest

docker tag ghcr.io/bgrins/vwa_classifieds_web:latest ghcr.io/bgrins/vwa_classifieds_web:1
docker tag ghcr.io/bgrins/vwa_classifieds_db:latest ghcr.io/bgrins/vwa_classifieds_db:1

docker push ghcr.io/bgrins/vwa_classifieds_web:1
docker push ghcr.io/bgrins/vwa_classifieds_db:1
```
