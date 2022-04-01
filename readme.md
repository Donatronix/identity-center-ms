### updates

To allow installation of sumra/sdk as a repository:

Dockerfile: change workdir to var/www/htm/web

docker-compose: change the volume mapping from ./web:/var/www/html:rw to ./::/var/www/html:rw

start dev using docker-compose up -d instead of ./deploy.sh build ...start

