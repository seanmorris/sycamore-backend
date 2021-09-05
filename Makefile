#!make
.PHONY: init

SHELL    = /bin/bash
PROJECT  ?=sycamore
REPO     ?=seanmorris

-include .env
-include .env.${TARGET}

-include vendor/seanmorris/ids/Makefile

init: ${TARGET_COMPOSE}
	@ docker run --rm \
		-v $$PWD:/app \
		-v $${COMPOSER_HOME:-$$HOME/.composer}:/tmp \
		composer -vvv require seanmorris/ids:dev-master
	@ make -s
	@ make -s start-fg

mobileapp:
	cd mobile \
	&& expo start

local-cert:
	openssl req -x509 -out data/local/ssl/localhost.crt -keyout data/local/ssl/localhost.key \
	  -newkey rsa:2048 -nodes -sha256 \
	  -subj '/CN=localhost' -extensions EXT -config <( \
	   printf "[dn]\nCN=localhost\n[req]\ndistinguished_name = dn\n[EXT]\nsubjectAltName=DNS:localhost\nkeyUsage=digitalSignature\nextendedKeyUsage=serverAuth")
