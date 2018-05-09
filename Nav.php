<?php
/*
 * This file is part of the Recommend plugin
 *
 * Copyright (C) 2016 LOCKON CO.,LTD. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Plugin\Recommend;

use Eccube\Common\EccubeNav;

class Nav implements EccubeNav
{
    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public static function getNav()
    {
        return [
            'content' => [
                'id' => 'plugin_recommend',
                'name' => 'おすすめ管理',
                'url' => 'plugin_recommend_list'
            ]
        ];
    }
}