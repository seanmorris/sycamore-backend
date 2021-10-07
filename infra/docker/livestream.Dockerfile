ARG TAG

FROM debian:buster-20210927-slim AS base
MAINTAINER Sean Morris <sean@seanmorr.is>

RUN set -eux; \
	apt-get update; \
	apt-get install -y \
		nginx \
		libpcre3-dev \
		libssl-dev \
		zlib1g-dev \
		libnginx-mod-rtmp \
		ffmpeg \
		libvo-aacenc0 \
		libvo-aacenc-dev;

COPY ./infra/livestream/nginx.conf /etc/nginx/nginx.conf

CMD ["nginx"]

FROM base AS test
FROM base AS dev
