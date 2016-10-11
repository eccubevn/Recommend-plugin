<?php
/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) 2000-2015 LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
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
     * @param Application $app
     * @param Request     $request
     * @return Response
     */
    public function index(Application $app, Request $request)
    {
        $pagination = null;

        $pagination = $app['eccube.plugin.recommend.repository.recommend_product']->findBy(array(), array('rank' => 'DESC'));

        return $app->render('Recommend/Resource/template/admin/index.twig', array(
            'pagination' => $pagination,
            'total_item_count' => count($pagination),
        ));
    }

    /**
     * おすすめ商品の新規作成
     * @param Application $app
     * @param Request     $request
     * @param integer     $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function create(Application $app, Request $request, $id)
    {

        $builder = $app['form.factory']->createBuilder('admin_recommend');
        $form = $builder->getForm();

        $Product = null;
        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);
            $data = $form->getData();
            if ($form->isValid()) {
                $service = $app['eccube.plugin.recommend.service.recommend'];
                $status = $service->createRecommend($data);

                if (!$status) {
                    $app->addError('admin.recommend.not_found', 'admin');
                } else {
                    $app->addSuccess('admin.plugin.recommend.register.success', 'admin');
                }

                return $app->redirect($app->url('admin_recommend_list'));
            }

            if (!empty($data['Product'])) {
                $Product = $data['Product'];
            }
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
     * 編集
     * @param Application $app
     * @param Request     $request
     * @param integer     $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function edit(Application $app, Request $request, $id)
    {
        if (!$id) {
            $app->addError('admin.recommend.recommend_id.not_exists', 'admin');

            return $app->redirect($app->url('admin_recommend_list'));
        }

        // IDからおすすめ商品情報を取得する
        $Recommend = $app['eccube.plugin.recommend.repository.recommend_product']->find($id);

        if (!$Recommend) {
            $app->addError('admin.recommend.not_found', 'admin');

            return $app->redirect($app->url('admin_recommend_list'));
        }

        // formの作成
        $form = $app['form.factory']
            ->createBuilder('admin_recommend', $Recommend)
            ->getForm();

        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $service = $app['eccube.plugin.recommend.service.recommend'];
                $status = $service->updateRecommend($form->getData());

                if (!$status) {
                    $app->addError('admin.recommend.not_found', 'admin');
                } else {
                    $app->addSuccess('admin.plugin.recommend.update.success', 'admin');
                }

                return $app->redirect($app->url('admin_recommend_list'));
            }
        }

        return $this->registerView(
            $app,
            array(
                'form' => $form->createView(),
                'Product' => $Recommend->getProduct(),
            )
        );
    }

    /**
     * おすすめ商品の削除
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
     * 上へ
     * @param Application $app
     * @param Request     $request
     * @param integer     $id
     * @deprecated It will be removed before release.
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function rankUp(Application $app, Request $request, $id)
    {
        // Valid token
        $this->isTokenValid($app);

        if (!$id) {
            $app->addError('admin.recommend.recommend_id.not_exists', 'admin');

            return $app->redirect($app->url('admin_recommend_list'));
        }

        // IDからおすすめ商品情報を取得する
        $Recommend = $app['eccube.plugin.recommend.repository.recommend_product']->find($id);
        if (!$Recommend) {
            $app->addError('admin.recommend.not_found', 'admin');

            return $app->redirect($app->url('admin_recommend_list'));
        }

        // ランクアップ
        $service = $app['eccube.plugin.recommend.service.recommend'];
        $service->rankUp($id);

        $app->addSuccess('admin.plugin.recommend.complete.up', 'admin');

        return $app->redirect($app->url('admin_recommend_list'));
    }

    /**
     * 下へ
     * @param Application $app
     * @param Request     $request
     * @param integer     $id
     * @deprecated It will be removed before release.
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function rankDown(Application $app, Request $request, $id)
    {
        $this->isTokenValid($app);

        if (!$id) {
            $app->addError('admin.recommend.recommend_id.not_exists', 'admin');

            return $app->redirect($app->url('admin_recommend_list'));
        }


        // IDからおすすめ商品情報を取得する
        $Recommend = $app['eccube.plugin.recommend.repository.recommend_product']->find($id);
        if (!$Recommend) {
            $app->addError('admin.recommend.not_found', 'admin');

            return $app->redirect($app->url('admin_recommend_list'));
        }

        // ランクアップ
        $service = $app['eccube.plugin.recommend.service.recommend'];
        $service->rankDown($id);

        $app->addSuccess('admin.plugin.recommend.complete.down', 'admin');

        return $app->redirect($app->url('admin_recommend_list'));
    }

    /**
     * 編集画面用のrender
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
