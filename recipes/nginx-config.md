Example Nginx config

Nginx can do:
* the TLS termination
* serve static files
* proxy all your workerman apps in the same domain
* ....

nginx.conf
```nginx
server {
    listen 80 default_server;
    listen [::]:80 default_server ipv6only=on;

    # Change to your public dir
    root /var/www/html/your-app/public;
    index index.html index.htm;

    server_name localhost;

    location / {
        try_files $uri $uri/ @backend;
    }

    # Add the ip:port of your app
    location @backend {
         proxy_pass 127.0.0.1:8080; // or localhost:8080;
         proxy_http_version 1.1;
         proxy_set_header Connection "";
    }

    location ~ /\. {
        deny all;
    }
}
```
