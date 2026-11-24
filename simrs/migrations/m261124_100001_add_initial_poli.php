<?php

use yii\db\Migration;

class m261124_100001_add_initial_poli extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('{{%poli}}', 'initial', $this->string(3)->notNull()->defaultValue(''));
        $this->execute("CREATE UNIQUE INDEX uq_poli_initial ON {{%poli}} (initial) WHERE (initial <> '')");
    }

    public function safeDown(): void
    {
        $this->dropIndex('uq_poli_initial', '{{%poli}}');
        $this->dropColumn('{{%poli}}', 'initial');
    }
}
