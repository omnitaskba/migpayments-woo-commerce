<?php
class MigpaymentsResponse
{
    public $data;
    public $error;

    public function __construct($error = null, $data = [])
    {
        $this->error = $error;
        $this->data = $data;
    }

}