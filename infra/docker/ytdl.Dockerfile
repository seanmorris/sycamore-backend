ARG TAG

FROM debian:buster-20210927-slim AS base
MAINTAINER Sean Morris <sean@seanmorr.is>

RUN set -eux; \
	apt-get update; \
	apt-get install -y wget python; \
	wget https://yt-dl.org/downloads/latest/youtube-dl -O /usr/local/bin/youtube-dl; \
	chmod a+rx /usr/local/bin/youtube-dl;

WORKDIR /app/data/local/ytdl

ENTRYPOINT ["youtube-dl"]

FROM base AS test
FROM base AS dev
