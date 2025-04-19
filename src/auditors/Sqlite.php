<?php

namespace markhuot\voyage\auditors;

use DateTime;
use markhuot\voyage\base\AuditorInterface;
use markhuot\voyage\base\Frame;
use PDO;
use stdClass;
use Throwable;

class Sqlite implements AuditorInterface
{
    protected ?PDO $db = null;

    public function __construct(
        protected ?string $path = null,
    ){
        $this->path ??= __DIR__ . '/../../audit.sqlite';
    }

    /**
     * @param array<string, mixed> $condition
     * @return array<array<string, mixed>>
     */
    public function fetchFrameData(array $condition): array
    {
        $whereStatement = implode(' AND ', array_map(function ($key) {
            return "{$key} = ?";
        }, array_keys($condition)));

        $statement = $this->db()->prepare('SELECT * FROM frames WHERE ' . $whereStatement);
        $statement->execute(array_values($condition));

        // Ensure the return type matches the expected type
        return (array)$statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function fetchFrameCount(array $condition): int
    {
        $wheres = [];
        $params = [];

        foreach ($condition as $key => $value) {
            if ($value === null) {
                $wheres[] = "{$key} is null";
            }
            elseif ($value === ':notnull:') {
                $wheres[] = "{$key} is not null";
            }
            else {
                $wheres[] = "{$key} = ?";
                $params[] = $value;
            }
        }

        $whereStatement = implode(' AND ', $wheres);

        $statement = $this->db()->prepare('SELECT count(*) as count FROM frames WHERE ' . $whereStatement);
        $statement->execute(array_values($params));

        // Ensure the return type matches the expected type
        return $statement->fetchObject()->count;
    }

    /**
     * @param Frame<mixed> $frame
     * @return bool
     */
    public function hydrateFrame(Frame $frame): bool
    {
        $condition = [
            'collection' => $frame->collection,
            'matrix' => $frame->matrix,
            'sourceKey' => $frame->sourceKey,
        ];

        $results = $this->fetchFrameData($condition);

        if (empty($results)) {
            return false;
        }

        if (count($results) > 1) {
            throw new \RuntimeException('Multiple frames found for the same condition ' . json_encode($condition));
        }

        // Ensure proper type access
        $frame->matrix = $results[0]['matrix'] ?? null;
        $frame->checksum = $results[0]['checksum'] ?? null;
        $frame->destinationKey = $results[0]['destinationKey'] ?? null;
        $frame->lastError = isset($results[0]['lastError']) && $results[0]['lastError'] ? new DateTime($results[0]['lastError']) : null;
        $frame->lastImport = isset($results[0]['lastImport']) && $results[0]['lastImport'] ? new DateTime($results[0]['lastImport']) : null;

        return true;
    }

    /**
     * @param Frame<mixed> $frame
     * @return void
     */
    public function persistFrame(Frame $frame): void
    {
        $statement = $this->db()->prepare('REPLACE INTO frames (collection, matrix, sourceKey, destinationKey, checksum, lastError, lastImport) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $statement->execute([
            $frame->collection,
            $frame->matrix,
            $frame->sourceKey,
            $frame->destinationKey,
            $frame->checksum,
            $frame->lastError ? $frame->lastError->format('Y-m-d H:i:s') : null,
            $frame->lastImport ? $frame->lastImport->format('Y-m-d H:i:s') : null,
        ]);
    }

    protected function db(): PDO
    {
        if ($this->db) {
            return $this->db;
        }

        if ($this->path !== '' && $this->path !== ':memory:') {
            $directory = dirname($this->path);
            if (!file_exists($directory)) {
                mkdir($directory, 0777, true);
            }
        }

        $conn = new PDO('sqlite:' . $this->path);
        $conn->prepare('CREATE TABLE IF NOT EXISTS frames (
            `collection` varchar(512) NOT NULL DEFAULT \'default\',
            `matrix` varchar(1024),
            `sourceKey` varchar(512) NOT NULL,
            `destinationKey` varchar(512),
            `checksum` varchar(512),
            `lastError` DATETIME,
            `lastImport` DATETIME,
            primary key (`collection`,`matrix`,`sourceKey`)
        )')->execute();

        return $this->db = $conn;
    }
}
