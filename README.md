# docker-php

It's a docker project containing the basic stuff to develop something using:

- PHP 7.x + FPM (running on port 9000)
- Nginx (running on port 80)
- MySQL (running on port 3306)
- Maildev (running on port 1080 and 1025)

You should add to your `/etc/hosts` file the following domain:

```
127.0.0.1 app.localhost
```

## Running the project

Considering that you already have the docker tool (including `docker-compose`) installed in your operating system, in order to initalize the services just run this command in the root folder of this project:

```bash
docker-compose up -d
```

And to stop the execution of the services:

```bash
docker-compose down
```

To gain access to one container you can run the following command:

```bash
docker-compose run composer bash
```

Considering that the services are running you can access them using a browser through the following address [http://app.localhost/](http://app.localhost/).

Feel free to use this receipt or modify it according to your requirements.
