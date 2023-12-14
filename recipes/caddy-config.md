Example of [Caddy](https://caddyserver.com/) configuration

Caddyfile
```
your.domain.com {
	encode zstd gzip
	root * /path/to/public
	file_server
	reverse_proxy localhost:8080
}
```
