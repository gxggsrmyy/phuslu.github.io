# Depoly
```
(sshpass -p Aa123456 autossh -M 0 -o UserKnownHostsFile=/dev/null -o StrictHostKeyChecking=no -o ServerAliveInterval=30 -o ServerAliveCountMax=8 -NgC -R 127.0.0.1:3001:127.0.0.1:80 admin@192.168.2.1 &)
```
