# Depoly
```
docker run -d -p 3000:3000 --restart always --log-opt max-size=100m --log-opt max-file=2 -v $(pwd)/grafana.ini:/etc/grafana/grafana.ini -e "GF_SECURITY_ADMIN_PASSWORD=123456" --name grafana grafana/grafana
```
