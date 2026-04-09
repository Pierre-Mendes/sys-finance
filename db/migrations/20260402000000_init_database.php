<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class InitDatabase extends AbstractMigration
{
    public function change(): void
    {
        // Table: user
        $users = $this->table('user', ['id' => 'UserId']);
        $users->addColumn('FirstName', 'string', ['limit' => 255])
              ->addColumn('LastName', 'string', ['limit' => 255])
              ->addColumn('Email', 'string', ['limit' => 255])
              ->addColumn('Password', 'string', ['limit' => 255])
              ->addColumn('Currency', 'string', ['limit' => 255])
              ->create();

        // Table: account
        $accounts = $this->table('account', ['id' => 'AccountId']);
        $accounts->addColumn('UserId', 'integer', ['signed' => false])
                 ->addColumn('AccountName', 'string', ['limit' => 255])
                 ->addForeignKey('UserId', 'user', 'UserId', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
                 ->create();

        // Table: category
        $categories = $this->table('category', ['id' => 'CategoryId']);
        $categories->addColumn('UserId', 'integer', ['signed' => false])
                   ->addColumn('CategoryName', 'string', ['limit' => 255])
                   ->addColumn('Level', 'integer', ['limit' => 2])
                   ->addForeignKey('UserId', 'user', 'UserId', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
                   ->create();

        // Table: totals
        $totals = $this->table('totals', ['id' => 'TotalsId']);
        $totals->addColumn('UserId', 'integer', ['signed' => false])
               ->addColumn('AccountId', 'integer', ['signed' => false])
               ->addColumn('Totals', 'integer')
               ->addForeignKey('UserId', 'user', 'UserId', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
               ->addForeignKey('AccountId', 'account', 'AccountId', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
               ->create();

        // Table: assets
        $assets = $this->table('assets', ['id' => 'AssetsId']);
        $assets->addColumn('UserId', 'integer', ['signed' => false])
               ->addColumn('Title', 'string', ['limit' => 255])
               ->addColumn('Date', 'date')
               ->addColumn('CategoryId', 'integer', ['signed' => false])
               ->addColumn('AccountId', 'integer', ['signed' => false])
               ->addColumn('Amount', 'string', ['limit' => 255])
               ->addColumn('Description', 'text')
               ->addForeignKey('UserId', 'user', 'UserId', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
               ->addForeignKey('AccountId', 'account', 'AccountId', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
               ->addForeignKey('CategoryId', 'category', 'CategoryId', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
               ->create();

        // Table: bills
        $bills = $this->table('bills', ['id' => 'BillsId']);
        $bills->addColumn('UserId', 'integer', ['signed' => false])
              ->addColumn('Title', 'string', ['limit' => 255])
              ->addColumn('Dates', 'date')
              ->addColumn('CategoryId', 'integer', ['signed' => false])
              ->addColumn('AccountId', 'integer', ['signed' => false])
              ->addColumn('Amount', 'string', ['limit' => 255])
              ->addColumn('Description', 'text')
              ->addForeignKey('UserId', 'user', 'UserId', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
              ->addForeignKey('AccountId', 'account', 'AccountId', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
              ->addForeignKey('CategoryId', 'category', 'CategoryId', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
              ->create();

        // Table: budget
        $budgets = $this->table('budget', ['id' => 'BudgetId']);
        $budgets->addColumn('UserId', 'integer', ['signed' => false])
                ->addColumn('CategoryId', 'integer', ['signed' => false])
                ->addColumn('Dates', 'date')
                ->addColumn('Amount', 'integer')
                ->addForeignKey('UserId', 'user', 'UserId', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
                ->addForeignKey('CategoryId', 'category', 'CategoryId', ['delete'=> 'CASCADE', 'update'=> 'NO_ACTION'])
                ->create();
    }
}
