<?php
/*
 * This file is part of the Recommend Product plugin
 *
 * Copyright (C) 2016 LOCKON CO.,LTD. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\Recommend\Twig\Extension;

/**
 * Class FilterBlackListedExtension
 * @package Plugin\Recommend\Twig\Extension
 */
class FilterBlackListedExtension extends \Twig_Extension
{
    private $blacklistedTags = array('script');

    /**
     * @return array
     */
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('filter_black_listed', array($this, 'htmlFilter')),
        );
    }

    /**
     * Filter
     *
     * @param string $html
     * @return mixed
     */
    public function htmlFilter($html)
    {
        foreach ($this->blacklistedTags as $tag) {
            $html = str_replace(array('<'.$tag.'>', '</'.$tag.'>'), array('&lt;'.$tag.'&gt;', '&lt;/'.$tag.'&gt;'), $html);
        }

        return $html;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'filter_black_listed_extension';
    }
}
