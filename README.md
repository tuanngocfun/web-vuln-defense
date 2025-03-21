# Testing server

Pentesting - A mini E-business project - MVC architecture

# Project setup instructions

- Download and install Docker.
- Clone the repository from https://github.com/tuanngocfun/web-vuln-defense
- Navigate to the project directory.
- Duplicate the `.env.example` file and rename it to `.env`.
- The `.env` file is ready to use. Modify the environment values in this file if customization is needed.
- Go to the secrets folder in the project root and duplicate the `.password.example.txt` file, renaming it to `.password.txt`.
- Open a terminal in the project root (ensure you're in the project root directory).
- Run the following command to install and run the server:

```
docker compose up --watch --build
```

_For Windows users: You can use the helper scripts in the scripts folder from the project root. Run the **`docker-reset.cmd`** script that initializes everything and starts the server._

- Wait until you see the line below in the console:

```
php-server    | [Thu Nov 28 03:16:17.049050 2024] [core:notice] [pid 1:tid 1] AH00094: Command line: 'apache2 -D FOREGROUND'
```

This indicates the server is ready.

- To seed the database, run the following command:

```
docker exec php-server /var/www/docker/migration.sh
```

run the following command:

```
docker exec -it php-server sh
ls -l /var/www/docker/migration.sh
chmod +x /var/www/docker/migration.sh
```

when you change files in the project to diff location

```
sudo chown $USER:$USER /path/to/php-reverse-shell.php
```

for reverse shell work with php

```
 ngoc  (e) base  …  selected-topics  codes  heuristic-network ip a | grep "inet " | grep -v "127.0.0.1""
    inet 172.19.129.155/22 brd 172.19.131.255 scope global dynamic noprefixroute wlp0s20f3
    inet 192.168.122.1/24 brd 192.168.122.255 scope global virbr0
    inet 100.94.98.113/32 scope global tailscale0
    inet 172.17.0.1/16 brd 172.17.255.255 scope global docker0
```

get the the ip address inet with global dynamic to assign into the php-reverse-shell.php

_Alternatively, on Windows, you can run the **`migration.cmd`** script._

- After seeing "Successfully seeding database," the setup is complete and the project is ready to go.<br>
  **The server is listening on http://localhost:9000**
