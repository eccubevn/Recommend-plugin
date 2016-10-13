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

use Eccube\Application;
use Eccube\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Class RecommendController
 * @package Plugin\Recommend\Controller
 */
class RecommendController extends AbstractController
{
    /**
     * おすすめ商品一覧
     *
     * @param Application $app
     * @param Request     $request
     * @return Response
     */
    public function index(Application $app, Request $request)
    {
        $limit = $app['config']['recommend_limit'];
        $pagination = $app['eccube.plugin.recommend.repository.recommend_product']->findBy(array(), array('rank' => 'DESC'), $limit);

        return $app->render('Recommend/Resource/template/admin/index.twig', array(
            'pagination' => $pagination,
            'total_item_count' => count($pagination),
        ));
    }

    /**
     * Create & Edit
     *
     * @param Application $app
     * @param Request     $request
     * @param integer     $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function edit(Application $app, Request $request, $id = null)
    {
        $Recommend = null;
        $Product = null;
        if (!is_null($id)) {
            // IDからおすすめ商品情報を取得する
            $Recommend = $app['eccube.plugin.recommend.repository.recommend_product']->find($id);

            if (!$Recommend) {
                $app->addError('admin.recommend.not_found', 'admin');

                return $app->redirect($app->url('admin_recommend_list'));
            }

            $Product = $Recommend->getProduct();
        }

        // formの作成
        $form = $app['form.factory']
            ->createBuilder('admin_recommend', $Recommend)
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $service = $app['eccube.plugin.recommend.service.recommend'];
            $data = $form->getData();
            if (is_null($data['id'])) {
                $status = $service->createRecommend($data);
            } else {
                $status = $service->updateRecommend($data);
            }

            if (!$status) {
                $app->addError('admin.recommend.not_found', 'admin');
            } else {
                $app->addSuccess('admin.plugin.recommend.update.success', 'admin');
            }

            return $app->redirect($app->url('admin_recommend_list'));
        }

        return $this->registerView(
            $app,
            array(
                'form' => $form->createView(),
                'Product' => $Product,
            )
        );
    }

    /**
     * おすすめ商品の削除
     *
     * @param Application $app
     * @param Request     $request
     * @param integer     $id
     * @throws BadRequestHttpException
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function delete(Application $app, Request $request, $id)
    {
        // Valid token
        $this->isTokenValid($app);

        // Check request
        if (!'POST' === $request->getMethod()) {
            throw new BadRequestHttpException();
        }

        // Id valid
        if (!$id) {
            $app->addError('admin.recommend.recommend_id.not_exists', 'admin');

            return $app->redirect($app->url('admin_recommend_list'));
        }

        $service = $app['eccube.plugin.recommend.service.recommend'];

        // おすすめ商品情報を削除する
        if ($service->deleteRecommend($id)) {
            $app->addSuccess('admin.plugin.recommend.delete.success', 'admin');
        } else {
            $app->addError('admin.recommend.not_found', 'admin');
        }

        return $app->redirect($app->url('admin_recommend_list'));
    }

    /**
     * Move rank with ajax
     *
     * @param Application $app
     * @param Request     $request
     * @return bool
     */
    public function moveRank(Application $app, Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $arrRank = $request->request->all();
            $app['eccube.plugin.recommend.repository.recommend_product']->moveRecommendRank($arrRank);
        }

        return true;
    }

    /**
     * 編集画面用のrender
     *
     * @param Application $app
     * @param array $parameters
     * @return Response
     */
    protected function registerView($app, $parameters = array())
    {
        // 商品検索フォーム
        $searchProductModalForm = $app['form.factory']->createBuilder('admin_search_product')->getForm();
        $viewParameters = array(
            'searchProductModalForm' => $searchProductModalForm->createView(),
        );
        $viewParameters += $parameters;

        return $app->render('Recommend/Resource/template/admin/regist.twig', $viewParameters);
    }
}
