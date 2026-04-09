<?php

namespace App\Repositories;

use App\Models\Category;
use PDO;

class CategoryRepository {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function findAllByWorkspaceIdAndLevel(int $workspaceId, int $level): array {
        $stmt = $this->db->prepare("SELECT CategoryId, WorkspaceId, CategoryName, Level FROM category WHERE WorkspaceId = :userId AND Level = :lvl ORDER BY CategoryName ASC");
        $stmt->execute(['userId' => $workspaceId, 'lvl' => $level]);
        
        $categories = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $categories[] = new Category($row['WorkspaceId'], $row['CategoryName'], $row['Level'], $row['CategoryId']);
        }
        return $categories;
    }

    public function findByIdAndWorkspaceId(int $id, int $workspaceId): ?Category {
        $stmt = $this->db->prepare("SELECT * FROM category WHERE CategoryId = :id AND WorkspaceId = :userId LIMIT 1");
        $stmt->execute(['id' => $id, 'userId' => $workspaceId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return null;
        return new Category($row['WorkspaceId'], $row['CategoryName'], $row['Level'], $row['CategoryId']);
    }

    public function save(Category $cat): Category {
        if ($cat->getId()) {
            $stmt = $this->db->prepare("UPDATE category SET CategoryName = :name WHERE CategoryId = :id AND WorkspaceId = :userId");
            $stmt->execute([
                'name' => $cat->getCategoryName(),
                'id' => $cat->getId(),
                'userId' => $cat->getUserId()
            ]);
            return $cat;
        }

        $stmt = $this->db->prepare("INSERT INTO category (WorkspaceId, CategoryName, Level) VALUES (:userId, :name, :lvl)");
        $stmt->execute([
            'userId' => $cat->getUserId(),
            'name' => $cat->getCategoryName(),
            'lvl' => $cat->getLevel()
        ]);
        
        return new Category($cat->getUserId(), $cat->getCategoryName(), $cat->getLevel(), (int) $this->db->lastInsertId());
    }

    public function delete(int $id, int $workspaceId): bool {
        $stmt = $this->db->prepare("DELETE FROM category WHERE CategoryId = :id AND WorkspaceId = :userId");
        $stmt->execute(['id' => $id, 'userId' => $workspaceId]);
        return $stmt->rowCount() > 0;
    }
}
