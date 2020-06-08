#
#--------------------------------------------------------------------------
# Image Setup
# $REF: $
#--------------------------------------------------------------------------
#

# FROM node:9.11
FROM node:12-alpine

USER root
WORKDIR /root

COPY ./su-node /sbin/su-node

RUN apk update \
    && apk add --no-cache bash shadow sudo su-exec \
                 curl nano wget

RUN npm i -g npm \
    && npm i -g gulp-cli \
    && npm i -g cross-env   \
    && rm -rf /root/.npm    \
    && echo "cache=/tmp/.npm" >> /.npmrc \
    && echo "prefix=/tmp" >> /.npmrc     \
    && chmod +x /sbin/su-node

EXPOSE 3000

WORKDIR /var/www

CMD ["tail", "-f", "/dev/null"]


