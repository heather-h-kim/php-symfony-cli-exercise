<?php

namespace App\Cli;

use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

class ArrayUploader
{
    protected String $fileName;
    protected array $arr;

    public function __construct(String $fileName,array $arr){
        $this->fileName = $fileName;
        $this->arr = $arr;
    }

    public function upload_array(){
        $json = json_encode($this->arr);
        $filesystem = new Filesystem();
        $filesystem->dumpFile($this->fileName, $json);
    }

}