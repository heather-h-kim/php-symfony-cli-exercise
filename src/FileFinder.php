<?php

namespace App\Cli;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Finder\Finder;

class FileFinder extends Command
{
    protected String $fileLocation;
    protected String $fileName;

    public function __construct(String $fileLocation, String $fileName){
        $this->fileLocation = $fileLocation;
        $this->fileName = $fileName;
    }

    public function find_file(): array
    {
        $finder = new Finder();
        $finder->files()->in($this->fileLocation)->name($this->fileName);

        foreach ($finder as $file) {
            $contents = json_decode($file->getContents(), true);
        }

        return $contents;
    }
}