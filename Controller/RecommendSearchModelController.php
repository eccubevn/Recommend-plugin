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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception as HttpException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Eccube\Common\Constant;

class RecommendSearchModelController
{

    private $main_title;

    private $sub_title;

    public function __construct()
    {
    }

    /**
     * 商品検索画面を表示する
     * @param Application $app
     * @param Request     $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function searchProduct(Application $app, Request $request, $page_no = null)
    {
        if ($request->isXmlHttpRequest()) {
            $app['monolog']->addDebug('search product start.');
            $page_count = $app['config']['default_page_count'];
            $session = $app['session'];
            if ('POST' === $request->getMethod()) {
                $page_no = 1;
                $searchData = array(
                    'name' => $request->get('id'),
                );
                if ($categoryId = $request->get('category_id')) {
                    $Category = $app['eccube.repository.category']->find($categoryId);
                    $searchData['category_id'] = $Category;
                }
                $session->set('eccube.admin.order.product.search', $searchData);
                $session->set('eccube.admin.order.product.search.page_no', $page_no);
            } else {
                $searchData = (array)$session->get('eccube.admin.order.product.search');
                if (is_null($page_no)) {
                    $page_no = intval($session->get('eccube.admin.order.product.search.page_no'));
                } else {
                    $session->set('eccube.admin.order.product.search.page_no', $page_no);
                }
            }

            /** @var $Products \Eccube\Entity\Product[] */
            $qb = $app['eccube.repository.product']->getQueryBuilderBySearchData($searchData);

            // 除外するproduct_idを設定する
            $existProductId = $request->get('exist_product_id');
            if (strlen($existProductId > 0)) {
                $qb->andWhere($qb->expr()->notin('p.id', ':existProductId'))
                    ->setParameter('existProductId', explode(",", $existProductId));
            }

            $Products = $qb->getQuery()->getResult();

            /** @var \Knp\Component\Pager\Pagination\SlidingPagination $pagination */
            $pagination = $app['paginator']()->paginate(
                $qb,
                $page_no,
                $page_count,
                array('wrap-queries' => true)
            );
            /** @var $Products \Eccube\Entity\Product[] */
            $Products = $pagination->getItems();

            if (is_null($Products)) {
                $app['monolog']->addDebug('search product not found.');
            }

            $forms = array();
            foreach ($Products as $Product) {
                /* @var $builder \Symfony\Component\Form\FormBuilderInterface */
                $builder = $app['form.factory']->createNamedBuilder('', 'add_cart', null, array(
                    'product' => $Product,
                ));
                $addCartForm = $builder->getForm();
                $forms[$Product->getId()] = $addCartForm->createView();
            }

            return $app->render('Recommend/Resource/template/admin/search_product.twig', array(
                'forms' => $forms,
                'Products' => $Products,
                'pagination' => $pagination,
            ));
        }
    }

}
