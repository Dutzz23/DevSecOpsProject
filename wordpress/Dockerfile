FROM wordpress:latest


RUN apt-get update && apt-get install -y \
    vim \
    less \
    curl \
    wget \
    unzip \
    git \
    mariadb-client

RUN rm -rf /var/lib/apt.lists/*

USER www-data