# Installation

The aim of this page is to explain you how to build and run the project.

## Pre-requisites

Ensure that, on your system you have :
* [Docker](https://docs.docker.com/get-docker/)
* [Git](https://git-scm.com/book/en/v2/Getting-Started-Installing-Git)
* [Make](https://linuxhint.com/install-build-essential-ubuntu/)
* [Gcloud CLI](https://cloud.google.com/sdk/docs/install?hl=en)

## Building the project

First, clone the repository by following this command if you are using the SSH protocol :
````
git clone git@github.com:ubitransports/service-repository.git
````

Edit the `.env` file and add this

```
MS_NAME=your-service-name
```

Change the api platform url prefix 

from `/api` to `/` 

in `config/routes/api_platform.yaml`

Launch the following command, it will create some useful files

````
make start
````

Add the service domain name to your localhost IP address by modifying your hosts file :
````
sudo nano /etc/hosts
````

For example
````
127.0.0.1 your-service-name.local
````

Start the reverse proxy :
````
cd ~/Ubitransport/reverse-proxy && make start
````

Access api documentation

````
http://your-service-name.local
````

## Some commands

If you just installed the project, it should already run, but to start it manually, run this command :
````
make start
````

To stop the project :
````
make stop
````

If you want to reset everything and install it from scratch
````
make reset
````
