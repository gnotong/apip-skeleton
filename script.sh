#!/bin/bash

path=`pwd`
dirname=`basename $path | awk '{print tolower($0)}'`

# Checks if the project was created
if [[ "$dirname" == "" ]];then
    exit 1;
fi

# Add the suffix '-service'
if [[ "$dirname" != *"-service" ]];then
    dirname="$dirname-service"
fi

env_file=.env
if test -f "$env_file"; then
  echo "MS_NAME=$dirname" >> $env_file
  printf "==> The MS_NAME id $dirname. If you want to change it please edit the .env file\n"
fi

# Changes the api platform url prefix
apip_route_config_file=config/routes/api_platform.yaml
if test -f "$apip_route_config_file"; then
  sed -i 's:\/api:/:g' $apip_route_config_file
  printf "==> The api url prefix has been updated in api_platform.yaml: from /api to / \n"
fi

# Creates the test dbname and replaces the default one in de .env.test file
env_test_file=.env.test
if test -f "$env_test_file"; then
  formatted_db_name=$(echo "$dirname" | tr - _)
  db_name=$formatted_db_name"_test"
  sed -i "s:db_name_test:$db_name:g" $env_test_file
  printf "==> The test database name is $db_name if you want to change it please edit the file .env.test \n"
fi

cp docker/php/.docker.env.example .docker.env