ARG TAG

FROM debian:bullseye-20210816-slim AS base
MAINTAINER Sean Morris <sean@seanmorr.is>

RUN set -eux; \
	apt-get update; \
	apt-get install --no-install-recommends -y gnupg apt-transport-https; \
	curl -sL https://deb.nodesource.com/setup_14.x | bash - ; \
	apt update; \
	apt install -y nodejs npm; \
	npm i -g brunch;


WORKDIR "/app"
CMD ["brunch", "watch", "-sn"]

RUN npm install

FROM base AS test
FROM base AS dev
FROM base AS web
FROM base AS worker
