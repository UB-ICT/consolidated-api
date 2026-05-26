FROM ubuntu:24.04

LABEL maintainer="Taylor Otwell"

ARG WWWGROUP
ARG NODE_VERSION=22
ARG POSTGRES_VERSION=17

WORKDIR /var/www/html

ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=UTC
ENV SUPERVISOR_PHP_COMMAND="/usr/bin/php -d variables_order=EGPCS /var/www/html/artisan serve --host=0.0.0.0 --port=80"
ENV SUPERVISOR_PHP_USER="sail"

# --------------------------------------------------------------------
# Timezone
# --------------------------------------------------------------------
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime \
    && echo $TZ > /etc/timezone

RUN echo "Acquire::http::Pipeline-Depth 0;" > /etc/apt/apt.conf.d/99custom && \
    echo "Acquire::http::No-Cache true;" >> /etc/apt/apt.conf.d/99custom && \
    echo "Acquire::BrokenProxy     true;" >> /etc/apt/apt.conf.d/99custom

# Core PHP and Tools Installation
RUN apt-get update && apt-get upgrade -y \
    && mkdir -p /etc/apt/keyrings \
    && apt-get install -y gnupg gosu curl ca-certificates zip unzip git supervisor sqlite3 libcap2-bin libpng-dev python3 dnsutils librsvg2-bin fswatch ffmpeg nano \
    && curl -sS 'https://keyserver.ubuntu.com/pks/lookup?op=get&search=0xb8dc7e53946656efbce4c1dd71daeaab4ad4cab6' | gpg --dearmor | tee /etc/apt/keyrings/ppa_ondrej_php.gpg > /dev/null \
    && echo "deb [signed-by=/etc/apt/keyrings/ppa_ondrej_php.gpg] https://ppa.launchpadcontent.net/ondrej/php/ubuntu noble main" > /etc/apt/sources.list.d/ppa_ondrej_php.list \
    && apt-get update \
    && apt-get install -y php8.2-cli php8.2-dev \
    php8.2-pgsql php8.2-sqlite3 php8.2-gd php8.2-imagick \
    php8.2-curl php8.2-mongodb \
    php8.2-imap php8.2-mysql php8.2-mbstring \
    php8.2-xml php8.2-zip php8.2-bcmath php8.2-soap \
    php8.2-intl php8.2-readline \
    php8.2-ldap \
    php8.2-msgpack php8.2-igbinary php8.2-redis php8.2-swoole \
    php8.2-memcached php8.2-pcov php8.2-xdebug \
    && curl -sLS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer \
    && curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg \
    && echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_$NODE_VERSION.x nodistro main" > /etc/apt/sources.list.d/nodesource.list \
    && apt-get update \
    && apt-get install -y nodejs \
    && curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | gpg --dearmor | tee /etc/apt/keyrings/yarn.gpg >/dev/null \
    && echo "deb [signed-by=/etc/apt/keyrings/yarn.gpg] https://dl.yarnpkg.com/debian/ stable main" > /etc/apt/sources.list.d/yarn.list \
    && curl -sS https://www.postgresql.org/media/keys/ACCC4CF8.asc | gpg --dearmor | tee /etc/apt/keyrings/pgdg.gpg >/dev/null \
    && echo "deb [signed-by=/etc/apt/keyrings/pgdg.gpg] http://apt.postgresql.org/pub/repos/apt noble-pgdg main" > /etc/apt/sources.list.d/pgdg.list \
    && apt-get update \
    && apt-get install -y yarn pdftk ghostscript \
    && apt-get install -y mysql-client \
    && apt-get install -y postgresql-client-$POSTGRES_VERSION \
    && apt-get -y autoremove \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# --------------------------------------------------------------------
# Ondrej PHP Repository
# --------------------------------------------------------------------
RUN curl -fsSL "https://keyserver.ubuntu.com/pks/lookup?op=get&search=0xb8dc7e53946656efbce4c1dd71daeaab4ad4cab6" \
    | gpg --dearmor -o /etc/apt/keyrings/ppa_ondrej_php.gpg \
    && echo "deb [signed-by=/etc/apt/keyrings/ppa_ondrej_php.gpg] https://ppa.launchpadcontent.net/ondrej/php/ubuntu noble main" \
    > /etc/apt/sources.list.d/ppa_ondrej_php.list

