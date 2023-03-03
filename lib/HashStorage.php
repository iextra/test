<?php

namespace RDN\Error;

class HashStorage
{
    protected string $storageFile;

    public function __construct(string $documentRoot)
    {
        $this->storageFile = $documentRoot . '/upload/error-hashes.txt';
    }

    public function add(string $hash): void
    {
        $this->addMany([$hash]);
    }

    public function addMany(array $hashes): void
    {
        $data = $this->getData();

        foreach ($hashes as $hash) {
            if (! in_array($hash, $data)) {
                $data[] = $hash;
            }
        }

        file_put_contents($this->storageFile, json_encode($data));
    }

    public function has(string $hash): bool
    {
        return in_array($hash, $this->getData());
    }

    public function delete(string $hash): void
    {
        if ($this->has($hash)) {
            $data = $this->getData();
            unset($data[array_search($hash, $data)]);
            file_put_contents($this->storageFile, json_encode($data));
        }
    }

    public function clear(): void
    {
        if (is_file($this->storageFile)) {
            unlink($this->storageFile);
        }
    }

    protected function getData(): array
    {
        if (is_file($this->storageFile)) {
            $data = json_decode(file_get_contents($this->storageFile), true);
            return is_array($data) ? $data : [];
        }

        return [];
    }
}
