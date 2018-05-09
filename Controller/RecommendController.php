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

use Eccube\Controller\AbstractController;
use Eccube\Form\Type\Admin\SearchProductType;
use Plugin\Recommend\Entity\RecommendProduct;
use Plugin\Recommend\Form\Type\RecommendProductType;
use Plugin\Recommend\Repository\RecommendProductRepository;
use Plugin\Recommend\Service\RecommendService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
     * @var RecommendService
     */
    private $recommendService;

    /**
     * RecommendController constructor.
     * @param RecommendProductRepository $recommendProductRepository
     * @param RecommendService $recommendService
     */
    public function __construct(RecommendProductRepository $recommendProductRepository, RecommendService $recommendService)
    {
        $this->recommendProductRepository = $recommendProductRepository;
        $this->recommendService = $recommendService;
    }


    /**
     * おすすめ商品一覧.
     *
     * @param Request     $request
     *
     * @return Response
     * @Route("/%eccube_admin_route%/plugin/recommend/", name="plugin_recommend_list")
     */
    public function index(Request $request)
    {
        $pagination = $this->recommendProductRepository->getRecommendList();

        return $this->render('Recommend/Resource/template/admin/index.twig', array(
            'pagination' => $pagination,
            'total_item_count' => count($pagination),
        ));
    }

    /**
     * Create & Edit.
     *
     * @param Request     $request
     * @param int         $id
     * @throws \Exception
     * @return \Symfony\Component\HttpFoundation\Response
     * @Route("/%eccube_admin_route%/plugin/recommend/new", name="plugin_recommend_new")
     * @Route("/%eccube_admin_route%/plugin/recommend/{id}/edit", name="plugin_recommend_edit", requirements={"id" = "\d+"})
     */
    public function edit(Request $request, $id = null)
    {
        /* @var RecommendProduct $Recommend */
        $Recommend = null;
        $Product = null;
        if (!is_null($id)) {
            // IDからおすすめ商品情報を取得する
            $Recommend = $this->recommendProductRepository->find($id);

            if (!$Recommend) {
                $this->addError('admin.plugin.recommend.not_found', 'admin');
                log_info('The recommend product is not found.', array('Recommend id' => $id));

                return $this->redirectToRoute('plugin_recommend_list');
            }

            $Product = $Recommend->getProduct();
        }

        // formの作成
        /* @var Form $form */
        $form = $this->formFactory
            ->createBuilder(RecommendProductType::class, $Recommend)
            ->getForm();

        $form->handleRequest($request);
        $data = $form->getData();
        if ($form->isSubmitted() && $form->isValid()) {
            $service = $this->recommendService;
            if (is_null($data['id'])) {
                if ($status = $service->createRecommend($data)) {
                    $this->addSuccess('admin.plugin.recommend.register.success', 'admin');
                    log_info('Add the new recommend product success.', array('Product id' => $data['Product']->getId()));
                }
            } else {
                if ($status = $service->updateRecommend($data)) {
                    $this->addSuccess('admin.plugin.recommend.update.success', 'admin');
                    log_info('Update the recommend product success.', array('Recommend id' => $Recommend->getId(), 'Product id' => $data['Product']->getId()));
                }
            }

            if (!$status) {
                $this->addError('admin.plugin.recommend.not_found', 'admin');
                log_info('Failed the recommend product updating.', array('Product id' => $data['Product']->getId()));
            }

            return $this->redirectToRoute('plugin_recommend_list');
        }

        if (!empty($data['Product'])) {
            $Product = $data['Product'];
        }

        $arrProductIdByRecommend = $this->recommendProductRepository->getRecommendProductIdAll();

        return $this->registerView(
            array(
                'form' => $form->createView(),
                'recommend_products' => json_encode($arrProductIdByRecommend),
                'Product' => $Product,
            )
        );
    }

    /**
     * おすすめ商品の削除.
     *
     * @param Request     $request
     * @param RecommendProduct $RecommendProduct
     *
     * @throws \Exception
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @Route("/%eccube_admin_route%/plugin/recommend/{id}/delete", name="plugin_recommend_delete", requirements={"id" = "\d+"})
     * @Method("DELETE")
     * @ParamConverter("RecommendProduct")
     */
    public function delete(Request $request, RecommendProduct $RecommendProduct)
    {
        // Valid token
        $this->isTokenValid();
        // おすすめ商品情報を削除する
        if ($this->recommendProductRepository->deleteRecommend($RecommendProduct)) {
            log_info('The recommend product delete success!', array('Recommend id' => $RecommendProduct->getId()));
            $this->addSuccess('admin.plugin.recommend.delete.success', 'admin');
        } else {
            $this->addError('admin.plugin.recommend.not_found', 'admin');
            log_info('The recommend product is not found.', array('Recommend id' => $RecommendProduct->getId()));
        }

        return $this->redirectToRoute('plugin_recommend_list');
    }

    /**
     * Move rank with ajax.
     *
     * @param Request     $request
     * @throws \Exception
     * @return Response
     *
     * @Route("/%eccube_admin_route%/plugin/recommend/sort_no/move", name="plugin_recommend_rank_move")
     */
    public function moveRank(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $arrRank = $request->request->all();
            $arrRankMoved = $this->recommendProductRepository->moveRecommendRank($arrRank);
            log_info('Recommend move rank', $arrRankMoved);
        }

        return new Response('OK');
    }

    /**
     * 編集画面用のrender.
     *
     * @param array       $parameters
     *
     * @return Response
     */
    protected function registerView($parameters = array())
    {
        // 商品検索フォーム
        $searchProductModalForm = $this->formFactory->createBuilder(SearchProductType::class)->getForm();
        $viewParameters = array(
            'searchProductModalForm' => $searchProductModalForm->createView(),
        );
        $viewParameters += $parameters;

        return $this->render('Recommend/Resource/template/admin/regist.twig', $viewParameters);
    }
}
