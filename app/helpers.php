<?php

function log_error(string $module, string $method, string $message)
{
    \App\Services\ErrorLogService::save($module, $method, $message);
}
