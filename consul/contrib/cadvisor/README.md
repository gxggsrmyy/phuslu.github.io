# Depoly
```
docker run -d -p 9099:8080 --restart always --log-opt max-size=100m --log-opt max-file=2 --volume=/:/rootfs:ro --volume=/var/run:/var/run:rw --volume=/sys:/sys:ro --volume=/var/lib/docker/:/var/lib/docker:ro --name=cadvisor google/cadvisor:latest
```
