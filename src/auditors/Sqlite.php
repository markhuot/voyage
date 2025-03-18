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

    public function fetchFrameData(array $condition): array
    {
        $whereStatement = implode(' AND ', array_map(function ($key) {
            return "{$key} = ?";
        }, array_keys($condition)));

        $statement = $this->db()->prepare('SELECT * FROM frames WHERE '.$whereStatement);
        $statement->execute(array_values($condition));

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

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
            throw new \RuntimeException('Multiple frames found for the same condition '.json_encode($condition));
        }

        $frame->matrix = $results[0]['matrix'] ?? null;
        $frame->checksum = $results[0]['checksum'] ?? null;
        $frame->destinationKey = $results[0]['destinationKey'] ?? null;
        $frame->lastError = $results[0]['lastError'] ? new DateTime($results[0]['lastError']) : null;
        $frame->lastImport = $results[0]['lastImport'] ? new DateTime($results[0]['lastImport']) : null;

        return true;
    }

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
