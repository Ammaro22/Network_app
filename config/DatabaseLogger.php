<?php

use Monolog\Logger;

class DatabaseLogger
{

    public function __invoke(array $config):Logger
    {

        return new Logger('MySqlLoggingHandler',[new MySqlLoggingHandler($config['level'])]);

    }

}
