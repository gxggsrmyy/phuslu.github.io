# Depoly
```
docker run -d -p 9090:9090 --restart always --log-opt max-size=100m --log-opt max-file=2 -v $(pwd)/prometheus.yml:/etc/prometheus/prometheus.yml --name prometheus prom/prometheus
```
