<?php

class PostCreateProject
{
  public static function dumpProjectName(Event $event) {
    $prefixSystem = $event->getComposer()->getPackage();
    $a = print_r($prefixSystem, true);
  }
}
