Add the following line to ow_includes/config.php:
- define('ACCESS_WEB_SERVICE', true);


Add rabbitmq to server to increase performance of sending notifications
- requirement: ext-bcmath extension for php
- create service to run rabbitmq/receive.php
- create cron job to hold up the above service

Add the following line to ow_includes/config.php
- define('RABBIT_HOST', 'localhost');
- define('RABBIT_PORT', '5672');
- define('RABBIT_USER', 'guest');
- define('RABBIT_PASSWORD', 'guest');

RabbitMQ installation in Ubuntu:

* For Ubuntu 16.04 use xenial instead of bionic

```bash
## Install RabbitMQ signing key
sudo apt-key adv --keyserver "hkps.pool.sks-keyservers.net" --recv-keys "0x6B73A36E6026DFCA"

## Install apt HTTPS transport
sudo apt-get install apt-transport-https

## Add Bintray repositories that provision latest RabbitMQ and Erlang 21.x releases
sudo tee /etc/apt/sources.list.d/bintray.rabbitmq.list <<EOF
deb https://dl.bintray.com/rabbitmq-erlang/debian bionic erlang-21.x
deb https://dl.bintray.com/rabbitmq/debian bionic main
EOF

## Update package indices
sudo apt-get update -y

## Install rabbitmq-server and its dependencies
sudo apt-get install rabbitmq-server -y --fix-missing
```

RabbitMQ Service creation:

1- Switch to root user

2- Create file at /etc/systemd/system/motoshub-consume@.service

3- Add these content to file:
```angular2html
[Unit]
Description=RabbitMQ Consumer for %i
Requires=rabbitmq-server.service
After=rabbitmq-server.service

[Service]
User=www-data
ExecStart=/usr/bin/env php "/var/www/html/%i/rabbitmq/receive.php"
StandardOutput=file:/var/log/motoshub-consume/%i.log
StandardError=inherit
Restart=always

[Install]
WantedBy=multi-user.target

```

4- create log folder:
```angular2html
sudo mkdir /var/log/motoshub-consume
sudo chown www-data:www-data /var/log/motoshub-consume
```

5- systemctl enable motoshub-consume@dir

6- systemctl start motoshub-consume@dir

7- systemctl restart motoshub-consume@dir.service



