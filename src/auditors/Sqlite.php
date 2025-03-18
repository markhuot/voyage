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

    public function hydrateFrame(Frame $frame): void
    {
        $statement = $this->db()->prepare('SELECT * FROM frames WHERE collection=? AND sourceKey=?');
        $statement->execute([$frame->collection, $frame->sourceKey]);

        /** @var stdClass|false $result */
        $result = $statement->fetch(PDO::FETCH_OBJ);

        if ($result) {
            $frame->checksum = $result->checksum ?? null;
            $frame->destinationKey = $result->destinationKey ?? null;
            $frame->lastError = $result->lastError ? new DateTime($result->lastError) : null;
            $frame->lastImport = $result->lastImport ? new DateTime($result->lastImport) : null;
        }
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
