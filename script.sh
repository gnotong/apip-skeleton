#!/bin/bash

path=`pwd`
dirname=`basename $path | awk '{print tolower($0)}'`

# Add the suffix '-service'
if [[ "$dirname" != *"-service" ]];then
    dirname="$dirname-service"
fi

echo "MS_NAME=$dirname" >> .env
printf "==> The MS_NAME id $dirname. If you want to change it please edit the .env file\n"

sed -i "s:prefix: \/api:prefix: \/" config/routes/api_platform.yaml
printf "==> The api url prefix has been updated in api_platform.yaml: from /api to / \n"
