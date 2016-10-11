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

    public function testRecommendList()
    {
        $html = $this->client->request('GET', $this->app->url('admin_recommend_list')
        );
        $this->assertContains('おすすめ商品内容設定', $html->html());
    }

    public function testRecommendNew()
    {
        $productId = 2;
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
        $ProductNew = $this->getRecommend($productId);
        $this->expected = $editMessage;
        $this->actual = $ProductNew->getComment();
        $this->verify();
    }

    public function testRecommendEdit()
    {
        $Recommend1 = $this->initRecommendData(1, 1);
        $Recommend2 = $this->initRecommendData(2, 2);
        $productId = 2;
        $recommendId = $Recommend2->getId();
        $editMessage = 'Just Unittest Edit';

        $this->client->request('POST',
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
        $ProductNew = $this->getRecommend($productId);
        $this->expected = $editMessage;
        $this->actual = $ProductNew->getComment();
        $this->verify();

    }

    public function testRecommendDelete()
    {
        $Recommend1 = $this->initRecommendData(1, 1);
        $productId = $Recommend1->getId();
        $this->client->request('POST',
            $this->app->url('admin_recommend_delete', array('id' => $productId))
        );
        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('admin_recommend_list')));
        $ProductNew = $this->app['eccube.plugin.recommend.repository.recommend_product']->find($productId);
        $this->expected = 1;
        $this->actual = $ProductNew->getDelFlg();
        $this->verify();
    }

    public function testRecommendRankUp()
    {
        $Recommend1 = $this->initRecommendData(1, 1);
        $rankExpected = $Recommend1->getRank();
        $Recommend2 = $this->initRecommendData(2, 2);
        $productId = $Recommend1->getId();
        $this->client->request('PUT',
            $this->app->url('admin_recommend_rank_up', array('id' => $productId)),
            array('id' => $productId, '_token' => 'dummy')
        );
        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('admin_recommend_list')));
        $ProductNew = $this->app['eccube.plugin.recommend.repository.recommend_product']->find($productId);
        $this->expected = $rankExpected + 1;
        $this->actual = $ProductNew->getRank();
        $this->verify();
    }

    public function testRecommendDown()
    {
        $Recommend1 = $this->initRecommendData(1, 1);
        $Recommend2 = $this->initRecommendData(2, 2);
        $rankExpected = $Recommend2->getRank();
        $productId = $Recommend2->getId();
        $this->client->request('PUT',
            $this->app->url('admin_recommend_rank_down', array('id' => $productId)),
            array('id' => $productId, '_token' => 'dummy')
        );
        $this->assertTrue($this->client->getResponse()->isRedirect($this->app->url('admin_recommend_list')));
        $ProductNew = $this->app['eccube.plugin.recommend.repository.recommend_product']->find($productId);
        $this->expected = $rankExpected - 1;
        $this->actual = $ProductNew->getRank();
        $this->verify();
    }

    public function testcaseRecommendSearchProduct()
    {
        $this->initRecommendData(1, 1);
        $this->initRecommendData(2, 2);
        $crawler = $this->client->request('POST',
            $this->app->url('admin_recommend_search_product'),
            array('admin_search_product' => array(
                'id' => '',
                'category_id' => '4',
                '_token' => 'dummy',
            )
            ), array(),
            array('HTTP_X-Requested-With' => 'XMLHttpRequest')
        );
        $message = '<div class="table-responsive">';
        $this->assertContains($message, $crawler->html());
    }

    private function getRecommend($productId)
    {
        $Product = $this->app['eccube.repository.product']->find($productId);
        return $this->app['eccube.plugin.recommend.repository.recommend_product']->findOneBy(array('Product' => $Product));
    }
}