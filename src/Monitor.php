<?php

namespace Binism\ErrorMonitoring;

class Monitor
{
    private $basePath;
    private $token;
    private $host = 'http://192.168.1.66:8081';

    public function __construct($token)
    {
        $this->basePath = dirname(debug_backtrace()[0]['file']);
        $this->token = $token;
    }

    public static function init($token)
    {
        $errorHandler = new static($token);
        $errorHandler->handleErrors();
    }

    public function handleErrors()
    {
        set_error_handler([$this, 'errorHandler']);
    }

    public function errorHandler($errno, $errstr, $errfile, $errline)
    {
        $systems = ['systems_domain' => $_SERVER['SERVER_NAME'], 'systems_dbName' => 'dbname'];

        $trace = debug_backtrace();
        foreach ($trace as $key => $t) {
            $trace[$key]['file'] = str_replace($this->basePath, "basePath", $t['file']);
            foreach ($trace[$key]['args'] as $argkey => $arg) {
                if (gettype($arg) === 'string') {
                    $trace[$key]['args'][$argkey] = str_replace($this->basePath, "basePath", $arg);
                }
            }
        }

        $error_array = ['errorlanguage' => 'php', 'errorMessage' => $errstr, 'errorCode' => $errno, 'errorFile' => str_replace($this->basePath, "basePath", $errfile), 'errorLine' => $errline, 'errorTrace' => json_encode($trace),];

        $postFields = array_merge($error_array, $systems);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "{$this->host}/api/{$this->token}/errors");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        $output = curl_exec($ch);
        curl_close($ch);
    }
}