# --------------------------------------------------------------------
# gRPC Extension
# --------------------------------------------------------------------
RUN apt-get update && \
    apt-get install -y libz-dev zlib1g-dev autoconf build-essential libprotobuf-dev protobuf-compiler && \
    pecl install grpc-1.51.1 && \
    echo "extension=grpc.so" > /etc/php/8.2/cli/conf.d/20-grpc.ini

# --------------------------------------------------------------------
# Microsoft ODBC Driver 18 & SQLSRV
# --------------------------------------------------------------------
RUN curl https://packages.microsoft.com/keys/microsoft.asc | gpg --dearmor > /etc/apt/keyrings/microsoft.gpg \
    && echo "deb [arch=amd64 signed-by=/etc/apt/keyrings/microsoft.gpg] https://packages.microsoft.com/ubuntu/24.04/prod noble main" > /etc/apt/sources.list.d/mssql-release.list \
    && apt-get update \
    && ACCEPT_EULA=Y apt-get install -y msodbcsql18 mssql-tools18 unixodbc-dev \
    && pecl install sqlsrv-5.11.0 pdo_sqlsrv-5.11.0 \
    && echo "extension=sqlsrv.so" > /etc/php/8.2/cli/conf.d/20-sqlsrv.ini \
    && echo "extension=pdo_sqlsrv.so" > /etc/php/8.2/cli/conf.d/20-pdo_sqlsrv.ini \
    && ln -sfn /opt/mssql-tools18/bin/sqlcmd /usr/bin/sqlcmd \
    && ln -sfn /opt/mssql-tools18/bin/bcp /usr/bin/bcp \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# --------------------------------------------------------------------
# User Setup
# --------------------------------------------------------------------
RUN userdel -r ubuntu || true
RUN groupadd --force -g 1337 sail
RUN useradd -ms /bin/bash --no-user-group -g 1337 -u 1337 sail

ENV PATH="/root/.bun/bin:$PATH"

# --------------------------------------------------------------------
# PECL Extensions
# --------------------------------------------------------------------
RUN pecl install grpc-1.51.1 \
    && pecl install swoole \
    && pecl install sqlsrv-5.11.0 \
    && pecl install pdo_sqlsrv-5.11.0 \
    && echo "extension=grpc.so" > /etc/php/8.2/cli/conf.d/20-grpc.ini \
    && echo "extension=swoole.so" > /etc/php/8.2/cli/conf.d/20-swoole.ini \
    && echo "extension=sqlsrv.so" > /etc/php/8.2/cli/conf.d/20-sqlsrv.ini \
    && echo "extension=pdo_sqlsrv.so" > /etc/php/8.2/cli/conf.d/20-pdo_sqlsrv.ini

# --------------------------------------------------------------------
# Optional FPM Registration
# --------------------------------------------------------------------
RUN if [ -d "/etc/php/8.2/fpm/conf.d" ]; then \
    echo "extension=grpc.so" > /etc/php/8.2/fpm/conf.d/20-grpc.ini; \
    echo "extension=swoole.so" > /etc/php/8.2/fpm/conf.d/20-swoole.ini; \
    echo "extension=sqlsrv.so" > /etc/php/8.2/fpm/conf.d/20-sqlsrv.ini; \
    echo "extension=pdo_sqlsrv.so" > /etc/php/8.2/fpm/conf.d/20-pdo_sqlsrv.ini; \
    fi

# --------------------------------------------------------------------
# Allow PHP To Bind To Port 80
# --------------------------------------------------------------------
RUN setcap "cap_net_bind_service=+ep" /usr/bin/php8.2

# --------------------------------------------------------------------
# Sail User
# --------------------------------------------------------------------
RUN userdel -r ubuntu || true

RUN groupadd --force -g 1337 sail \
    && useradd -ms /bin/bash --no-user-group -g 1337 -u 1337 sail

# --------------------------------------------------------------------
# Configuration Files
# --------------------------------------------------------------------
COPY start-container /usr/local/bin/start-container
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY php.ini /etc/php/8.2/cli/conf.d/99-sail.ini

RUN chmod +x /usr/local/bin/start-container

# --------------------------------------------------------------------
# Cleanup
# --------------------------------------------------------------------
RUN apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

EXPOSE 80/tcp

ENTRYPOINT ["start-container"]