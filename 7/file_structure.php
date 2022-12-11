<?php

namespace Assignment;

require_once "enums/Command.php";

$size_limit = 100000;
echo "SIZE OF ALL DIRECTORIES LOWER THAN SIZE IS: " . (new FileSystem(file_get_contents("data.txt"), $size_limit))->getSmallerThanSize() . PHP_EOL;

$whole_size = (new FileSystem(file_get_contents("data.txt"), $size_limit))->getSize();
echo "SIZE OF OUTERMOST DIRECTORY IS: $whole_size" . PHP_EOL;

$space_available = 70000000;
$space_needed = 30000000;
$space_currently_available = $space_available - $whole_size;
$space_still_needed = $space_needed - $space_currently_available;

echo "SPACE CURRENTLY AVAILABLE IS: $space_currently_available" . PHP_EOL;
echo "SPACE STILL NEEDED FOR UPDATE: $space_still_needed" . PHP_EOL;

echo "SMALLEST DIRECTORY SPACE TO BE DELETED IS: " . (new FileSystem(file_get_contents("data.txt"), $size_limit, $space_still_needed))->getSmallestNeeded();

class FileSystem
{
    private array $directory_path;
    private Command|null $current_command;
    private int|null $size_limit;
    private int|null $space_still_needed;
    private int $smallest_needed_for_deletion;

    private int $smaller_than_limit_size = 0;

    public function __construct(string $terminal_output, int $size_limit = null, int $space_still_needed = null)
    {
        $this->directory_path = [];
        $this->current_command = null;
        $this->size_limit = $size_limit;
        $this->space_still_needed = $space_still_needed;
        $this->smallest_needed_for_deletion = PHP_INT_MAX;

        // Read terminal output
        $exploded_output = explode(PHP_EOL, $terminal_output);
        foreach ($exploded_output as $line) {
            $this->readLine($line);
        }
    }

    public function getSize(): int
    {
        return $this->directory_path[0]->getDirectorySize();
    }

    public function getSmallerThanSize(): int
    {
        return $this->smaller_than_limit_size;
    }

    public function getSmallestNeeded(): int
    {
        return $this->smallest_needed_for_deletion;
    }

    private function readLine(string $line)
    {
        if (str_starts_with($line, "$")) {
            if (preg_match('/\$ cd (.*)/', trim($line), $matches)) {
                $this->current_command = Command::CHANGE;
                $this->changeDirectory($matches[1]);
            } else if ($line === "$ ls") {
                $this->current_command = Command::LIST;
            } else {
                throw new \Error("Cannot execute command $line");
            }
        } else {
            // Check if we are listing files and directories and add to children of current directory
            if ($this->current_command === Command::LIST) {
                if (preg_match('/(\d+) (.*)/', $line, $matches)) {
                    // Add file to current directory
                    $this->directory_path[count($this->directory_path) - 1]->addChildFile(new File(name: $matches[2], size: $matches[1]));

                    // Add file size to whole directory path
                    array_map(fn ($dir) => $dir->addSize($matches[1]), $this->directory_path);
                }
            }
        }
    }

    private function changeDirectory(string $directory_name)
    {
        if ($directory_name === "..") {
            $last_directory = array_pop($this->directory_path);

            // Check if popped directory size fits size limit and add to whole size
            if ($last_directory->getDirectorySize() < $this->size_limit) {
                $this->smaller_than_limit_size += $last_directory->getDirectorySize();
            }

            // Check if popped directory will create enough space for update
            if ($last_directory->getDirectorySize() > $this->space_still_needed && $last_directory->getDirectorySize() < $this->smallest_needed_for_deletion) {
                $this->smallest_needed_for_deletion = $last_directory->getDirectorySize();
            }
        } else {
            $this->directory_path[] = new Directory(name: $directory_name);
        }
    }
}

class Directory
{
    private int $size = 0;
    private string $name;
    private array $files;

    public function __construct(string $name, array $files = [])
    {
        $this->files = $files;
        $this->name = $name;
    }

    public function addChildFile(File $file)
    {
        $this->files[] = $file;
    }

    public function calculateDirectorySize()
    {
        $this->size = 0;
        foreach ($this->files as $file) {
            $this->size += $file->getSize();
        }

        return $this;
    }

    public function getDirectorySize(): int
    {
        return $this->size;
    }

    public function getChildrenFiles(): array
    {
        return $this->files;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function addSize(int $size)
    {
        $this->size += $size;
    }
}

class File
{
    private int $size;
    private string $name;

    public function __construct(string $name, int $size)
    {
        $this->name = $name;
        $this->size = $size;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
