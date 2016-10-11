<?php

namespace Plugin\Recommend\Tests\Repository;

use Eccube\Common\Constant;
use Eccube\Tests\Web\Admin\AbstractAdminWebTestCase;
use Plugin\Recommend\Entity\RecommendProduct;
use Eccube\Entity\Master\Disp;

class RecommendRepositoryTest extends AbstractAdminWebTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->initDeleteData();
    }

    private function initDeleteData()
    {
        $Recommends = $this->app['eccube.plugin.recommend.repository.recommend_product']->findAll();
        foreach ($Recommends as $Recommend) {
            $this->app['orm.em']->remove($Recommend);
        }
        $this->app['orm.em']->flush();
    }

    private function initRecommendData($productId, $rank)
    {
        $dateTime = new \DateTime();
        $rank = $rank;
        $fake = $this->getFaker();

        $Recommend = new \Plugin\Recommend\Entity\RecommendProduct();
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
     * assume : :have 2 elements
     * get 4 elements
     */
    public function testFindList()
    {
        $this->initRecommendData(1, 1);
        $this->initRecommendData(2, 2);
        $pagination = $this->app['eccube.plugin.recommend.repository.recommend_product']->findList();
        $this->expected = 2;
        $this->actual = count($pagination);
        $this->verify();
    }

    /**
     * assume : :have 2 elements
     * get 1 element from 2
     */
    public function testFindByRankUp()
    {
        $this->initRecommendData(1, 1);
        $this->initRecommendData(2, 2);
        $ProductsOver = $this->app['eccube.plugin.recommend.repository.recommend_product']->findByRankUp(1);
        $this->expected = 2;
        $this->actual = $ProductsOver->getRank();
        $this->verify();
    }

    /**
     * assume : :have 2 elements
     * get 1 element from 1
     */
    public function testFindByRankDown()
    {
        $this->initRecommendData(1, 1);
        $this->initRecommendData(2, 2);
        $ProductsOver = $this->app['eccube.plugin.recommend.repository.recommend_product']->findByRankDown(2);
        $this->expected = 1;
        $this->actual = $ProductsOver->getRank();
        $this->verify();
    }

    /**
     * assume : :have 42 elements
     * get element with rank 2
     */
    public function testGetMaxRank()
    {
        $this->initRecommendData(1, 1);
        $this->initRecommendData(2, 2);
        $productsOver = $this->app['eccube.plugin.recommend.repository.recommend_product']->getMaxRank();
        $this->expected = 2;
        $this->actual = $productsOver;
        $this->verify();
    }

    public function testGetRecommendProduct()
    {
        $this->initRecommendData(1, 1);
        $this->initRecommendData(2, 2);
        $Disp = $this->app['eccube.repository.master.disp']->find(Disp::DISPLAY_SHOW);
        $RecommendProducts = $this->app['eccube.plugin.recommend.repository.recommend_product']->getRecommendProduct($Disp);
        $this->expected = 2;
        $this->actual = count($RecommendProducts);
        $this->verify();
    }

}