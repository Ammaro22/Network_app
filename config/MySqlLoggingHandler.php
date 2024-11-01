<?php

use Illuminate\Support\Facades\DB;
use Monolog\Handler\AbstractProcessingHandler;

class MySqlLoggingHandler extends AbstractProcessingHandler
{

    private  string $table;

    protected function write(\Monolog\LogRecord $record): void
    {
        $data = array(
            'message'         => $record['message'],
            'context'         => json_encode($record['context']),
            'level'         => $record['level'],
            'level_name'         => $record['level_name'],
            'channel'         => $record['channel'],
            'record_datetime'         => $record['datetime']->format('Y-m-d H:i:s'),
            'extra'         => json_encode($record['extra']),
            'formatted'         => $record['formatted'],
            'remote_addr'         => $_SERVER['REMOTE_ADDR']??null,
            'user_agent'         => $_SERVER['HTTP_USER_AGENT']??null,
            'created_at'         => date('Y-m-d H:i:s'),

        );
        DB::table('log')->insert($data);



    }


}
