ARG PHP_VERSION=8.2.30
#
FROM php:${PHP_VERSION}-cli
#
ARG APP_ROOT
ARG XDEBUG_PORT
ARG WSL_IP
#
ENV COMPOSER_ALLOW_SUPERUSER=1 \
  DEBIAN_FRONTEND=noninteractive \
  LANG=fr_FR.UTF-8 \
  TZ=Europe/Paris \
  APP_ROOT=$APP_ROOT \
  XDEBUG_PORT=$XDEBUG_PORT \
  WSL_IP=$WSL_IP
#
RUN apt-get update \
  && apt-get upgrade -y \
  && apt-get install -y \
  libgcc1 \
  libgssapi-krb5-2 \
  libstdc++6 \
  procps \
  libcurl4 \
  wget \
  libicu-dev \
  vim nano \
  libxml2-dev \
  git \
  zlib1g-dev libzip-dev zip unzip \
  libssl-dev libssl-doc libsasl2-dev \
  locales \
  libmcrypt-dev \
  libonig-dev \
  locate \
  lsb-release \
  dnsutils iproute2 inetutils-ping netcat-traditional \
  jq \
  rsync \
  yamllint \
  # Xdebug
  && pecl install xdebug-3.3.2 \
  && docker-php-ext-enable xdebug \
  # php-xml & zip
  && docker-php-ext-install xml zip \
  # timezone
  && ln -snf /usr/share/zoneinfo/${TZ} /etc/localtime && echo ${TZ} > /etc/timezone \
  # Clean up
  && apt-get autoremove -y && apt-get clean -y && rm -rf /var/lib/apt/lists/*
#
# Xdebug CLI config for VSCode debugging
COPY .docker/php-config/xdebug-cli.ini.temp /tmp/xdebug-cli.ini.temp
#
RUN apt-get update && apt-get install -y --no-install-recommends gettext \
  && apt-get clean -y && rm -rf /var/lib/apt/lists/*
#
RUN envsubst < /tmp/xdebug-cli.ini.temp > /usr/local/etc/php/conf.d/99-xdebug-cli.ini
#
# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
#
# Install Node.js and pnpm via nvm
ENV NVM_DIR="/root/.nvm"
RUN curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.40.4/install.sh | bash \
  && \. "$NVM_DIR/nvm.sh" \
  && nvm install 24 \
  && nvm use 24 \
  && corepack enable pnpm
ENV PATH="$NVM_DIR/versions/node/v24/bin:${PATH}"
#

ENV PATH="/usr/local/bin:${PATH}"

USER root

ENV PATH="${APP_ROOT}/vendor/bin:/root/.local/bin:${PATH}"
