<?php
namespace Plugin\Recommend\Tests\Web;

use Eccube\Common\Constant;
use Eccube\Tests\Web\Admin\AbstractAdminWebTestCase;
use Symfony\Component\DomCrawler\Crawler;


class RecommendAdminControllerTest extends AbstractAdminWebTestCase
{
    /**
     * please ensure have 1 or more order in database before testing
     */
    public function setUp()
    {
        parent::setUp();
        $this->deleteAllRows(array('plg_recommend_product'));
        // recommend for product 1 with rank 1
        $this->Recommend1 = $this->_initRecommendData(1, 1);
        // recommend for product 2 with rank 2
        $this->Recommend2 = $this->_initRecommendData(2, 2);
    }

    /**
     * @param $productId
     * @param $rank
     * @return \Plugin\Recommend\Entity\RecommendProduct
     */
    private function _initRecommendData($productId, $rank)
    {
        $dateTime = new \DateTime();
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
     * testRecommendList
     */
    public function testRecommendList()
    {
        $crawler = $this->client->request('GET', $this->app->url('admin_recommend_list'));
        $this->assertContains('おすすめ商品内容設定', $crawler->html());
    }

    /**
     * RecommendSearchModelController
     */
    public function testAjaxSearchProduct()
    {
        $crawler = $this->client->request(
            'POST',
            $this->app->url('admin_recommend_search_product'),
            array('admin_search_product' => array(
                                            'id' => '',
                                            'category_id' => '',
                                            '_token' => 'dummy'
            )
            ),
            array(),
            array('HTTP_X-Requested-With' => 'XMLHttpRequest')
        );

        $productList = $crawler->html();
        $this->assertContains('パーコレーター', $productList);
    }

    /**
     * RecommendSearchModelController
     */
    public function testAjaxSearchProductValue()
    {
        $crawler = $this->client->request(
            'POST',
            $this->app->url('admin_recommend_search_product'),
            array('admin_search_product' => array(
                'id' => '',
                'category_id' => 1,
                '_token' => 'dummy'
            )
            ),
            array(),
            array('HTTP_X-Requested-With' => 'XMLHttpRequest')
        );

        $productList = $crawler->html();
        $this->assertContains('パーコレーター', $productList);
    }

    /**
     * testRecommendCreate
     */
    public function testRecommendCreate()
    {
        $crawler = $this->client->request('GET', $this->app->url('admin_recommend_new'));
        $this->assertContains('おすすめ商品管理', $crawler->html());
    }

    /**
     * testRecommendNew
     */
    public function testRecommendNew()
    {
        $this->deleteAllRows(array('plg_recommend_product'));

        $productId = 1;
        $editMessage = 'Just Unittest';
        $this->client->request(
            'POST',
            $this->app->url('admin_recommend_new'),
            array('admin_recommend' => array('_token' => 'dummy',
                'comment' => $editMessage,
                'Product' => $productId
            )
            )
        );

        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('admin_recommend_list')));
        $ProductNew = $this->_getRecommend($productId);

        $this->expected = $editMessage;
        $this->actual = $ProductNew->getComment();
        $this->verify();
    }

    /**
     * testRecommendEdit
     */
    public function testRecommendEdit()
    {
        $productId = 2;
        $recommendId = $this->Recommend2->getId();
        $editMessage = 'Just Unittest Edit';

        $this->client->request(
            'POST',
            $this->app->url('admin_recommend_edit', array('id' => $recommendId)),
            array(
                'admin_recommend' => array('_token' => 'dummy',
                    'comment' => $editMessage,
                    'id' => $recommendId,
                    'Product' => $productId
                )
            )
        );
        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('admin_recommend_list')));
        $ProductNew = $this->_getRecommend($productId);

        $this->expected = $editMessage;
        $this->actual = $ProductNew->getComment();
        $this->verify();

    }

    /**
     * testRecommendDelete
     */
    public function testRecommendDelete()
    {
        $productId = $this->Recommend1->getId();
        $this->client->request(
            'POST',
            $this->app->url('admin_recommend_delete', array('id' => $productId))
        );
        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('admin_recommend_list')));
        $ProductNew = $this->app['eccube.plugin.recommend.repository.recommend_product']->find($productId);

        $this->expected = 1;
        $this->actual = $ProductNew->getDelFlg();
        $this->verify();
    }

    /**
     * testRecommendRankUp
     */
    public function testRecommendRankUp()
    {
        $rankExpected = $this->Recommend1->getRank();
        $productId = $this->Recommend1->getId();
        $this->client->request(
            'PUT',
            $this->app->url('admin_recommend_rank_up', array('id' => $productId)),
            array('id' => $productId, '_token' => 'dummy')
        );
        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('admin_recommend_list')));
        $ProductNew = $this->app['eccube.plugin.recommend.repository.recommend_product']->find($productId);

        $this->expected = $rankExpected + 1;
        $this->actual = $ProductNew->getRank();
        $this->verify();
    }

    /**
     * testRecommendDown
     */
    public function testRecommendDown()
    {
        $rankExpected = $this->Recommend2->getRank();
        $productId = $this->Recommend2->getId();
        $this->client->request(
            'PUT',
            $this->app->url('admin_recommend_rank_down', array('id' => $productId)),
            array('id' => $productId, '_token' => 'dummy')
        );
        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('admin_recommend_list')));
        $ProductNew = $this->app['eccube.plugin.recommend.repository.recommend_product']->find($productId);

        $this->expected = $rankExpected - 1;
        $this->actual = $ProductNew->getRank();
        $this->verify();
    }

    /**
     * @param $productId
     * @return mixed
     */
    private function _getRecommend($productId)
    {
        $Product = $this->app['eccube.repository.product']->find($productId);

        return $this->app['eccube.plugin.recommend.repository.recommend_product']->findOneBy(array('Product' => $Product));
    }
}