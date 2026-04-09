<?php

namespace App\Repositories;

use App\Contracts\ICreditCardRepository;
use App\Models\CreditCard;
use PDO;

class CreditCardRepository implements ICreditCardRepository {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function findAllByWorkspaceId(int $workspaceId): array {
        $stmt = $this->db->prepare("SELECT * FROM credit_cards WHERE WorkspaceId = :userId");
        $stmt->execute(['userId' => $workspaceId]);
        
        $cards = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $cards[] = new CreditCard(
                (int)$row['WorkspaceId'], (int)$row['AccountId'], $row['Name'], (float)$row['LimitAmount'], 
                (int)$row['ClosingDay'], (int)$row['DueDay'], $row['Brand'], $row['Color'] ?? '#4f46e5', (int)$row['CardId']
            );
        }
        return $cards;
    }

    public function findByIdAndWorkspaceId(int $id, int $workspaceId): ?CreditCard {
        $stmt = $this->db->prepare("SELECT * FROM credit_cards WHERE CardId = :id AND WorkspaceId = :userId LIMIT 1");
        $stmt->execute(['id' => $id, 'userId' => $workspaceId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return null;
        return new CreditCard(
            (int)$row['WorkspaceId'], (int)$row['AccountId'], $row['Name'], (float)$row['LimitAmount'], 
            (int)$row['ClosingDay'], (int)$row['DueDay'], $row['Brand'], $row['Color'] ?? '#4f46e5', (int)$row['CardId']
        );
    }

    public function save(CreditCard $c): CreditCard {
        if ($c->getId()) {
            $stmt = $this->db->prepare("
                UPDATE credit_cards 
                SET Name = :name, AccountId = :accId, Brand = :brand, LimitAmount = :limit, ClosingDay = :closing, DueDay = :due, Color = :color
                WHERE CardId = :id AND WorkspaceId = :userId
            ");
            $stmt->execute([
                'name' => $c->getName(), 'accId' => $c->getAccountId(), 'brand' => $c->getBrand(),
                'limit' => $c->getLimitAmount(), 'closing' => $c->getClosingDay(), 'due' => $c->getDueDay(),
                'color' => $c->getColor(), 'id' => $c->getId(), 'userId' => $c->getWorkspaceId()
            ]);
            return $c;
        }

        $stmt = $this->db->prepare("
            INSERT INTO credit_cards (WorkspaceId, AccountId, Name, Brand, LimitAmount, ClosingDay, DueDay, Color)
            VALUES (:userId, :accId, :name, :brand, :limit, :closing, :due, :color)
        ");
        $stmt->execute([
            'userId' => $c->getWorkspaceId(), 'accId' => $c->getAccountId(), 'name' => $c->getName(),
            'brand' => $c->getBrand(), 'limit' => $c->getLimitAmount(), 'closing' => $c->getClosingDay(), 
            'due' => $c->getDueDay(), 'color' => $c->getColor()
        ]);
        
        return new CreditCard(
            $c->getWorkspaceId(), $c->getAccountId(), $c->getName(), $c->getLimitAmount(), 
            $c->getClosingDay(), $c->getDueDay(), $c->getBrand(), $c->getColor(), (int)$this->db->lastInsertId()
        );
    }

    public function delete(int $id, int $workspaceId): bool {
        $stmt = $this->db->prepare("DELETE FROM credit_cards WHERE CardId = :id AND WorkspaceId = :userId");
        $stmt->execute(['id' => $id, 'userId' => $workspaceId]);
        return $stmt->rowCount() > 0;
    }
}
