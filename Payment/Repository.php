<?php
namespace Payment;

abstract class Repository
{
    private string $file;

    public function __construct($file)
    {
        $this->file = $file;
    }

    public function readStorage(): array{
        $file = file_get_contents($this->file);
        return (array)json_decode($file);
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function getData(string $key): mixed
    {
        $data = $this->readStorage();
        return $data[$key];
    }

    /**
     * @param array $filters
     *
     * @return array
     */
    public function findBy(array $filters): array
    {
        $result = [];
        $data = $this->readStorage();

        foreach ($data as $row) {
            foreach ($filters as $key => $value) {
                if ($row[$key] !== $value) {
                    continue 2;
                }
            }
            $result[] = $row;
        }

        return $result;
    }

    /**
     * @param $key
     * @param $value
     * @return void
     */
    public function saveData($key, $value): void
    {
        $data = $this->readStorage();
        $data[$key] = $value;
        $this->save($data);
    }

    /**
     * @param $value
     * @return void
     */
    public function addData($value): void
    {
        $data = $this->readStorage();
        $data[] = $value;
        $this->save($data);
    }

    /**
     * @param $data
     * @return void
     */
    private function save($data): void
    {
        $json = json_encode($data);
        file_put_contents($this->file, $json);
    }

}