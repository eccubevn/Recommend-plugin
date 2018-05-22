<?php
/*
 * This file is part of the Recommend plugin
 *
 * Copyright (C) 2016 LOCKON CO.,LTD. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\Recommend\Tests\Web;

use Eccube\Common\Constant;
use Eccube\Repository\ProductRepository;
use Eccube\Tests\Web\AbstractWebTestCase;
use Plugin\Recommend\Entity\RecommendProduct;

/**
 * Class RecommendControllerTest.
 */
class BlockRecommendControllerTest extends AbstractWebTestCase
{
    /**
     * @var RecommendProduct
     */
    private $RecommendProduct1;
    /**
     * @var RecommendProduct
     */
    private $RecommendProduct2;

    /**
     * setUp.
     */
    public function setUp()
    {
        parent::setUp();
        // recommend for product 1 with rank 1
        $this->RecommendProduct1 = $this->initRecommendData(1, 1);
        // recommend for product 2 with rank 2
        $this->RecommendProduct2 = $this->initRecommendData(2, 2);
    }

    /**
     * Block.RecommendController.
     */
    public function testRecommendBlock()
    {
        $crawler = $this->client->request(
            'GET',
            $this->generateUrl('block_recommend_product_block')
        );

        $this->assertContains($this->RecommendProduct1->getProduct()->getName(), $crawler->html());
        $this->assertContains($this->RecommendProduct2->getProduct()->getName(), $crawler->html());
    }

    /**
     * @param $productId
     * @param $rank
     *
     * @return \Plugin\Recommend\Entity\RecommendProduct
     */
    private function initRecommendData($productId, $rank)
    {
        $dateTime = new \DateTime();
        $fake = $this->getFaker();

        $Recommend = new \Plugin\Recommend\Entity\RecommendProduct();
        $Recommend->setComment($fake->word);
        $Recommend->setProduct($this->container->get(ProductRepository::class)->find($productId));
        $Recommend->setSortno($rank);
        $Recommend->setVisible(Constant::ENABLED);
        $Recommend->setCreateDate($dateTime);
        $Recommend->setUpdateDate($dateTime);
        $this->entityManager->persist($Recommend);
        $this->entityManager->flush();

        return $Recommend;
    }
}
