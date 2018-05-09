<?php
/*
 * This file is part of the Recommend Product plugin
 *
 * Copyright (C) 2016 LOCKON CO.,LTD. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\Recommend\Controller\Block;

use Eccube\Controller\AbstractController;
use Plugin\Recommend\Repository\RecommendProductRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class RecommendController.
 */
class RecommendController extends AbstractController
{
    /**
     * @var RecommendProductRepository
     */
    private $recommendProductRepository;

    /**
     * RecommendController constructor.
     * @param RecommendProductRepository $recommendProductRepository
     */
    public function __construct(RecommendProductRepository $recommendProductRepository)
    {
        $this->recommendProductRepository = $recommendProductRepository;
    }

    /**
     * @Route("/block/recommend_product_block", name="block_recommend_product_block")
     * @Template("Block/recommend_product_block.twig")
     */
    public function index(Request $request)
    {
        $arrRecommendProduct = $this->recommendProductRepository->getRecommendProduct();

        return array(
            'recommend_products' => $arrRecommendProduct,
        );
    }
}
