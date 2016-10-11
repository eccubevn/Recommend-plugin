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

namespace Plugin\Recommend\Service;

use Eccube\Common\Constant;

/**
 * Class RecommendService
 * @package Plugin\Recommend\Service
 */
class RecommendService
{
    /** @var \Eccube\Application */
    public $app;

    /** @var \Eccube\Entity\BaseInfo */
    public $BaseInfo;

    /**
     * コンストラクタ
     * @param object $app
     */
    public function __construct($app)
    {
        $this->app = $app;
        $this->BaseInfo = $app['eccube.repository.base_info']->get();
    }

    /**
     * おすすめ商品情報を新規登録する
     * @param array $data
     * @return bool
     */
    public function createRecommend($data)
    {
        // おすすめ商品詳細情報を生成する
        $Recommend = $this->newRecommend($data);

        return $this->app['eccube.plugin.recommend.repository.recommend_product']->saveRecommend($Recommend);
    }

    /**
     * おすすめ商品情報を更新する
     * @param array $data
     * @return bool
     */
    public function updateRecommend($data)
    {
        $dateTime = new \DateTime();

        // おすすめ商品情報を取得する
        $Recommend = $this->app['eccube.plugin.recommend.repository.recommend_product']->find($data['id']);
        if(!$Recommend) {
            return false;
        }

        // おすすめ商品情報を書き換える
        $Recommend->setComment($data['comment']);
        $Recommend->setProduct($data['Product']);
        $Recommend->setUpdateDate($dateTime);

        // おすすめ商品情報を更新する
        return $this->app['eccube.plugin.recommend.repository.recommend_product']->saveRecommend($Recommend);
    }

    /**
     * おすすめ商品情報を削除する
     * @param integer $recommendId
     * @return bool
     */
    public function deleteRecommend($recommendId)
    {
        $currentDateTime = new \DateTime();

        // おすすめ商品情報を取得する
        $Recommend =$this->app['eccube.plugin.recommend.repository.recommend_product']->find($recommendId);
        if(!$Recommend) {
            return false;
        }
        // おすすめ商品情報を書き換える
        $Recommend->setDelFlg(Constant::ENABLED);
        $Recommend->setUpdateDate($currentDateTime);

        // おすすめ商品情報を登録する
        return $this->app['eccube.plugin.recommend.repository.recommend_product']->saveRecommend($Recommend);
    }

    /**
     * おすすめ商品情報を生成する
     * @param array $data
     * @return \Plugin\Recommend\Entity\RecommendProduct
     */
    protected function newRecommend($data)
    {
        $dateTime = new \DateTime();

        $rank = $this->app['eccube.plugin.recommend.repository.recommend_product']->getMaxRank();

        $Recommend = new \Plugin\Recommend\Entity\RecommendProduct();
        $Recommend->setComment($data['comment']);
        $Recommend->setProduct($data['Product']);
        $Recommend->setRank(($rank ? $rank : 0) + 1);
        $Recommend->setDelFlg(Constant::DISABLED);
        $Recommend->setCreateDate($dateTime);
        $Recommend->setUpdateDate($dateTime);

        return $Recommend;
    }
}
