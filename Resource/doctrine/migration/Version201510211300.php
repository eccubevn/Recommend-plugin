<?php
/*
 * This file is part of the Recommend Product plugin
 *
 * Copyright (C) 2016 LOCKON CO.,LTD. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\Tools\SchemaTool;
use Eccube\Application;

/**
 * Class Version201510211300
 * @package DoctrineMigrations
 */
class Version201510211300 extends AbstractMigration
{
    /**
     * @var string table name
     */
    const NAME = 'plg_recommend_product';

    /**
     * Setup data
     *
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->createRecommendProduct($schema);
    }

    /**
     * Remove data
     *
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $schema->dropTable(self::NAME);
        $schema->dropSequence('plg_recommend_product_recommend_product_id_seq');
    }

    /**
     * Create recommend table
     *
     * @param Schema $schema
     * @return bool
     */
    protected function createRecommendProduct(Schema $schema)
    {
        if ($schema->hasTable(self::NAME)) {
            return true;
        }

        $app = Application::getInstance();
        $em = $app['orm.em'];
        $classes = array(
            $em->getClassMetadata('Plugin\Recommend\Entity\RecommendProduct'),
        );
        $tool = new SchemaTool($em);
        $tool->createSchema($classes);

        return true;
    }
}
