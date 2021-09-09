ARG TAG

FROM debian:bullseye-20210816-slim AS base
MAINTAINER Sean Morris <sean@seanmorr.is>

RUN set -eux; \
	apt-get update; \
	apt-get install -y \
		nginx \
		libpcre3-dev \
		libssl-dev \
		zlib1g-dev \
		libnginx-mod-http-lua \
		libnginx-mod-rtmp \
		ffmpeg \
		libvo-aacenc0 \
		libvo-aacenc-dev;

COPY ./infra/balancer/nginx.conf /etc/nginx/nginx.conf

CMD ["nginx"]

FROM base AS test
FROM base AS dev
