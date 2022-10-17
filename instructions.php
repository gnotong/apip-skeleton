#!/usr/bin/env php
<?php

echo "\n\n\033[32m ======================================================================== \033[0m";
echo "\n\033[32m ==== FOLLOW THESE CUSTOM INSTRUCTIONS TO FINISH SETTING UP YOUR APP ==== \033[0m";
echo "\n\033[32m ======================================================================== \033[0m";

echo "\n\n=> Donot forget to define the MS_NAME in the .env file. Example: MS_NAME=test-service";
echo "\n\n=> Change the api platform url prefix from `/api` to `/` in config/routes/api_platform.yaml";
echo "\n\n=> Donot forget to add an entry in the /etc/hosts file. Example: 127.0.0.1 test-service.local";
echo "\n\n=> Donot forget to enable the reverse proxy";
echo "\n\n=> Access your application using a urlof this type `http://test-service.local`";