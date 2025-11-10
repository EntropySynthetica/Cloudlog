docker network create cloudlog

docker run --name mysql-server \
  --network cloudlog \
  -e MYSQL_ROOT_PASSWORD=rootpass \
  -e MYSQL_DATABASE=cloudlog \
  -e MYSQL_USER=cloudlog \
  -e MYSQL_PASSWORD=cloudlogpass \
  -p 3306:3306 \
  -d mysql:8.0.39-debian

docker run --name cloudlog \
  --network cloudlog \
  -e BASEURL=https://172.18.25.6:8443 \
  -e DBDATABASE=cloudlog \
  -e DBHOSTNAME=mysql-server \
  -e DBUSERNAME=cloudlog \
  -e DBPASSWORD=cloudlogpass \
  -e MYGRID=EN16nu \
  -p 8443:443 \
  -p 8080:80 \
  -d cloudlog:dev