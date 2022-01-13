<?php

namespace qpi\task;

use pocketmine\scheduler\Task;

class DatabaseTask extends Task {

    public function __construct(private \mysqli $db) {

    }

    public function onRun(): void {
        $this->db->query("SELECT * FROM assoc LIMIT 0;");
    }

}