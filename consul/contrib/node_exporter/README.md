# Depoly
```
docker run -d --restart always --log-opt max-size=100m --log-opt max-file=2 --net=host -e PORT=9100 -e "SSH_HOST=192.168.2.2" -e "SSH_USER=phuslu" -e "SSH_PASS=123456" --name "node_exporter_for_lab" phuslu/remote_node_exporter
docker run -d --restart always --log-opt max-size=100m --log-opt max-file=2 --net=host -e PORT=9101 -e "SSH_HOST=192.168.2.1" -e "SSH_USER=admin"  -e "SSH_PASS=123456" --name "node_exporter_for_gw" phuslu/remote_node_exporter
docker run -d --restart always --log-opt max-size=100m --log-opt max-file=2 --net=host -e PORT=9102 -e "SSH_HOST=bwg.phus.lu" -e "SSH_USER=phuslu" -e "SSH_PASS=123456" --name "node_exporter_for_bwg" phuslu/remote_node_exporter
docker run -d --restart always --log-opt max-size=100m --log-opt max-file=2 --net=host -e PORT=9103 -e "SSH_HOST=vir.phus.lu" -e "SSH_USER=phuslu" -e "SSH_PASS=123456" --name "node_exporter_for_vir" phuslu/remote_node_exporter
```
