<?php
/*
 * This file is part of the Recommend plugin
 *
 * Copyright (C) 2016 LOCKON CO.,LTD. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Plugin\Recommend\Tests\Repository;

use Eccube\Common\Constant;
use Eccube\Tests\Web\Admin\AbstractAdminWebTestCase;
use Plugin\Recommend\Entity\RecommendProduct;
use Eccube\Entity\Master\Disp;

/**
 * Class RecommendRepositoryTest
 * @package Plugin\Recommend\Tests\Repository
 */
class RecommendRepositoryTest extends AbstractAdminWebTestCase
{
    /**
     * Delete all Recommend for testing
     */
    public function setUp()
    {
        parent::setUp();
        $this->deleteAllRows(array('plg_recommend_product'));

        // recommend for product 1 with rank 1
        $this->_initRecommendData(1, 1);
        // recommend for product 2 with rank 2
        $this->_initRecommendData(2, 2);
    }

    /**
     * @param $productId
     * @param $rank
     * @return RecommendProduct
     */
    private function _initRecommendData($productId, $rank)
    {
        $dateTime = new \DateTime();
        $fake = $this->getFaker();

        $Recommend = new RecommendProduct();
        $Recommend->setComment($fake->word);
        $Recommend->setProduct($this->app['eccube.repository.product']->find($productId));
        $Recommend->setRank($rank);
        $Recommend->setDelFlg(Constant::DISABLED);
        $Recommend->setCreateDate($dateTime);
        $Recommend->setUpdateDate($dateTime);
        $this->app['orm.em']->persist($Recommend);
        $this->app['orm.em']->flush();
        return $Recommend;
    }

    /**
     * function : findByRankUp
     */
    public function testFindByRankUp()
    {
        $ProductsOver = $this->app['eccube.plugin.recommend.repository.recommend_product']->findByRankUp(1);

        $this->expected = 2;
        $this->actual = $ProductsOver->getRank();
        $this->verify();
    }

    /**
     * function : findByRankDown
     */
    public function testFindByRankDown()
    {
        $ProductsOver = $this->app['eccube.plugin.recommend.repository.recommend_product']->findByRankDown(2);

        $this->expected = 1;
        $this->actual = $ProductsOver->getRank();
        $this->verify();
    }

    /**
     * function : getMaxRank
     */
    public function testGetMaxRank()
    {
        $ProductsOver = $this->app['eccube.plugin.recommend.repository.recommend_product']->getMaxRank();

        $this->expected = 2;
        $this->actual = $ProductsOver;
        $this->verify();
    }

    /**
     * function : getRecommendProduct
     */
    public function testGetRecommendProduct()
    {
        $Disp = $this->app['eccube.repository.master.disp']->find(Disp::DISPLAY_SHOW);
        $RecommendProducts = $this->app['eccube.plugin.recommend.repository.recommend_product']->getRecommendProduct($Disp);

        $this->expected = 2;
        $this->actual = count($RecommendProducts);
        $this->verify();
    }
}
