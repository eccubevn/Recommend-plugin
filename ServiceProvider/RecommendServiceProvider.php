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

namespace Plugin\Recommend\ServiceProvider;

use Monolog\Handler\FingersCrossed\ErrorLevelActivationStrategy;
use Monolog\Handler\FingersCrossedHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Plugin\Recommend\Form\Type\RecommendProductType;
use Plugin\Recommend\Service\RecommendService;
use Silex\Application as BaseApplication;
use Silex\ServiceProviderInterface;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Yaml\Yaml;

/**
 * Class RecommendServiceProvider
 * @package Plugin\Recommend\ServiceProvider
 */
class RecommendServiceProvider implements ServiceProviderInterface
{
    /**
     * @param BaseApplication $app
     */
    public function register(BaseApplication $app)
    {
        // おすすめ情報テーブルリポジトリ
        $app['eccube.plugin.recommend.repository.recommend_product'] = $app->share(function () use ($app) {
            return $app['orm.em']->getRepository('Plugin\Recommend\Entity\RecommendProduct');
        });

        // おすすめ商品の一覧
        $app->match('/'.$app["config"]["admin_route"].'/recommend', '\Plugin\Recommend\Controller\RecommendController::index')
            ->value('id', null)->assert('id', '\d+|')
            ->bind('admin_recommend_list');

        // おすすめ商品の新規先
        $app->match('/'.$app["config"]["admin_route"].'/recommend/new', '\Plugin\Recommend\Controller\RecommendController::create')
            ->value('id', null)->assert('id', '\d+|')
            ->bind('admin_recommend_new');

        // おすすめ商品の新規作成・編集確定
        $app->match('/'.$app["config"]["admin_route"].'/recommend/commit', '\Plugin\Recommend\Controller\RecommendController::commit')
        ->value('id', null)->assert('id', '\d+|')
        ->bind('admin_recommend_commit');

        // おすすめ商品の編集
        $app->match('/'.$app["config"]["admin_route"].'/recommend/edit/{id}', '\Plugin\Recommend\Controller\RecommendController::edit')
            ->value('id', null)->assert('id', '\d+|')
            ->bind('admin_recommend_edit');

        // おすすめ商品の削除
        $app->match('/'.$app["config"]["admin_route"].'/recommend/delete/{id}', '\Plugin\Recommend\Controller\RecommendController::delete')
        ->value('id', null)->assert('id', '\d+|')
        ->bind('admin_recommend_delete');

        // おすすめ商品のランク移動（上）
        $app->match('/'.$app["config"]["admin_route"].'/recommend/rank_up/{id}', '\Plugin\Recommend\Controller\RecommendController::rankUp')
            ->value('id', null)->assert('id', '\d+|')
            ->bind('admin_recommend_rank_up');

        // おすすめ商品のランク移動（下）
        $app->match('/'.$app["config"]["admin_route"].'/recommend/rank_down/{id}', '\Plugin\Recommend\Controller\RecommendController::rankDown')
            ->value('id', null)->assert('id', '\d+|')
            ->bind('admin_recommend_rank_down');

        // 商品検索画面表示
        $app->post('/'.$app["config"]["admin_route"].'/recommend/search/product', '\Plugin\Recommend\Controller\RecommendSearchModelController::searchProduct')
            ->bind('admin_recommend_search_product');

        // ブロック
        $app->match('/block/recommend_product_block', '\Plugin\Recommend\Controller\Block\RecommendController::index')
            ->bind('block_recommend_product_block');


        // 型登録
        $app['form.types'] = $app->share($app->extend('form.types', function ($types) use ($app) {
            $types[] = new RecommendProductType($app);

            return $types;
        }));

        // サービスの登録
        $app['eccube.plugin.recommend.service.recommend'] = $app->share(function () use ($app) {
            return new RecommendService($app);
        });

        // メッセージ登録
        $app['translator'] = $app->share($app->extend('translator', function ($translator, \Silex\Application $app) {
            $translator->addLoader('yaml', new YamlFileLoader());

            $file = __DIR__.'/../Resource/locale/message.'.$app['locale'].'.yml';
            if (file_exists($file)) {
                $translator->addResource('yaml', $file, $app['locale']);
            }

            return $translator;
        }));

        // メニュー登録
        $app['config'] = $app->share($app->extend('config', function ($config) {
            // menu bar
            $addNavi['id'] = 'admin_recommend';
            $addNavi['name'] = 'おすすめ管理';
            $addNavi['url'] = 'admin_recommend_list';
            $nav = $config['nav'];
            foreach ($nav as $key => $val) {
                if ('content' == $val['id']) {
                    $nav[$key]['child'][] = $addNavi;
                }
            }
            $config['nav'] = $nav;

            // Update constants
            $constantFile = __DIR__.'/../Resource/config/constant.yml';
            if (file_exists($constantFile)) {
                $constant = Yaml::parse(file_get_contents($constantFile));
                if (!empty($constant)) {
                    // Replace constants
                    $config = array_replace_recursive($config, $constant);
                }
            }

            return $config;
        }));

        // ログファイル設定
        $app['monolog.Recommend'] = $app->share(function ($app) {
            $loggerClass = $app['monolog.logger.class'];
            $loggerName = 'plugin.Recommend';
            $logger = new $loggerClass($loggerName);

            $file = $app['config']['root_dir'].'/app/log/Recommend.log';
            $rotateHandler = new RotatingFileHandler($file, $app['config']['log']['max_files'], Logger::INFO);
            $rotateHandler->setFilenameFormat(
                'Recommend_{date}',
                'Y-m-d'
            );

            $logger->pushHandler(
                new FingersCrossedHandler(
                    $rotateHandler,
                    new ErrorLevelActivationStrategy(Logger::INFO)
                )
            );

            return $logger;
        });
    }

    /**
     * @param BaseApplication $app
     */
    public function boot(BaseApplication $app)
    {
    }
}
