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

use Eccube\Application;
use Eccube\Common\Constant;

class RecommendService
{
    /** @var \Eccube\Application */
    public $app;

    /** @var \Eccube\Entity\BaseInfo */
    public $BaseInfo;

    /**
     * コンストラクタ
     * @param Application $app
     */
    public function __construct(\Silex\Application $app)
    {
        $this->app = $app;
        $this->BaseInfo = $app['eccube.repository.base_info']->get();
    }

    /**
     * おすすめ商品情報を新規登録する
     * @param $data
     * @return bool
     */
    public function createRecommend($data) {
        // おすすめ商品詳細情報を生成する
        $Recommend = $this->newRecommend($data);

        $em = $this->app['orm.em'];

        // おすすめ商品情報を登録する
        $em->persist($Recommend);

        $em->flush();

        return true;
    }

    /**
     * おすすめ商品情報を更新する
     * @param $data
     * @return bool
     */
    public function updateRecommend($data) {
        $dateTime = new \DateTime();
        $em = $this->app['orm.em'];

        // おすすめ商品情報を取得する
        $Recommend =$this->app['eccube.plugin.recommend.repository.recommend_product']->find($data['id']);
        if(is_null($Recommend)) {
            false;
        }

        // おすすめ商品情報を書き換える
        $Recommend->setComment($data['comment']);
        $Recommend->setProduct($data['Product']);
        $Recommend->setUpdateDate($dateTime);

        // おすすめ商品情報を更新する
        $em->persist($Recommend);

        $em->flush();

        return true;
    }

    /**
     * おすすめ商品情報を削除する
     * @param $recommendId
     * @return bool
     */
    public function deleteRecommend($recommendId) {
        $currentDateTime = new \DateTime();
        $em = $this->app['orm.em'];

        // おすすめ商品情報を取得する
        $Recommend =$this->app['eccube.plugin.recommend.repository.recommend_product']->find($recommendId);
        if(is_null($Recommend)) {
            false;
        }
        // おすすめ商品情報を書き換える
        $Recommend->setDelFlg(Constant::ENABLED);
        $Recommend->setUpdateDate($currentDateTime);

        // おすすめ商品情報を登録する
        $em->persist($Recommend);

        $em->flush();

        return true;
    }

    /**
     * おすすめ商品情報の順位を上げる
     * @param $recommendId
     * @return bool
     */
    public function rankUp($recommendId) {
        $currentDateTime = new \DateTime();
        $em = $this->app['orm.em'];

        // おすすめ商品情報を取得する
        $Recommend =$this->app['eccube.plugin.recommend.repository.recommend_product']->find($recommendId);
        if(is_null($Recommend)) {
            false;
        }
        // 対象ランクの上に位置するおすすめ商品を取得する
        $TargetRecommend =$this->app['eccube.plugin.recommend.repository.recommend_product']
                                ->findByRankUp($Recommend->getRank());
        if(is_null($TargetRecommend)) {
            false;
        }
        
        // ランクを入れ替える
        $rank = $TargetRecommend->getRank();
        $TargetRecommend->setRank($Recommend->getRank());
        $Recommend->setRank($rank);
        
        // 更新日設定
        $Recommend->setUpdateDate($currentDateTime);
        $TargetRecommend->setUpdateDate($currentDateTime);
        
        // 更新
        $em->persist($Recommend);
        $em->persist($TargetRecommend);

        $em->flush();

        return true;
    }

    /**
     * おすすめ商品情報の順位を下げる
     * @param $recommendId
     * @return bool
     */
    public function rankDown($recommendId) {
        $currentDateTime = new \DateTime();
        $em = $this->app['orm.em'];

        // おすすめ商品情報を取得する
        $Recommend =$this->app['eccube.plugin.recommend.repository.recommend_product']->find($recommendId);
        if(is_null($Recommend)) {
            false;
        }
        // 対象ランクの上に位置するおすすめ商品を取得する
        $TargetRecommend =$this->app['eccube.plugin.recommend.repository.recommend_product']
                                ->findByRankDown($Recommend->getRank());
        if(is_null($TargetRecommend)) {
            false;
        }
        
        // ランクを入れ替える
        $rank = $TargetRecommend->getRank();
        $TargetRecommend->setRank($Recommend->getRank());
        $Recommend->setRank($rank);
        
        // 更新日設定
        $Recommend->setUpdateDate($currentDateTime);
        $TargetRecommend->setUpdateDate($currentDateTime);
        
        // 更新
        $em->persist($Recommend);
        $em->persist($TargetRecommend);

        $em->flush();

        return true;
    }

    /**
     * おすすめ商品情報を生成する
     * @param $data
     * @return \Plugin\Recommend\Entity\RecommendProduct
     */
    protected function newRecommend($data) {
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
