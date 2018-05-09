<?php
/*
 * This file is part of the Recommend Product plugin
 *
 * Copyright (C) 2016 LOCKON CO.,LTD. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\Recommend\Controller;

use Doctrine\Common\Collections\ArrayCollection;
use Eccube\Controller\AbstractController;
use Eccube\Repository\CategoryRepository;
use Eccube\Repository\ProductRepository;
use Knp\Component\Pager\Paginator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class RecommendSearchModelController.
 */
class RecommendSearchModelController extends AbstractController
{
    /**
     * @var CategoryRepository
     */
    private $categoryRepository;

    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * RecommendSearchModelController constructor.
     * @param CategoryRepository $categoryRepository
     * @param ProductRepository $productRepository
     */
    public function __construct(CategoryRepository $categoryRepository, ProductRepository $productRepository)
    {
        $this->categoryRepository = $categoryRepository;
        $this->productRepository = $productRepository;
    }

    /**
     * 商品検索画面を表示する.
     *
     * @param Request     $request
     * @param int         $page_no
     *
     * @return \Symfony\Component\HttpFoundation\Response|null
     * @Route("/%eccube_admin_route%/plugin/recommend/search/product", name="plugin_recommend_search_product")
     * @Route("/%eccube_admin_route%/plugin/recommend/search/product/page/{page_no}", requirements={"page_no" = "\d+"}, name="plugin_recommend_search_product_page")
     */
    public function searchProduct(Request $request, $page_no = null, Paginator $paginator)
    {
        if (!$request->isXmlHttpRequest()) {
            return null;
        }

        log_debug('Search product start.');

        $pageCount = $this->eccubeConfig['eccube_default_page_count'];
        $session = $this->session;
        if ('POST' === $request->getMethod()) {
            $page_no = 1;
            $searchData = array(
                'name' => trim($request->get('id')),
            );

            if ($categoryId = $request->get('category_id')) {
                    $searchData['category_id'] = $categoryId;
            }

            $session->set('eccube.plugin.recommend.product.search', $searchData);
            $session->set('eccube.plugin.recommend.product.search.page_no', $page_no);
        } else {
            $searchData = (array) $session->get('eccube.plugin.recommend.product.search');
            if (is_null($page_no)) {
                $page_no = intval($session->get('eccube.plugin.recommend.product.search.page_no'));
            } else {
                $session->set('eccube.plugin.recommend.product.search.page_no', $page_no);
            }
        }

        //set parameter
        $searchData['id'] = $searchData['name'];

        if (!empty($searchData['category_id'])) {
            $searchData['category_id'] = $this->categoryRepository->find($searchData['category_id']);
        }

        $qb = $this->productRepository->getQueryBuilderBySearchDataForAdmin($searchData);

        /** @var \Knp\Component\Pager\Pagination\SlidingPagination $pagination */
        $pagination = $paginator->paginate(
            $qb,
            $page_no,
            $pageCount,
            array('wrap-queries' => true)
        );

        /** @var ArrayCollection */
        $arrProduct = $pagination->getItems();

        log_debug('Search product finish.');
        if (count($arrProduct) == 0) {
            log_debug('Search product not found.');
        }

        return $this->render('Recommend/Resource/template/admin/search_product.twig', array(
            'pagination' => $pagination,
        ));
    }
}
